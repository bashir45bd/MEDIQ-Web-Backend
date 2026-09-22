<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Response;
use PDO;
use Throwable;

class QueueService {
    public function callNext(int $doctorId, int $chamberId, string $date, int $actorUserId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Find next eligible patient in queue
            $stmt = $pdo->prepare("
                SELECT a.id, a.token_display, a.token_number, a.patient_id, p.user_id, u.full_name AS patient_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
                  AND a.status IN ('CHECKED_IN', 'WAITING')
                ORDER BY a.token_number ASC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $nextApt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$nextApt) {
                $pdo->rollBack();
                Response::error('NO_PATIENT_WAITING', 'No patients currently waiting in the chamber queue.', 404);
            }

            $aptId = (int)$nextApt['id'];

            // Transition status to CALLED
            $upStmt = $pdo->prepare("UPDATE appointments SET status = 'CALLED' WHERE id = :id");
            $upStmt->execute(['id' => $aptId]);

            $qUpStmt = $pdo->prepare("UPDATE appointment_queue SET status = 'CALLED', called_at = NOW() WHERE appointment_id = :aid");
            $qUpStmt->execute(['aid' => $aptId]);

            // Record queue event
            $evStmt = $pdo->prepare("
                INSERT INTO queue_events (appointment_id, doctor_id, chamber_id, appointment_date, event_type, actor_id, actor_role, created_at)
                VALUES (:aid, :did, :cid, :dt, 'CALL', :act, 'DOCTOR', NOW())
            ");
            $evStmt->execute(['aid' => $aptId, 'did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'act' => $actorUserId]);

            $pdo->commit();

            AuditService::log($actorUserId, 'DOCTOR', 'QUEUE_CALL_NEXT', 'appointments', $aptId, null, [
                'token' => $nextApt['token_display']
            ]);

            // Push Notification to Called Patient
            try {
                $docInfo = $this->getDoctorChamberDetails($pdo, $doctorId, $chamberId);
                $docName = $docInfo['doctor_name'];
                $chamberName = $docInfo['chamber_name'];

                if (!empty($nextApt['user_id'])) {
                    NotificationService::dispatch(
                        (int)$nextApt['user_id'],
                        'PATIENT_CALLED',
                        'APT_' . $aptId . '_CALLED_' . time(),
                        'ALL',
                        "🚨 ডা. {$docName}-এর চেম্বারে আপনার সিরিয়াল ডাকা হয়েছে! ({$nextApt['token_display']})",
                        "অনুগ্রহ করে ডা. {$docName}-এর চেম্বারে ({$chamberName}) প্রবেশ করুন। আপনার সিরিয়াল: {$nextApt['token_display']}।",
                        [
                            'appointment_id' => (string)$aptId,
                            'token_display'  => $nextApt['token_display'],
                            'doctor_name'    => $docName,
                            'chamber_name'   => $chamberName,
                            'type'           => 'patient_called',
                            'priority'       => 'high'
                        ]
                    );
                }

                // Push "Ready" Alert to the Next Patient in Queue
                $nextInQueueStmt = $pdo->prepare("
                    SELECT a.id, a.token_display, p.user_id, u.full_name AS patient_name
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    JOIN users u ON p.user_id = u.id
                    WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
                      AND a.status IN ('CHECKED_IN', 'WAITING')
                    ORDER BY a.token_number ASC
                    LIMIT 1
                ");
                $nextInQueueStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
                $nextWaiting = $nextInQueueStmt->fetch(PDO::FETCH_ASSOC);

                if ($nextWaiting && !empty($nextWaiting['user_id'])) {
                    NotificationService::dispatch(
                        (int)$nextWaiting['user_id'],
                        'PATIENT_READY',
                        'APT_' . $nextWaiting['id'] . '_READY_' . time(),
                        'ALL',
                        "⏳ আপনার সিরিয়াল পরবর্তী! প্রস্তুত থাকুন (ডা. {$docName})",
                        "আপনার সিরিয়াল: {$nextWaiting['token_display']}। ডা. {$docName}-এর চেম্বারে ({$chamberName}) বর্তমান রোগীর পরেই আপনার ডাক পড়বে, চেম্বারের কাছাকাছি প্রস্তুত থাকুন।",
                        [
                            'appointment_id' => (string)$nextWaiting['id'],
                            'token_display'  => $nextWaiting['token_display'],
                            'doctor_name'    => $docName,
                            'chamber_name'   => $chamberName,
                            'type'           => 'patient_ready',
                            'priority'       => 'high'
                        ]
                    );
                }
            } catch (Throwable $ne) {
                error_log("[NOTIFICATION CALL ERROR] " . $ne->getMessage());
            }

            return $nextApt;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function startConsultation(int $appointmentId, int $doctorId, int $actorUserId): void {
        $pdo = Database::getConnection();
        // Concurrency-safe atomic state transition: Only succeeds if status is CALLED
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'CONSULTING', consultation_start_time = NOW()
            WHERE id = :id AND doctor_id = :did AND status = 'CALLED'
        ");
        $stmt->execute(['id' => $appointmentId, 'did' => $doctorId]);

        if ($stmt->rowCount() === 0) {
            Response::error('INVALID_STATE_TRANSITION', 'Patient must be in CALLED state to start consultation.', 409);
        }

        $pdo->prepare("UPDATE appointment_queue SET status = 'CONSULTING', started_at = NOW() WHERE appointment_id = :aid")
            ->execute(['aid' => $appointmentId]);

        AuditService::log($actorUserId, 'DOCTOR', 'CONSULTATION_STARTED', 'appointments', $appointmentId);
    }

    public function completeConsultation(int $appointmentId, int $doctorId, int $actorUserId): void {
        $pdo = Database::getConnection();
        // Concurrency-safe atomic check: Succeeds if status is CONSULTING or CALLED
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'COMPLETED', 
                consultation_start_time = COALESCE(consultation_start_time, NOW()),
                consultation_end_time = NOW()
            WHERE id = :id AND doctor_id = :did AND status IN ('CALLED', 'CONSULTING')
        ");
        $stmt->execute(['id' => $appointmentId, 'did' => $doctorId]);

        if ($stmt->rowCount() === 0) {
            Response::error('INVALID_STATE_TRANSITION', 'Patient must be in CALLED or CONSULTING state to complete.', 409);
        }

        $pdo->prepare("
            UPDATE appointment_queue 
            SET status = 'COMPLETED', 
                started_at = COALESCE(started_at, NOW()),
                completed_at = NOW() 
            WHERE appointment_id = :aid
        ")->execute(['aid' => $appointmentId]);

        AuditService::log($actorUserId, 'DOCTOR', 'CONSULTATION_COMPLETED', 'appointments', $appointmentId);
    }

    public function skipPatient(int $appointmentId, int $doctorId, int $actorUserId): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'SKIPPED'
            WHERE id = :id AND doctor_id = :did AND status = 'CALLED'
        ");
        $stmt->execute(['id' => $appointmentId, 'did' => $doctorId]);

        if ($stmt->rowCount() === 0) {
            Response::error('INVALID_STATE_TRANSITION', 'Only CALLED patients can be skipped.', 409);
        }

        $pdo->prepare("UPDATE appointment_queue SET status = 'SKIPPED' WHERE appointment_id = :aid")
            ->execute(['aid' => $appointmentId]);

        AuditService::log($actorUserId, 'DOCTOR', 'PATIENT_SKIPPED', 'appointments', $appointmentId);
    }

    public function markNoShow(int $appointmentId, int $doctorId, int $actorUserId): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'NO_SHOW'
            WHERE id = :id AND doctor_id = :did AND status = 'CALLED'
        ");
        $stmt->execute(['id' => $appointmentId, 'did' => $doctorId]);

        if ($stmt->rowCount() === 0) {
            Response::error('INVALID_STATE_TRANSITION', 'Only CALLED patients can be marked as No-Show.', 409);
        }

        $pdo->prepare("UPDATE appointment_queue SET status = 'NO_SHOW' WHERE appointment_id = :aid")
            ->execute(['aid' => $appointmentId]);

        AuditService::log($actorUserId, 'DOCTOR', 'PATIENT_NO_SHOW', 'appointments', $appointmentId);
    }

    public function broadcastDelay(int $doctorId, int $chamberId, string $date, int $delayMinutes, ?string $note, int $actorUserId): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE doctor_daily_availability
            SET doctor_live_status = 'DELAYED', delay_minutes = :dm, status_note = :nt, last_status_updated_at = NOW()
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
        ");
        $stmt->execute([
            'dm'  => $delayMinutes,
            'nt'  => $note ?: ("Doctor delayed by {$delayMinutes} minutes due to traffic/emergency."),
            'did' => $doctorId,
            'cid' => $chamberId,
            'dt'  => $date
        ]);

        AuditService::log($actorUserId, 'DOCTOR', 'BROADCAST_DELAY', 'doctor_daily_availability', $chamberId, null, [
            'delay_minutes' => $delayMinutes
        ]);

        // Push notification to all waiting patients for today's session
        try {
            $docInfo = $this->getDoctorChamberDetails($pdo, $doctorId, $chamberId);
            $docName = $docInfo['doctor_name'];
            $chamberName = $docInfo['chamber_name'];

            $pStmt = $pdo->prepare("
                SELECT DISTINCT p.user_id, a.id AS appointment_id, a.token_display
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
                  AND a.status IN ('BOOKED', 'CHECKED_IN', 'WAITING', 'CALLED')
            ");
            $pStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $waitingPatients = $pStmt->fetchAll(PDO::FETCH_ASSOC);

            $delayTitle = "⚠️ ডা. {$docName}-এর চেম্বার বিলম্বের নোটিশ";
            $delayBody = "জরুরি কারণে ডা. {$docName}-এর চেম্বার ({$chamberName}) শুরু হতে আনুমানিক {$delayMinutes} মিনিট দেরি হবে। আপনার ধৈর্য্যের জন্য ধন্যবাদ।";

            foreach ($waitingPatients as $wp) {
                NotificationService::dispatch(
                    (int)$wp['user_id'],
                    'DOCTOR_DELAY',
                    'DELAY_' . $wp['appointment_id'] . '_' . date('Ymd_H'),
                    'ALL',
                    $delayTitle,
                    $delayBody,
                    [
                        'appointment_id' => (string)$wp['appointment_id'],
                        'doctor_name'    => $docName,
                        'chamber_name'   => $chamberName,
                        'delay_minutes'  => (string)$delayMinutes,
                        'type'           => 'doctor_delay'
                    ]
                );
            }
        } catch (Throwable $ne) {
            error_log("[NOTIFICATION DELAY ERROR] " . $ne->getMessage());
        }
    }

    public function recallSkippedPatient(int $appointmentId, int $doctorId, int $actorUserId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                SELECT a.id, a.chamber_id, a.token_display, a.token_number, a.patient_id, p.user_id, u.full_name AS patient_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE a.id = :id AND a.doctor_id = :did AND a.status = 'SKIPPED'
                FOR UPDATE
            ");
            $stmt->execute(['id' => $appointmentId, 'did' => $doctorId]);
            $apt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$apt) {
                $pdo->rollBack();
                Response::error('APPOINTMENT_NOT_FOUND', 'Skipped patient not found or already processed.', 404);
            }

            // Re-activate to CALLED state
            $pdo->prepare("UPDATE appointments SET status = 'CALLED' WHERE id = :id")->execute(['id' => $appointmentId]);
            $pdo->prepare("UPDATE appointment_queue SET status = 'CALLED', called_at = NOW() WHERE appointment_id = :aid")->execute(['aid' => $appointmentId]);

            $pdo->prepare("
                INSERT INTO queue_events (appointment_id, doctor_id, chamber_id, appointment_date, event_type, actor_id, actor_role, created_at)
                SELECT id, doctor_id, chamber_id, appointment_date, 'RECALL', :act, 'DOCTOR', NOW()
                FROM appointments WHERE id = :aid
            ")->execute(['aid' => $appointmentId, 'act' => $actorUserId]);

            $pdo->commit();

            AuditService::log($actorUserId, 'DOCTOR', 'PATIENT_RECALLED', 'appointments', $appointmentId, null, [
                'token' => $apt['token_display']
            ]);

            if (!empty($apt['user_id'])) {
                $docInfo = $this->getDoctorChamberDetails($pdo, $doctorId, (int)($apt['chamber_id'] ?? 0));
                $docName = $docInfo['doctor_name'];
                $chamberName = $docInfo['chamber_name'];

                NotificationService::dispatch(
                    (int)$apt['user_id'],
                    'PATIENT_CALLED',
                    'APT_' . $appointmentId . '_RECALLED_' . time(),
                    'ALL',
                    "🚨 ডা. {$docName}-এর চেম্বারে আপনার সিরিয়াল পুনরায় ডাকা হয়েছে!",
                    "অনুগ্রহ করে দ্রুত ডা. {$docName}-এর চেম্বারে ({$chamberName}) প্রবেশ করুন। আপনার সিরিয়াল: {$apt['token_display']}।",
                    [
                        'appointment_id' => (string)$appointmentId,
                        'token_display'  => $apt['token_display'],
                        'doctor_name'    => $docName,
                        'chamber_name'   => $chamberName,
                        'type'           => 'patient_called',
                        'priority'       => 'high'
                    ]
                );
            }

            return $apt;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function swapQueuePositions(int $aptId1, int $aptId2, int $doctorId, int $actorUserId): void {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt1 = $pdo->prepare("SELECT id, token_number, token_display, status FROM appointments WHERE id = :id AND doctor_id = :did FOR UPDATE");
            $stmt1->execute(['id' => $aptId1, 'did' => $doctorId]);
            $a1 = $stmt1->fetch(PDO::FETCH_ASSOC);

            $stmt2 = $pdo->prepare("SELECT id, token_number, token_display, status FROM appointments WHERE id = :id AND doctor_id = :did FOR UPDATE");
            $stmt2->execute(['id' => $aptId2, 'did' => $doctorId]);
            $a2 = $stmt2->fetch(PDO::FETCH_ASSOC);

            if (!$a1 || !$a2) {
                $pdo->rollBack();
                Response::error('APPOINTMENT_NOT_FOUND', 'One or both appointments not found.', 404);
            }

            // Swap token_number and token_display
            $pdo->prepare("UPDATE appointments SET token_number = :tn, token_display = :td WHERE id = :id")
                ->execute(['tn' => $a2['token_number'], 'td' => $a2['token_display'], 'id' => $aptId1]);

            $pdo->prepare("UPDATE appointments SET token_number = :tn, token_display = :td WHERE id = :id")
                ->execute(['tn' => $a1['token_number'], 'td' => $a1['token_display'], 'id' => $aptId2]);

            // Also swap sequence in appointment_queue
            $pdo->prepare("UPDATE appointment_queue SET sequence_number = :seq WHERE appointment_id = :aid")
                ->execute(['seq' => $a2['token_number'], 'aid' => $aptId1]);

            $pdo->prepare("UPDATE appointment_queue SET sequence_number = :seq WHERE appointment_id = :aid")
                ->execute(['seq' => $a1['token_number'], 'aid' => $aptId2]);

            $pdo->commit();

            AuditService::log($actorUserId, 'DOCTOR', 'QUEUE_SWAP', 'appointments', $aptId1, null, [
                'swapped_with' => $aptId2,
                'token1' => $a1['token_display'],
                'token2' => $a2['token_display']
            ]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateDoctorPresence(int $doctorId, int $chamberId, string $date, string $presenceStatus, ?string $note, int $actorUserId): void {
        $allowed = ['IN_CHAMBER', 'AWAY', 'PRAYER_BREAK', 'MEAL_BREAK', 'SHORT_PAUSE'];
        if (!in_array($presenceStatus, $allowed, true)) {
            $presenceStatus = 'IN_CHAMBER';
        }

        $liveStatus = ($presenceStatus === 'IN_CHAMBER') ? 'AVAILABLE' : 'SHORT_BREAK';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO doctor_daily_availability (doctor_id, chamber_id, appointment_date, status, doctor_live_status, presence_status, status_note, last_status_updated_at)
            VALUES (:did, :cid, :dt, 'ON', :ls, :ps, :nt, NOW())
            ON DUPLICATE KEY UPDATE 
                doctor_live_status = VALUES(doctor_live_status),
                presence_status = VALUES(presence_status),
                status_note = VALUES(status_note),
                last_status_updated_at = NOW()
        ");
        $stmt->execute([
            'did' => $doctorId,
            'cid' => $chamberId,
            'dt'  => $date,
            'ls'  => $liveStatus,
            'ps'  => $presenceStatus,
            'nt'  => $note
        ]);

        AuditService::log($actorUserId, 'DOCTOR', 'UPDATE_PRESENCE', 'doctor_daily_availability', $chamberId, null, [
            'presence_status' => $presenceStatus,
            'note' => $note
        ]);

        // Push targeted notification to waiting patients of this doctor & chamber today
        try {
            $docInfo = $this->getDoctorChamberDetails($pdo, $doctorId, $chamberId);
            $docName = $docInfo['doctor_name'];
            $chamberName = $docInfo['chamber_name'];

            $pStmt = $pdo->prepare("
                SELECT DISTINCT p.user_id, a.id AS appointment_id, a.token_display
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
                  AND a.status IN ('BOOKED', 'CHECKED_IN', 'WAITING', 'CALLED')
            ");
            $pStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $waitingPatients = $pStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($waitingPatients)) {
                $statusMap = [
                    'PRAYER_BREAK' => ['🕌 নামাজের বিরতি', 'নামাজের বিরতিতে আছেন। চেম্বার সাময়িক স্থগিত আছে।'],
                    'MEAL_BREAK'   => ['🍽️ খাবারের বিরতি', 'খাবারের বিরতিতে আছেন। চেম্বার সাময়িক স্থগিত আছে।'],
                    'SHORT_PAUSE'  => ['⏸️ সাময়িক বিরতি', 'সাময়িক বিরতিতে আছেন। কিছুক্ষণের মধ্যেই রোগী দেখা শুরু হবে।'],
                    'AWAY'         => ['🚪 চেম্বারের বাইরে', 'চেম্বারের বাইরে আছেন। কিছুক্ষণের মধ্যেই ফিরে আসবেন।'],
                    'IN_CHAMBER'   => ['🟢 চেম্বার পুনরায় চালু', 'চেম্বারে উপস্থিত আছেন এবং রোগী দেখা পুনরায় শুরু হয়েছে।']
                ];

                $info = $statusMap[$presenceStatus] ?? ['☕ বিরতির নোটিশ', 'বিরতিতে আছেন।'];
                $customNote = !empty($note) ? " ({$note})" : "";
                $title = "{$info[0]} — ডা. {$docName}";
                $body = "ডা. {$docName} ({$chamberName}) {$info[1]}{$customNote}";

                foreach ($waitingPatients as $wp) {
                    NotificationService::dispatch(
                        (int)$wp['user_id'],
                        'DOCTOR_PRESENCE_UPDATE',
                        'PRESENCE_' . $wp['appointment_id'] . '_' . $presenceStatus . '_' . time(),
                        'ALL',
                        $title,
                        $body,
                        [
                            'appointment_id'  => (string)$wp['appointment_id'],
                            'doctor_name'     => $docName,
                            'chamber_name'    => $chamberName,
                            'presence_status' => $presenceStatus,
                            'type'            => 'doctor_presence'
                        ]
                    );
                }
            }
        } catch (Throwable $pe) {
            error_log("[NOTIFICATION PRESENCE ERROR] " . $pe->getMessage());
        }
    }

    public function toggleChamberBooking(int $doctorId, int $chamberId, string $date, bool $isOpen, int $actorUserId): void {
        $status = $isOpen ? 'ON' : 'CLOSED';
        $isBookingOpen = $isOpen ? 1 : 0;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO doctor_daily_availability (doctor_id, chamber_id, appointment_date, status, is_booking_open, last_status_updated_at)
            VALUES (:did, :cid, :dt, :st, :bk, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                is_booking_open = VALUES(is_booking_open),
                last_status_updated_at = NOW()
        ");
        $stmt->execute([
            'did' => $doctorId,
            'cid' => $chamberId,
            'dt'  => $date,
            'st'  => $status,
            'bk'  => $isBookingOpen
        ]);

        AuditService::log($actorUserId, 'DOCTOR', 'TOGGLE_BOOKING', 'doctor_daily_availability', $chamberId, null, [
            'is_open' => $isOpen
        ]);

        if ($isOpen) {
            try {
                $docInfo = $this->getDoctorChamberDetails($pdo, $doctorId, $chamberId);
                $docName = $docInfo['doctor_name'];
                $chamberName = $docInfo['chamber_name'];

                $favStmt = $pdo->prepare("SELECT user_id FROM favorites WHERE doctor_id = :did");
                $favStmt->execute(['did' => $doctorId]);
                $favUsers = $favStmt->fetchAll(PDO::FETCH_COLUMN);

                $title = "🔔 সিরিয়াল বুকিং চালু হয়েছে!";
                $body = "ডা. {$docName}-এর চেম্বারে ({$chamberName}) আজকের সিরিয়াল বুকিং চালু হয়েছে। এখনই সিরিয়াল নিন।";

                foreach ($favUsers as $fuid) {
                    NotificationService::dispatch(
                        (int)$fuid,
                        'BOOKING_OPENED',
                        'DOC_' . $doctorId . '_BOOKING_OPEN_' . date('Ymd_H'),
                        'PUSH',
                        $title,
                        $body,
                        ['doctor_id' => (string)$doctorId, 'chamber_id' => (string)$chamberId, 'doctor_name' => $docName, 'type' => 'booking_opened']
                    );
                }
            } catch (Throwable $e) {
                error_log("[BOOKING BROADCAST ERROR] " . $e->getMessage());
            }
        }
    }

    private function getDoctorChamberDetails(PDO $pdo, int $doctorId, int $chamberId): array {
        $stmt = $pdo->prepare("
            SELECT u.full_name AS doctor_name, c.name AS chamber_name 
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            JOIN doctor_chambers c ON c.id = :cid
            WHERE d.id = :did
            LIMIT 1
        ");
        $stmt->execute(['did' => $doctorId, 'cid' => $chamberId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'doctor_name'  => $row['doctor_name'] ?? 'ডাক্তার',
            'chamber_name' => $row['chamber_name'] ?? 'চেম্বার'
        ];
    }
}

