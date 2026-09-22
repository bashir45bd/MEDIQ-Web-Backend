<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\BookingService;
use App\Services\WaitTimeService;
use App\Middleware\RoleMiddleware;
use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Helpers\Database;
use App\Validators\Validator;
use PDO;

class AppointmentController extends BaseController {
    private BookingService $bookingService;

    public function __construct() {
        $this->bookingService = new BookingService();
    }

    public function validateBooking(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['doctor_id', 'chamber_id', 'appointment_date'])
            ->validateOrAbort();

        $doctorId  = (int)$body['doctor_id'];
        $chamberId = (int)$body['chamber_id'];
        $date      = $body['appointment_date'];
        $ptype     = $body['patient_type'] ?? 'NEW';

        $pdo = Database::getConnection();
        self::expirePastAppointments($pdo);

        // 1. Cannot book for past dates
        if ($date < date('Y-m-d')) {
            Response::error(
                'INVALID_APPOINTMENT_DATE',
                'Appointment date cannot be in the past. (অতীতের কোনো তারিখ নির্বাচন করা যাবে না।)',
                422
            );
        }

        // 2. Validate doctor has an active schedule for this chamber on this day of week
        $dayOfWeek = (int)date('w', strtotime($date));
        $schCheck = $pdo->prepare("
            SELECT id, start_time, end_time FROM doctor_schedules
            WHERE doctor_id = :did AND chamber_id = :cid AND day_of_week = :dow AND is_active = 1
            LIMIT 1
        ");
        $schCheck->execute(['did' => $doctorId, 'cid' => $chamberId, 'dow' => $dayOfWeek]);
        $activeSchedule = $schCheck->fetch(PDO::FETCH_ASSOC);

        if (!$activeSchedule) {
            $allSch = $pdo->prepare("
                SELECT day_of_week, start_time, end_time FROM doctor_schedules
                WHERE doctor_id = :did AND chamber_id = :cid AND is_active = 1
                ORDER BY day_of_week ASC
            ");
            $allSch->execute(['did' => $doctorId, 'cid' => $chamberId]);
            $availableRows = $allSch->fetchAll(PDO::FETCH_ASSOC);

            $dayNamesEn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $dayNamesBn = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];

            if (!empty($availableRows)) {
                $dayStrings = [];
                foreach ($availableRows as $r) {
                    $d = (int)$r['day_of_week'];
                    $st = date('g:i A', strtotime($r['start_time']));
                    $et = date('g:i A', strtotime($r['end_time']));
                    $dayStrings[] = "{$dayNamesEn[$d]} ({$dayNamesBn[$d]}) [{$st} - {$et}]";
                }
                $availDaysStr = implode(', ', $dayStrings);
            } else {
                $availDaysStr = 'No consultation schedule configured for this chamber yet.';
            }

            $selectedDayName = "{$dayNamesEn[$dayOfWeek]} ({$dayNamesBn[$dayOfWeek]})";
            Response::error(
                'DOCTOR_NOT_AVAILABLE_ON_DATE',
                "Doctor does not have a scheduled chamber session on {$selectedDayName}. Available chamber days: {$availDaysStr}. (ডাক্তার এই বারে এই চেম্বারে রোগী দেখেন না। চেম্বার সূচি: {$availDaysStr})",
                422
            );
        }

        $doc = $pdo->prepare("SELECT consultation_fee, follow_up_fee FROM doctors WHERE id = :did");
        $doc->execute(['did' => $doctorId]);
        $fees = $doc->fetch(PDO::FETCH_ASSOC);

        if (!$fees) {
            Response::error('DOCTOR_NOT_FOUND', 'Doctor not found.', 404);
        }

        $consultationFee = ($ptype === 'FOLLOW_UP') ? (float)$fees['follow_up_fee'] : (float)$fees['consultation_fee'];
        $platformFee = 50.00;

        $patientId = (int)$user['patient_id'];
        $familyId  = !empty($body['family_member_id']) ? (int)$body['family_member_id'] : null;

        if ($familyId !== null) {
            $dupStmt = $pdo->prepare("
                SELECT token_display, status FROM appointments
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                  AND patient_id = :pid AND family_member_id = :fid
                  AND status NOT IN ('CANCELLED', 'NO_SHOW')
                LIMIT 1
            ");
            $dupStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'pid' => $patientId, 'fid' => $familyId]);
        } else {
            $dupStmt = $pdo->prepare("
                SELECT token_display, status FROM appointments
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                  AND patient_id = :pid AND family_member_id IS NULL
                  AND status NOT IN ('CANCELLED', 'NO_SHOW')
                LIMIT 1
            ");
            $dupStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'pid' => $patientId]);
        }

        $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            Response::error(
                'DUPLICATE_BOOKING',
                "You already have an active appointment (Serial: {$existing['token_display']}) with this doctor at this chamber for {$date}. (আপনি ইতোমধ্যে এই তারিখে এই ডাক্তারের চেম্বারে সিরিয়াল নিয়েছেন।)",
                409
            );
        }

        Response::success([
            'eligible'         => true,
            'consultation_fee' => $consultationFee,
            'platform_fee'     => $platformFee,
            'total_payable'    => $platformFee,
            'currency'         => 'BDT'
        ]);
    }

    public function book(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['doctor_id', 'chamber_id', 'appointment_date'])
            ->validateOrAbort();

        $result = $this->bookingService->bookAppointment($body, $user);
        Response::success($result, 'Appointment booked successfully. Serial token issued.', 201);
    }

    public function list(): void {
        $user = AuthMiddleware::handle();
        $pdo = Database::getConnection();
        self::expirePastAppointments($pdo);

        if ($user['role'] === 'PATIENT') {
            $stmt = $pdo->prepare("
                SELECT a.*, d.designation, u.full_name AS doctor_name, c.name AS chamber_name, c.address AS chamber_address,
                       ds.start_time AS schedule_start_time, ds.slot_duration_minutes
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                JOIN doctor_chambers c ON a.chamber_id = c.id
                LEFT JOIN doctor_schedules ds ON ds.doctor_id = a.doctor_id AND ds.chamber_id = a.chamber_id AND ds.day_of_week = (DAYOFWEEK(a.appointment_date) - 1)
                WHERE a.patient_id = :pid
                ORDER BY a.appointment_date DESC, a.token_number ASC
            ");
            $stmt->execute(['pid' => (int)$user['patient_id']]);
            $apts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($apts as &$apt) {
                $startTime = $apt['schedule_start_time'] ?? '16:00:00';
                $slotDur = (int)($apt['slot_duration_minutes'] ?? 10);
                $tokenNum = (int)$apt['token_number'];
                $offset = max(0, ($tokenNum - 1) * $slotDur);
                $apt['estimated_consultation_time'] = date('h:i A', strtotime("{$apt['appointment_date']} $startTime + $offset minutes"));
                $arrOffset = max(0, $offset - 25);
                $apt['expected_arrival_time'] = date('h:i A', strtotime("{$apt['appointment_date']} $startTime + $arrOffset minutes"));
                $apt['arrival_note'] = 'ডাক্তার দেখার ২০-২৫ মিনিট আগে চেম্বারে পৌঁছান।';
            }

            Response::success($apts);
            return;
        } elseif ($user['role'] === 'DOCTOR') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $chamberId = !empty($_GET['chamber_id']) ? (int)$_GET['chamber_id'] : null;

            $sql = "
                SELECT a.*, p.gender, p.blood_group, u.full_name AS patient_name, u.phone AS patient_phone,
                       c.name AS chamber_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN users u ON p.user_id = u.id
                JOIN doctor_chambers c ON a.chamber_id = c.id
                WHERE a.doctor_id = :did AND a.appointment_date = :dt
            ";
            $params = ['did' => (int)$user['doctor_id'], 'dt' => $date];

            if ($chamberId) {
                $sql .= " AND a.chamber_id = :cid";
                $params['cid'] = $chamberId;
            }

            $sql .= " ORDER BY a.token_number ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // ADMIN
            $stmt = $pdo->query("
                SELECT a.*, udoc.full_name AS doctor_name, upat.full_name AS patient_name, c.name AS chamber_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users udoc ON d.user_id = udoc.id
                JOIN patients p ON a.patient_id = p.id
                JOIN users upat ON p.user_id = upat.id
                JOIN doctor_chambers c ON a.chamber_id = c.id
                ORDER BY a.created_at DESC LIMIT 100
            ");
        }

        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function show(array $params): void {
        $user = AuthMiddleware::handle();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        self::expirePastAppointments($pdo);
        $stmt = $pdo->prepare("
            SELECT a.*, d.designation, u.full_name AS doctor_name, c.name AS chamber_name, c.address AS chamber_address,
                   upat.full_name AS patient_name, upat.phone AS patient_phone
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            JOIN patients p ON a.patient_id = p.id
            JOIN users upat ON p.user_id = upat.id
            JOIN doctor_chambers c ON a.chamber_id = c.id
            WHERE a.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $apt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$apt) {
            Response::error('APPOINTMENT_NOT_FOUND', 'Appointment not found.', 404);
        }

        // IDOR Check
        if ($user['role'] === 'PATIENT' && (int)$apt['patient_id'] !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied to this appointment.', 403);
        }
        if ($user['role'] === 'DOCTOR' && (int)$apt['doctor_id'] !== (int)$user['doctor_id']) {
            Response::error('FORBIDDEN', 'Access denied to this appointment.', 403);
        }

        // Attach live wait time
        $apt['live_queue'] = WaitTimeService::calculateForAppointment($id);

        Response::success($apt);
    }

    public function cancel(array $params): void {
        $user = AuthMiddleware::handle();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $id]);
        $apt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$apt) {
            Response::error('APPOINTMENT_NOT_FOUND', 'Appointment not found.', 404);
        }

        if ($user['role'] === 'PATIENT' && (int)$apt['patient_id'] !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied.', 403);
        }

        if (in_array($apt['status'], ['CONSULTING', 'COMPLETED', 'CANCELLED'], true)) {
            Response::error('CANNOT_CANCEL', "Appointment in state {$apt['status']} cannot be cancelled.", 409);
        }

        $pdo->beginTransaction();
        try {
            // Cancel appointment
            $cStmt = $pdo->prepare("
                UPDATE appointments 
                SET status = 'CANCELLED', cancelled_at = NOW(), cancelled_by = :uid, cancellation_reason = 'Patient requested cancellation'
                WHERE id = :id
            ");
            $cStmt->execute(['uid' => $user['id'], 'id' => $id]);

            // Release 1 capacity slot
            $relCap = $pdo->prepare("
                UPDATE doctor_capacity
                SET booked_count = GREATEST(0, booked_count - 1), version = version + 1
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
            ");
            $relCap->execute(['did' => $apt['doctor_id'], 'cid' => $apt['chamber_id'], 'dt' => $apt['appointment_date']]);

            // If chamber was FULL, reopen to ON
            $reopen = $pdo->prepare("
                UPDATE doctor_daily_availability 
                SET status = 'ON' 
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt AND status = 'FULL'
            ");
            $reopen->execute(['did' => $apt['doctor_id'], 'cid' => $apt['chamber_id'], 'dt' => $apt['appointment_date']]);

            $pdo->commit();

            // Push notification to patient
            try {
                $patUserStmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = :pid LIMIT 1");
                $patUserStmt->execute(['pid' => $apt['patient_id']]);
                $patientUserId = (int)$patUserStmt->fetchColumn();
                if ($patientUserId > 0) {
                    \App\Services\NotificationService::dispatch(
                        $patientUserId,
                        'APPOINTMENT_CANCELLED',
                        'APT_' . $id . '_CANCELLED',
                        'ALL',
                        "অ্যাপয়েন্টমেন্ট বাতিল",
                        "আপনার সিরিয়াল {$apt['token_display']}-এর অ্যাপয়েন্টমেন্ট সফলভাবে বাতিল করা হয়েছে।",
                        [
                            'appointment_id' => (string)$id,
                            'token_display'  => $apt['token_display'],
                            'type'           => 'appointment_cancelled'
                        ]
                    );
                }
            } catch (\Throwable $ne) {
                error_log("[NOTIFICATION CANCEL ERROR] " . $ne->getMessage());
            }

            Response::success(null, 'Appointment cancelled successfully. Slot released.');

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Automatically cancel unvisited appointments from past dates
     */
    public static function expirePastAppointments(PDO $pdo): int {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'CANCELLED',
                cancelled_at = NOW(),
                cancellation_reason = 'Auto-cancelled: Appointment date expired without visit.'
            WHERE appointment_date < :today 
              AND status IN ('BOOKED', 'CHECKED_IN', 'WAITING', 'CALLED')
        ");
        $stmt->execute(['today' => $today]);
        $affected = $stmt->rowCount();

        try {
            $qStmt = $pdo->prepare("
                UPDATE appointment_queue 
                SET status = 'NO_SHOW' 
                WHERE appointment_date < :today 
                  AND status IN ('WAITING', 'CALLED')
            ");
            $qStmt->execute(['today' => $today]);
        } catch (\Throwable $e) {
            // Silently ignore if queue table structure does not match
        }

        return $affected;
    }
}
