<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DoctorRepository;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use App\Helpers\Response;
use App\Helpers\Database;
use App\Validators\Validator;
use PDO;

class DoctorController extends BaseController {
    private DoctorRepository $docRepo;

    public function __construct() {
        $this->docRepo = new DoctorRepository();
    }

    public function search(): void {
        $filters = $this->getQueryParams();
        $doctors = $this->docRepo->search($filters);
        Response::success($doctors, 'Doctors retrieved successfully.');
    }

    public function profile(array $params): void {
        $id = (int)($params['id'] ?? 0);
        $doctor = $this->docRepo->findById($id);
        if (!$doctor) {
            Response::error('DOCTOR_NOT_FOUND', 'Doctor profile not found.', 404);
        }
        Response::success($doctor);
    }

    public function chambers(array $params): void {
        $id = (int)($params['id'] ?? 0);
        $chambers = $this->docRepo->getChambersByDoctor($id);
        Response::success($chambers);
    }

    public function availability(array $params): void {
        $id = (int)($params['id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');
        $chamberId = (int)($_GET['chamber_id'] ?? 0);

        if (!$chamberId) {
            Response::error('VALIDATION_ERROR', 'chamber_id parameter is required.', 422);
        }

        $avail = $this->docRepo->getTodayAvailability($id, $chamberId, $date);
        Response::success($avail ?: [
            'status' => 'OFF',
            'remaining_slots' => 0,
            'message' => 'No active session scheduled for this date.'
        ]);
    }

    public function dashboard(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $today = date('Y-m-d');

        $pdo = Database::getConnection();

        // 1. Fetch chambers
        $chambers = $this->docRepo->getChambersByDoctor($doctorId);

        // 2. Aggregate today's stats for selected or default chamber
        $selectedCid = !empty($_GET['chamber_id']) ? (int)$_GET['chamber_id'] : null;
        $todayChamber = null;
        if ($selectedCid) {
            foreach ($chambers as $c) {
                if ((int)$c['id'] === $selectedCid) {
                    $todayChamber = $c;
                    break;
                }
            }
        }
        if (!$todayChamber) {
            $todayChamber = $chambers[0] ?? null;
        }

        $stats = [
            'today_date'        => $today,
            'chambers_count'    => count($chambers),
            'active_chamber_id' => $todayChamber ? (int)$todayChamber['id'] : null,
            'current_token'     => null,
            'waiting_count'     => 0,
            'completed_count'   => 0,
            'total_booked'      => 0,
            'capacity'          => 20,
            'remaining'         => 20,
            'availability'      => 'ON',
            'live_status'       => 'AVAILABLE',
            'status_note'       => null,
            'delay_minutes'     => 0
        ];

        if ($todayChamber) {
            $cid = (int)$todayChamber['id'];
            $avail = $this->docRepo->getTodayAvailability($doctorId, $cid, $today);
            if ($avail) {
                $stats['availability'] = $avail['status'];
                $stats['live_status']  = $avail['doctor_live_status'];
                $stats['delay_minutes']= (int)$avail['delay_minutes'];
                $stats['capacity']     = (int)$avail['total_capacity'];
                $stats['total_booked'] = (int)$avail['booked_count'];
                $stats['remaining']    = (int)$avail['remaining_slots'];
                $stats['status_note']  = $avail['status_note'] ?? null;
            }

            // Waiting patients count
            $waitStmt = $pdo->prepare("
                SELECT COUNT(*) FROM appointments 
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt AND status IN ('BOOKED', 'CHECKED_IN', 'WAITING')
            ");
            $waitStmt->execute(['did' => $doctorId, 'cid' => $cid, 'dt' => $today]);
            $stats['waiting_count'] = (int)$waitStmt->fetchColumn();

            // Completed count
            $compStmt = $pdo->prepare("
                SELECT COUNT(*) FROM appointments 
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt AND status = 'COMPLETED'
            ");
            $compStmt->execute(['did' => $doctorId, 'cid' => $cid, 'dt' => $today]);
            $stats['completed_count'] = (int)$compStmt->fetchColumn();

            // Active/Current token being consulted
            $currStmt = $pdo->prepare("
                SELECT token_display FROM appointments 
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt AND status IN ('CALLED', 'CONSULTING')
                ORDER BY id DESC LIMIT 1
            ");
            $currStmt->execute(['did' => $doctorId, 'cid' => $cid, 'dt' => $today]);
            $stats['current_token'] = $currStmt->fetchColumn() ?: null;
        }

        Response::success([
            'doctor'   => $user,
            'chambers' => $chambers,
            'stats'    => $stats
        ]);
    }

    public function toggleAvailability(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['chamber_id', 'status'])
            ->in('status', ['ON', 'PAUSED', 'OFF', 'CLOSED'])
            ->validateOrAbort();

        $doctorId = (int)$user['doctor_id'];
        $chamberId = (int)$body['chamber_id'];
        $date = $body['date'] ?? date('Y-m-d');
        $status = $body['status'];
        $reason = $body['reason'] ?? ($body['status_note'] ?? null);
        $liveStatus = $body['live_status'] ?? null;
        $delayMinutes = (int)($body['delay_minutes'] ?? 0);

        // Sanitize liveStatus to valid ENUM values: 'AVAILABLE','CONSULTING','SHORT_BREAK','DELAYED','EMERGENCY_DELAY','FINISHED'
        $validLiveStatuses = ['AVAILABLE', 'CONSULTING', 'SHORT_BREAK', 'DELAYED', 'EMERGENCY_DELAY', 'FINISHED'];
        if (!empty($liveStatus) && !in_array($liveStatus, $validLiveStatuses, true)) {
            if ($liveStatus === 'OFF' || $liveStatus === 'CLOSED') {
                $liveStatus = 'FINISHED';
            } elseif ($liveStatus === 'PAUSED' || $liveStatus === 'BREAK') {
                $liveStatus = 'SHORT_BREAK';
            } else {
                $liveStatus = null;
            }
        }

        $pdo = Database::getConnection();
        // Ensure capacity record exists
        $pdo->prepare("
            INSERT IGNORE INTO doctor_capacity (doctor_id, chamber_id, appointment_date, total_capacity, booked_count, version)
            VALUES (:did, :cid, :dt, 20, 0, 1)
        ")->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);

        $this->docRepo->updateAvailabilityStatus($doctorId, $chamberId, $date, $status, $reason, $liveStatus, $delayMinutes);

        // Push notifications to waiting patients if doctor is on break or delayed
        if (in_array($liveStatus, ['SHORT_BREAK', 'DELAYED', 'EMERGENCY_DELAY'], true)) {
            try {
                $dInfoStmt = $pdo->prepare("
                    SELECT u.full_name, c.name AS chamber_name 
                    FROM doctors d 
                    JOIN users u ON d.user_id = u.id 
                    JOIN doctor_chambers c ON c.id = :cid 
                    WHERE d.id = :did LIMIT 1
                ");
                $dInfoStmt->execute(['did' => $doctorId, 'cid' => $chamberId]);
                $dInfo = $dInfoStmt->fetch(PDO::FETCH_ASSOC);
                $docName = $dInfo['full_name'] ?? 'ডাক্তার';
                $chamberName = $dInfo['chamber_name'] ?? 'চেম্বার';

                $pStmt = $pdo->prepare("
                    SELECT DISTINCT p.user_id, a.id AS appointment_id, a.token_display
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
                      AND a.status IN ('BOOKED', 'CHECKED_IN', 'WAITING', 'CALLED')
                ");
                $pStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
                $waitingPatients = $pStmt->fetchAll(PDO::FETCH_ASSOC);

                $title = ($liveStatus === 'SHORT_BREAK') ? "☕ ডা. {$docName} সাময়িক বিরতিতে আছেন" : "⚠️ ডা. {$docName}-এর চেম্বার বিলম্বের নোটিশ";
                $bodyMsg = ($liveStatus === 'SHORT_BREAK')
                    ? "ডা. {$docName} ({$chamberName}) সাময়িক বিরতিতে আছেন। কিছুক্ষণের মধ্যেই সিরিয়াল ডাকা শুরু হবে।"
                    : ($reason ?: "জরুরি কারণে ডা. {$docName}-এর চেম্বার ({$chamberName}) শুরু হতে আনুমানিক {$delayMinutes} মিনিট দেরি হবে।");

                foreach ($waitingPatients as $wp) {
                    \App\Services\NotificationService::dispatch(
                        (int)$wp['user_id'],
                        'DOCTOR_STATUS_UPDATE',
                        'STATUS_' . $wp['appointment_id'] . '_' . $liveStatus . '_' . date('Ymd_H'),
                        'ALL',
                        $title,
                        $bodyMsg,
                        [
                            'appointment_id' => (string)$wp['appointment_id'],
                            'doctor_name'    => $docName,
                            'chamber_name'   => $chamberName,
                            'live_status'    => $liveStatus,
                            'type'           => 'doctor_status_update'
                        ]
                    );
                }
            } catch (\Throwable $ne) {
                error_log("[NOTIFICATION STATUS ERROR] " . $ne->getMessage());
            }
        }

        Response::success([
            'status'      => $status,
            'reason'      => $reason,
            'live_status' => $liveStatus
        ], "Session availability status updated to {$status}.");

    }

    public function updateCapacity(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['chamber_id', 'total_capacity'])
            ->numeric('total_capacity')
            ->validateOrAbort();

        $doctorId = (int)$user['doctor_id'];
        $chamberId = (int)$body['chamber_id'];
        $date = $body['date'] ?? date('Y-m-d');
        $newCapacity = (int)$body['total_capacity'];

        $pdo = Database::getConnection();
        // Check current booked count
        $stmt = $pdo->prepare("
            SELECT booked_count FROM doctor_capacity
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
        ");
        $stmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $booked = (int)($stmt->fetchColumn() ?: 0);

        if ($newCapacity < $booked) {
            Response::error('CAPACITY_REDUCTION_INVALID', "Cannot reduce capacity below existing {$booked} confirmed bookings.", 422);
        }

        $upStmt = $pdo->prepare("
            INSERT INTO doctor_capacity (doctor_id, chamber_id, appointment_date, total_capacity, booked_count, version)
            VALUES (:did, :cid, :dt, :cap, :bk, 1)
            ON DUPLICATE KEY UPDATE total_capacity = :cap, version = version + 1
        ");
        $upStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'cap' => $newCapacity, 'bk' => $booked]);

        Response::success(['total_capacity' => $newCapacity, 'booked_count' => $booked], 'Capacity updated successfully.');
    }

    public function myChambers(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $chambers = $this->docRepo->getChambersByDoctor((int)$user['doctor_id']);
        Response::success($chambers);
    }

    public function createChamber(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['name', 'address', 'contact_phone'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $cityId = (int)($body['city_id'] ?? 1);
        $areaId = (int)($body['area_id'] ?? 1);

        $cCheck = $pdo->prepare("SELECT id FROM cities WHERE id = :cid");
        $cCheck->execute(['cid' => $cityId]);
        if (!$cCheck->fetchColumn()) {
            $cityId = 1;
        }

        $aCheck = $pdo->prepare("SELECT id FROM areas WHERE id = :aid AND city_id = :cid");
        $aCheck->execute(['aid' => $areaId, 'cid' => $cityId]);
        if (!$aCheck->fetchColumn()) {
            $fallbackArea = $pdo->prepare("SELECT id FROM areas WHERE city_id = :cid LIMIT 1");
            $fallbackArea->execute(['cid' => $cityId]);
            $areaId = (int)($fallbackArea->fetchColumn() ?: 1);
        }

        $stmt = $pdo->prepare("
            INSERT INTO doctor_chambers (doctor_id, name, address, city_id, area_id, contact_phone, room_number, token_prefix, is_active, created_at)
            VALUES (:did, :name, :addr, :city, :area, :phone, :room, :pref, 1, NOW())
        ");
        $stmt->execute([
            'did'   => (int)$user['doctor_id'],
            'name'  => $body['name'],
            'addr'  => $body['address'],
            'city'  => $cityId,
            'area'  => $areaId,
            'phone' => $body['contact_phone'],
            'room'  => $body['room_number'] ?? null,
            'pref'  => $body['token_prefix'] ?? 'A'
        ]);

        Response::success(['chamber_id' => (int)$pdo->lastInsertId()], 'Chamber created successfully.', 201);
    }

    public function updateChamber(array $params): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $chamberId = (int)($params['id'] ?? 0);
        $doctorId = (int)$user['doctor_id'];
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['name', 'address', 'contact_phone'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $chk = $pdo->prepare("SELECT id FROM doctor_chambers WHERE id = :cid AND doctor_id = :did");
        $chk->execute(['cid' => $chamberId, 'did' => $doctorId]);
        if (!$chk->fetch()) {
            Response::error('CHAMBER_NOT_FOUND', 'Chamber not found or access denied.', 404);
        }

        $cityId = (int)($body['city_id'] ?? 1);
        $areaId = (int)($body['area_id'] ?? 1);

        $cCheck = $pdo->prepare("SELECT id FROM cities WHERE id = :cid");
        $cCheck->execute(['cid' => $cityId]);
        if (!$cCheck->fetchColumn()) {
            $cityId = 1;
        }

        $aCheck = $pdo->prepare("SELECT id FROM areas WHERE id = :aid AND city_id = :cid");
        $aCheck->execute(['aid' => $areaId, 'cid' => $cityId]);
        if (!$aCheck->fetchColumn()) {
            $fallbackArea = $pdo->prepare("SELECT id FROM areas WHERE city_id = :cid LIMIT 1");
            $fallbackArea->execute(['cid' => $cityId]);
            $areaId = (int)($fallbackArea->fetchColumn() ?: 1);
        }

        $stmt = $pdo->prepare("
            UPDATE doctor_chambers 
            SET name = :name, address = :addr, city_id = :city, area_id = :area,
                contact_phone = :phone, room_number = :room, token_prefix = :pref,
                is_active = :act, updated_at = NOW()
            WHERE id = :cid AND doctor_id = :did
        ");
        $stmt->execute([
            'name'  => $body['name'],
            'addr'  => $body['address'],
            'city'  => $cityId,
            'area'  => $areaId,
            'phone' => $body['contact_phone'],
            'room'  => $body['room_number'] ?? null,
            'pref'  => $body['token_prefix'] ?? 'A',
            'act'   => isset($body['is_active']) ? (int)$body['is_active'] : 1,
            'cid'   => $chamberId,
            'did'   => $doctorId
        ]);

        Response::success(null, 'Chamber updated successfully.');
    }

    public function deleteChamber(array $params): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $chamberId = (int)($params['id'] ?? 0);
        $doctorId = (int)$user['doctor_id'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE doctor_chambers SET is_active = 0, updated_at = NOW() WHERE id = :cid AND doctor_id = :did");
        $stmt->execute(['cid' => $chamberId, 'did' => $doctorId]);

        Response::success(null, 'Chamber deactivated successfully.');
    }

    public function mySchedules(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, c.name AS chamber_name 
            FROM doctor_schedules s
            JOIN doctor_chambers c ON s.chamber_id = c.id
            WHERE s.doctor_id = :did
            ORDER BY s.chamber_id, s.day_of_week ASC
        ");
        $stmt->execute(['did' => (int)$user['doctor_id']]);
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function saveSchedule(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['chamber_id', 'day_of_week', 'start_time', 'end_time', 'default_capacity'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO doctor_schedules (doctor_id, chamber_id, day_of_week, start_time, end_time, slot_duration_minutes, default_capacity, is_active)
            VALUES (:did, :cid, :day, :st, :et, :dur, :cap, 1)
            ON DUPLICATE KEY UPDATE start_time = :st, end_time = :et, slot_duration_minutes = :dur, default_capacity = :cap, is_active = 1
        ");
        $stmt->execute([
            'did' => (int)$user['doctor_id'],
            'cid' => (int)$body['chamber_id'],
            'day' => (int)$body['day_of_week'],
            'st'  => $body['start_time'],
            'et'  => $body['end_time'],
            'dur' => (int)($body['slot_duration_minutes'] ?? 10),
            'cap' => (int)$body['default_capacity']
        ]);

        Response::success(null, 'Weekly chamber schedule saved successfully.');
    }

    public function deleteSchedule(array $params): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $scheduleId = (int)($params['id'] ?? 0);
        $doctorId = (int)$user['doctor_id'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE id = :sid AND doctor_id = :did");
        $stmt->execute(['sid' => $scheduleId, 'did' => $doctorId]);

        Response::success(null, 'Weekly schedule deleted successfully.');
    }

    public function updateSchedule(array $params): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $scheduleId = (int)($params['id'] ?? 0);
        $doctorId = (int)$user['doctor_id'];
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['chamber_id', 'day_of_week', 'start_time', 'end_time', 'default_capacity'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $chk = $pdo->prepare("SELECT id FROM doctor_schedules WHERE id = :sid AND doctor_id = :did");
        $chk->execute(['sid' => $scheduleId, 'did' => $doctorId]);
        if (!$chk->fetch()) {
            Response::error('SCHEDULE_NOT_FOUND', 'Schedule not found or access denied.', 404);
        }

        $stmt = $pdo->prepare("
            UPDATE doctor_schedules
            SET chamber_id = :cid, day_of_week = :day, start_time = :st, end_time = :et,
                slot_duration_minutes = :dur, default_capacity = :cap, is_active = :act
            WHERE id = :sid AND doctor_id = :did
        ");
        $stmt->execute([
            'cid' => (int)$body['chamber_id'],
            'day' => (int)$body['day_of_week'],
            'st'  => $body['start_time'],
            'et'  => $body['end_time'],
            'dur' => (int)($body['slot_duration_minutes'] ?? 10),
            'cap' => (int)$body['default_capacity'],
            'act' => isset($body['is_active']) ? (int)$body['is_active'] : 1,
            'sid' => $scheduleId,
            'did' => $doctorId
        ]);

        Response::success(null, 'Weekly schedule updated successfully.');
    }

    public function swapQueue(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $body = $this->getJsonBody();

        Validator::make($body)->required(['appointment_id_1', 'appointment_id_2'])->validateOrAbort();

        (new QueueService())->swapQueuePositions((int)$body['appointment_id_1'], (int)$body['appointment_id_2'], $doctorId, (int)$user['id']);
        Response::success(null, 'Queue order swapped successfully.');
    }

    public function recallSkipped(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $body = $this->getJsonBody();

        Validator::make($body)->required(['appointment_id'])->validateOrAbort();

        $apt = (new QueueService())->recallSkippedPatient((int)$body['appointment_id'], $doctorId, (int)$user['id']);
        Response::success($apt, 'Skipped patient recalled successfully.');
    }

    public function updatePresence(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $body = $this->getJsonBody();

        Validator::make($body)->required(['chamber_id', 'date', 'presence_status'])->validateOrAbort();

        (new QueueService())->updateDoctorPresence(
            $doctorId,
            (int)$body['chamber_id'],
            $body['date'],
            $body['presence_status'],
            $body['note'] ?? null,
            (int)$user['id']
        );
        Response::success(null, 'Presence status updated successfully.');
    }

    public function toggleBooking(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $body = $this->getJsonBody();

        Validator::make($body)->required(['chamber_id', 'date', 'is_open'])->validateOrAbort();

        (new QueueService())->toggleChamberBooking(
            $doctorId,
            (int)$body['chamber_id'],
            $body['date'],
            (bool)$body['is_open'],
            (int)$user['id']
        );
        Response::success(null, 'Booking status updated successfully.');
    }

    public function getProfile(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT d.*, u.full_name, u.email, u.phone, u.profile_photo, u.status AS user_status
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $doctorId]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doctor) {
            Response::error('DOCTOR_NOT_FOUND', 'Doctor profile not found.', 404);
        }

        // Get specialties
        $specStmt = $pdo->prepare("SELECT specialty_id FROM doctor_specialties WHERE doctor_id = :id");
        $specStmt->execute(['id' => $doctorId]);
        $doctor['specialty_ids'] = $specStmt->fetchAll(PDO::FETCH_COLUMN);

        // Get all specialties
        $allSpecs = $pdo->query("SELECT id, name FROM specialties ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $doctor['all_specialties'] = $allSpecs;

        Response::success($doctor);
    }

    public function updateProfile(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $userId = (int)$user['id'];
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['full_name', 'phone', 'designation', 'consultation_fee'])
            ->numeric('consultation_fee')
            ->validateOrAbort();

        $pdo = Database::getConnection();

        // Update User info
        $photoSql = "";
        $uParams = [
            'name'  => $body['full_name'],
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'],
            'uid'   => $userId
        ];
        if (!empty($body['profile_photo'])) {
            $photoSql = ", profile_photo = :photo";
            $uParams['photo'] = $body['profile_photo'];
        }

        $uStmt = $pdo->prepare("
            UPDATE users 
            SET full_name = :name, email = :email, phone = :phone {$photoSql}, updated_at = NOW()
            WHERE id = :uid
        ");
        $uStmt->execute($uParams);

        // Update Doctor info
        $dStmt = $pdo->prepare("
            UPDATE doctors
            SET designation = :desig, experience_years = :exp, bio = :bio,
                consultation_fee = :cfee, follow_up_fee = :ffee, updated_at = NOW()
            WHERE id = :did
        ");
        $dStmt->execute([
            'desig' => $body['designation'],
            'exp'   => (int)($body['experience_years'] ?? 0),
            'bio'   => $body['bio'] ?? null,
            'cfee'  => (float)$body['consultation_fee'],
            'ffee'  => (float)($body['follow_up_fee'] ?? ($body['consultation_fee'] * 0.6)),
            'did'   => $doctorId
        ]);

        // Update Specialties if provided
        if (isset($body['specialty_ids']) && is_array($body['specialty_ids'])) {
            $pdo->prepare("DELETE FROM doctor_specialties WHERE doctor_id = :did")->execute(['did' => $doctorId]);
            $insSpec = $pdo->prepare("INSERT INTO doctor_specialties (doctor_id, specialty_id) VALUES (:did, :sid)");
            foreach ($body['specialty_ids'] as $sid) {
                $insSpec->execute(['did' => $doctorId, 'sid' => (int)$sid]);
            }
        }

        AuditService::log($userId, 'DOCTOR', 'PROFILE_UPDATED', 'doctors', $doctorId, null, $body);
        Response::success(null, 'Profile updated successfully.');
    }
}
