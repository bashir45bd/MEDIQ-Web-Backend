<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use PDO;

class WaitTimeService {
    public static function calculateForAppointment(int $appointmentId): array {
        $pdo = Database::getConnection();

        // 1. Fetch appointment details
        $stmt = $pdo->prepare("
            SELECT a.id, a.doctor_id, a.chamber_id, a.appointment_date, a.token_number, a.token_display,
                   a.status, c.token_prefix, c.name AS chamber_name, c.address AS chamber_address,
                   u.full_name AS doctor_name, s.slot_duration_minutes, s.start_time AS chamber_start_time
            FROM appointments a
            JOIN doctor_chambers c ON a.chamber_id = c.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            LEFT JOIN doctor_schedules s ON a.doctor_id = s.doctor_id AND a.chamber_id = s.chamber_id 
                 AND s.day_of_week = DAYOFWEEK(a.appointment_date) - 1
            WHERE a.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $appointmentId]);
        $apt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$apt) {
            return [
                'estimated_wait' => 'Unavailable',
                'recommended_arrival' => 'Unavailable',
                'patients_ahead' => 0
            ];
        }

        $doctorId  = (int)$apt['doctor_id'];
        $chamberId = (int)$apt['chamber_id'];
        $date      = $apt['appointment_date'];
        $myToken   = (int)$apt['token_number'];

        // 2. Fetch doctor live availability & broadcast delay
        $avStmt = $pdo->prepare("
            SELECT doctor_live_status, delay_minutes, status_note, presence_status, is_booking_open
            FROM doctor_daily_availability
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
            LIMIT 1
        ");
        $avStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $avail = $avStmt->fetch(PDO::FETCH_ASSOC);
        $doctorDelay = $avail ? (int)$avail['delay_minutes'] : 0;
        $doctorStatus = $avail ? $avail['doctor_live_status'] : 'AVAILABLE';

        // 3. Accurate count of active patients ahead in queue
        $aheadStmt = $pdo->prepare("
            SELECT COUNT(*) FROM appointments
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
              AND token_number < :myToken
              AND status IN ('CONSULTING', 'CALLED', 'WAITING', 'CHECKED_IN', 'BOOKED')
        ");
        $aheadStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'myToken' => $myToken]);
        $patientsAhead = (int)$aheadStmt->fetchColumn();

        // Count completed today
        $compStmt = $pdo->prepare("
            SELECT COUNT(*) FROM appointments
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt AND status = 'COMPLETED'
        ");
        $compStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $completedCount = (int)$compStmt->fetchColumn();

        // 4. Determine ONGOING patient (Inside room: CONSULTING or CALLED)
        $ongoingStmt = $pdo->prepare("
            SELECT a.id, a.token_display, a.token_number, a.status, a.consultation_start_time,
                   COALESCE(fm.full_name, u.full_name) AS patient_name,
                   CASE WHEN a.id = :aid THEN 1 ELSE 0 END AS is_you
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN patient_family_members fm ON a.family_member_id = fm.id
            WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt 
              AND a.status IN ('CONSULTING', 'CALLED')
            ORDER BY FIELD(a.status, 'CONSULTING', 'CALLED'), a.token_number ASC
            LIMIT 1
        ");
        $ongoingStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'aid' => $appointmentId]);
        $ongoingRow = $ongoingStmt->fetch(PDO::FETCH_ASSOC);

        $ongoingPatient = null;
        $currentToken = 'None (Waiting to start)';
        $elapsedConsulting = 0;

        if ($ongoingRow) {
            $currentToken = $ongoingRow['token_display'];
            if (!empty($ongoingRow['consultation_start_time'])) {
                $elapsedConsulting = max(0, (int)((time() - strtotime($ongoingRow['consultation_start_time'])) / 60));
            }
            $ongoingPatient = [
                'id'               => (int)$ongoingRow['id'],
                'token'            => $ongoingRow['token_display'],
                'name'             => self::maskPatientName($ongoingRow['patient_name'], (bool)$ongoingRow['is_you']),
                'status'           => $ongoingRow['status'],
                'status_label'     => ($ongoingRow['status'] === 'CONSULTING') ? 'ডাক্তারের রুমে ভিজিট চলছে (Consulting)' : 'রুমে প্রবেশের জন্য ডাকা হয়েছে (Called In)',
                'is_you'           => (bool)$ongoingRow['is_you'],
                'duration_minutes' => $elapsedConsulting,
            ];
        }

        // 5. Determine NEXT patient in queue
        $excludeOngoingId = $ongoingRow ? (int)$ongoingRow['id'] : 0;
        $nextStmt = $pdo->prepare("
            SELECT a.id, a.token_display, a.token_number, a.status,
                   COALESCE(fm.full_name, u.full_name) AS patient_name,
                   CASE WHEN a.id = :aid THEN 1 ELSE 0 END AS is_you
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN patient_family_members fm ON a.family_member_id = fm.id
            WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
              AND a.id != :excludeId
              AND a.status IN ('CALLED', 'CHECKED_IN', 'WAITING', 'BOOKED')
            ORDER BY FIELD(a.status, 'CALLED', 'CHECKED_IN', 'WAITING', 'BOOKED'), a.token_number ASC
            LIMIT 1
        ");
        $nextStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'excludeId' => $excludeOngoingId, 'aid' => $appointmentId]);
        $nextRow = $nextStmt->fetch(PDO::FETCH_ASSOC);

        $nextPatient = null;
        if ($nextRow) {
            $nextPatient = [
                'id'           => (int)$nextRow['id'],
                'token'        => $nextRow['token_display'],
                'name'         => self::maskPatientName($nextRow['patient_name'], (bool)$nextRow['is_you']),
                'status'       => $nextRow['status'],
                'status_label' => ($nextRow['status'] === 'CALLED') ? 'প্রস্তুত (Called)' : 'পরবর্তী সিরিয়াল (Next in line)',
                'is_you'       => (bool)$nextRow['is_you'],
            ];
        }

        // 6. Rolling average consultation duration (realistic clamp between 3 and 30 mins)
        $avgDuration = max(5, min(25, (int)($apt['slot_duration_minutes'] ?: 10)));
        $histStmt = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, consultation_start_time, consultation_end_time)) AS avg_dur
            FROM appointments
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt 
              AND status = 'COMPLETED' AND consultation_start_time IS NOT NULL AND consultation_end_time IS NOT NULL
              AND TIMESTAMPDIFF(MINUTE, consultation_start_time, consultation_end_time) BETWEEN 2 AND 45
        ");
        $histStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $histAvg = $histStmt->fetchColumn();
        if ($histAvg && (float)$histAvg >= 3.0 && (float)$histAvg <= 35.0) {
            $avgDuration = (int)round((float)$histAvg);
        }

        // Clamp elapsed consulting time to average duration
        $elapsedClamped = min($elapsedConsulting, $avgDuration);

        // 7. Wait time calculation
        $baseWait = max(0, ($patientsAhead * $avgDuration) + $doctorDelay - $elapsedClamped);
        $waitMin  = max(0, (int)round($baseWait * 0.80));
        $waitMax  = (int)round($baseWait * 1.25) + 5;

        $isYouOngoing = ($ongoingRow && (int)$ongoingRow['id'] === $appointmentId);
        $isYouNext    = ($nextRow && (int)$nextRow['id'] === $appointmentId);

        if ($isYouOngoing) {
            $estimatedWait = ($apt['status'] === 'CONSULTING') ? "আপনার ভিজিট চলছে (Consulting Now)" : "রুমে প্রবেশ করুন (Please Enter)";
            $recommendedArrival = "আপনি ডাক্তারের রুমে আছেন";
        } elseif ($isYouNext) {
            $estimatedWait = "আপনি পরবর্তী রোগী (০-৫ মিনিট)";
            $recommendedArrival = "চেম্বারের দরজার সামনে প্রস্তুত থাকুন";
        } elseif ($patientsAhead === 0) {
            $estimatedWait = "আপনি পরবর্তী রোগী (০-৫ মিনিট)";
            $recommendedArrival = "এখনই চেম্বারে উপস্থিত থাকুন";
        } else {
            $estimatedWait = "{$waitMin}–{$waitMax} মিনিট";
            $arrivalTimestamp = time() + (($waitMin - 15) * 60);
            if ($arrivalTimestamp <= time()) {
                $recommendedArrival = "এখনই চেম্বারে উপস্থিত থাকুন";
            } else {
                $recommendedArrival = date('g:i A', $arrivalTimestamp);
            }
        }

        // 8. Fetch live queue roster for today
        $rosterStmt = $pdo->prepare("
            SELECT a.id, a.token_display, a.token_number, a.status,
                   COALESCE(fm.full_name, u.full_name) AS patient_name,
                   CASE WHEN a.id = :aid THEN 1 ELSE 0 END AS is_you
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN patient_family_members fm ON a.family_member_id = fm.id
            WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
              AND a.status IN ('CONSULTING', 'CALLED', 'WAITING', 'CHECKED_IN', 'BOOKED')
            ORDER BY a.token_number ASC
        ");
        $rosterStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'aid' => $appointmentId]);
        $rosterRows = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);

        $queueRoster = [];
        foreach ($rosterRows as $r) {
            $rId = (int)$r['id'];
            $isOngoing = ($ongoingRow && (int)$ongoingRow['id'] === $rId);
            $isNext = ($nextRow && (int)$nextRow['id'] === $rId);
            $isYou = (bool)$r['is_you'];

            $displayLabel = 'অপেক্ষমান (Waiting)';
            if ($isOngoing) {
                $displayLabel = 'চলমান (Ongoing)';
            } elseif ($isNext) {
                $displayLabel = 'পরবর্তী (Next)';
            } elseif ($isYou) {
                $displayLabel = 'আপনি (You)';
            }

            $queueRoster[] = [
                'id'            => $rId,
                'token_display' => $r['token_display'],
                'token_number'  => (int)$r['token_number'],
                'status'        => $r['status'],
                'patient_name'  => self::maskPatientName($r['patient_name'], $isYou),
                'is_you'        => $isYou,
                'is_ongoing'    => $isOngoing,
                'is_next'       => $isNext,
                'display_label' => $displayLabel,
            ];
        }

        $isDoctorConsulting = ($doctorStatus === 'CONSULTING' || !empty($ongoingRow));
        $isBreak = ($doctorStatus === 'SHORT_BREAK');
        $todayStr = date('Y-m-d');

        $dtTimestamp = strtotime($date);
        $daysEn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $daysBn = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];
        $dow = (int)date('w', $dtTimestamp);
        $dayNameEn = $daysEn[$dow] ?? '';
        $dayNameBn = $daysBn[$dow] ?? '';
        $formattedDate = date('d M Y', $dtTimestamp) . ", {$dayNameEn} ({$dayNameBn})";

        return [
            'appointment_id'           => (int)$apt['id'],
            'doctor_name'              => $apt['doctor_name'] ?? 'Doctor',
            'chamber_name'             => $apt['chamber_name'] ?? 'Chamber',
            'chamber_address'          => $apt['chamber_address'] ?? '',
            'chamber_start_time'       => $apt['chamber_start_time'] ?? null,
            'appointment_date'         => $date,
            'appointment_date_formatted'=> $formattedDate,
            'day_name_en'              => $dayNameEn,
            'day_name_bn'              => $dayNameBn,
            'is_today'                 => ($date === $todayStr),
            'is_future'                => ($date > $todayStr),
            'your_token'               => $apt['token_display'],
            'current_token'            => $currentToken,
            'is_doctor_consulting'     => $isDoctorConsulting,
            'is_break'                 => $isBreak,
            'patients_ahead'           => $patientsAhead,
            'completed_count'          => $completedCount,
            'doctor_delay_minutes'     => $doctorDelay,
            'doctor_status'            => $doctorStatus,
            'status_note'              => $avail ? $avail['status_note'] : null,
            'presence_status'          => $avail['presence_status'] ?? 'IN_CHAMBER',
            'is_booking_open'          => isset($avail['is_booking_open']) ? (int)$avail['is_booking_open'] : 1,
            'estimated_wait'           => $estimatedWait,
            'recommended_arrival'      => $recommendedArrival,
            'status'                   => $apt['status'],
            'ongoing_patient'          => $ongoingPatient,
            'next_patient'             => $nextPatient,
            'is_you_ongoing'           => $isYouOngoing,
            'is_you_next'              => $isYouNext,
            'queue_roster'             => $queueRoster,
            'last_updated'             => date('g:i A')
        ];
    }

    public static function maskPatientName(?string $name, bool $isYou): string {
        if ($isYou) {
            return 'You (আপনি)';
        }
        if (empty($name)) {
            return 'Patient';
        }
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.';
        }
        $len = mb_strlen($name);
        if ($len > 3) {
            return mb_substr($name, 0, 3) . '...';
        }
        return $name;
    }
}
