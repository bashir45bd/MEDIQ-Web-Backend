<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use App\Helpers\Response;
use App\Helpers\Database;
use App\Validators\Validator;
use PDO;

class AdminController extends BaseController {
    public function dashboard(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $today = date('Y-m-d');

        // Doctors count
        $docStats = $pdo->query("
            SELECT 
                COUNT(*) AS total_doctors,
                SUM(CASE WHEN verification_status = 'VERIFIED' THEN 1 ELSE 0 END) AS verified_doctors,
                SUM(CASE WHEN verification_status = 'PENDING' THEN 1 ELSE 0 END) AS pending_doctors,
                SUM(CASE WHEN verification_status = 'SUSPENDED' THEN 1 ELSE 0 END) AS suspended_doctors
            FROM doctors
        ")->fetch(PDO::FETCH_ASSOC);

        // Patients count
        $totalPatients = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

        // Today's appointments
        $aptStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_today,
                SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_today,
                SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) AS no_show_today,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_today,
                SUM(total_paid) AS revenue_today
            FROM appointments
            WHERE appointment_date = :dt
        ");
        $aptStmt->execute(['dt' => $today]);
        $todayStats = $aptStmt->fetch(PDO::FETCH_ASSOC);

        // Monthly revenue
        $firstOfMonth = date('Y-m-01');
        $revStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0.00) FROM platform_transactions
            WHERE transaction_type = 'FEE_COLLECTION' AND created_at >= :fom
        ");
        $revStmt->execute(['fom' => $firstOfMonth]);
        $monthlyRevenue = (float)$revStmt->fetchColumn();

        // Active Promotions
        $activePromos = (int)$pdo->query("SELECT COUNT(*) FROM promotions WHERE status = 'ACTIVE' AND end_date >= CURRENT_DATE()")->fetchColumn();

        Response::success([
            'doctors'           => $docStats,
            'total_patients'    => $totalPatients,
            'today'             => $todayStats,
            'monthly_revenue'   => $monthlyRevenue,
            'active_promotions' => $activePromos
        ]);
    }

    public function listDoctors(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $status = $_GET['status'] ?? null;

        $sql = "
            SELECT d.*, u.full_name AS doctor_name, u.email, u.phone, u.status AS user_status,
                   GROUP_CONCAT(s.name SEPARATOR ', ') AS specialties
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            LEFT JOIN doctor_specialties ds ON d.id = ds.doctor_id
            LEFT JOIN specialties s ON ds.specialty_id = s.id
        ";
        $params = [];
        if ($status) {
            $sql .= " WHERE d.verification_status = :st";
            $params['st'] = $status;
        }
        $sql .= " GROUP BY d.id ORDER BY d.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function verifyDoctor(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE doctors 
            SET verification_status = 'VERIFIED', verified_at = NOW(), verified_by = :aid
            WHERE id = :id
        ");
        $stmt->execute(['aid' => $admin['id'], 'id' => $docId]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'DOCTOR_VERIFIED', 'doctors', $docId);
        Response::success(null, 'Doctor BMDC credentials verified and profile published.');
    }

    public function rejectDoctor(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();
        $reason = $body['reason'] ?? 'Invalid BMDC documents';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE doctors 
            SET verification_status = 'REJECTED', verification_notes = :nt
            WHERE id = :id
        ");
        $stmt->execute(['nt' => $reason, 'id' => $docId]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'DOCTOR_REJECTED', 'doctors', $docId, null, ['reason' => $reason]);
        Response::success(null, 'Doctor application rejected.');
    }

    public function suspendDoctor(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE doctors SET verification_status = 'SUSPENDED' WHERE id = :id");
        $stmt->execute(['id' => $docId]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'DOCTOR_SUSPENDED', 'doctors', $docId);
        Response::success(null, 'Doctor account suspended.');
    }

    public function overrideAvailability(): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['doctor_id', 'chamber_id', 'appointment_date', 'status', 'reason'])
            ->in('status', ['ON', 'OFF', 'PAUSED', 'FULL', 'CLOSED'])
            ->validateOrAbort();

        $did    = (int)$body['doctor_id'];
        $cid    = (int)$body['chamber_id'];
        $dt     = $body['appointment_date'];
        $status = $body['status'];
        $reason = $body['reason'];

        $pdo = Database::getConnection();
        $oldStmt = $pdo->prepare("SELECT status FROM doctor_daily_availability WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt");
        $oldStmt->execute(['did' => $did, 'cid' => $cid, 'dt' => $dt]);
        $oldStatus = $oldStmt->fetchColumn() ?: 'UNKNOWN';

        $stmt = $pdo->prepare("
            INSERT INTO doctor_daily_availability (doctor_id, chamber_id, appointment_date, status, status_note, last_status_updated_at)
            VALUES (:did, :cid, :dt, :st, :nt, NOW())
            ON DUPLICATE KEY UPDATE status = :st, status_note = :nt, last_status_updated_at = NOW()
        ");
        $stmt->execute(['did' => $did, 'cid' => $cid, 'dt' => $dt, 'st' => $status, 'nt' => "[ADMIN OVERRIDE] {$reason}"]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_AVAILABILITY_OVERRIDE', 'doctor_daily_availability', $cid, [
            'status' => $oldStatus
        ], [
            'status' => $status,
            'reason' => $reason
        ]);

        Response::success(null, "Availability overridden to {$status}. Audit log recorded.");
    }

    public function revenueReport(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT t.*, p.gateway_name, p.transaction_ref, a.appointment_number, u.full_name AS payer_name
            FROM platform_transactions t
            JOIN payments p ON t.payment_id = p.id
            JOIN appointments a ON p.appointment_id = a.id
            JOIN users u ON p.user_id = u.id
            ORDER BY t.id DESC LIMIT 100
        ");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function auditLogs(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT a.*, u.full_name AS actor_name, u.email AS actor_email
            FROM audit_logs a
            JOIN users u ON a.actor_id = u.id
            ORDER BY a.id DESC LIMIT 100
        ");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function settings(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY key_name ASC");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function updateSetting(): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['key_name', 'setting_value'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE system_settings 
            SET setting_value = :val, updated_by = :uid, updated_at = NOW()
            WHERE key_name = :key
        ");
        $stmt->execute([
            'val' => $body['setting_value'],
            'uid' => $admin['id'],
            'key' => $body['key_name']
        ]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'SETTING_UPDATED', 'system_settings', 1, null, $body);
        Response::success(null, "Setting {$body['key_name']} updated successfully.");
    }

    public function listPromotions(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM promotions ORDER BY id DESC");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createPromotion(): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['name', 'code', 'type', 'discount_value', 'start_date', 'end_date'])
            ->in('type', ['FIXED', 'PERCENT', 'FREE'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO promotions (name, code, type, discount_value, max_discount, start_date, end_date, usage_limit, per_user_limit, minimum_fee, priority, status)
            VALUES (:name, :code, :type, :val, :maxd, :sdt, :edt, :ulim, :pulim, :minf, :prio, 'ACTIVE')
        ");
        $stmt->execute([
            'name'  => $body['name'],
            'code'  => strtoupper(trim((string)$body['code'])),
            'type'  => $body['type'],
            'val'   => (float)$body['discount_value'],
            'maxd'  => !empty($body['max_discount']) ? (float)$body['max_discount'] : null,
            'sdt'   => $body['start_date'],
            'edt'   => $body['end_date'],
            'ulim'  => !empty($body['usage_limit']) ? (int)$body['usage_limit'] : null,
            'pulim' => (int)($body['per_user_limit'] ?? 1),
            'minf'  => (float)($body['minimum_fee'] ?? 0.00),
            'prio'  => (int)($body['priority'] ?? 0)
        ]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'PROMOTION_CREATED', 'promotions', (int)$pdo->lastInsertId(), null, $body);
        Response::success(null, 'Promotion campaign created successfully.', 201);
    }

    public function getDoctorDetails(array $params): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT d.*, u.full_name AS doctor_name, u.email, u.phone, u.status AS user_status
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $docId]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doctor) {
            Response::error('DOCTOR_NOT_FOUND', 'Doctor not found.', 404);
        }

        // Chambers
        $cStmt = $pdo->prepare("
            SELECT c.*, ci.name AS city_name, a.name AS area_name
            FROM doctor_chambers c
            JOIN cities ci ON c.city_id = ci.id
            JOIN areas a ON c.area_id = a.id
            WHERE c.doctor_id = :id
            ORDER BY c.id ASC
        ");
        $cStmt->execute(['id' => $docId]);
        $chambers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        // Schedules
        $sStmt = $pdo->prepare("
            SELECT s.*, c.name AS chamber_name
            FROM doctor_schedules s
            JOIN doctor_chambers c ON s.chamber_id = c.id
            WHERE s.doctor_id = :id
            ORDER BY s.chamber_id, s.day_of_week ASC
        ");
        $sStmt->execute(['id' => $docId]);
        $schedules = $sStmt->fetchAll(PDO::FETCH_ASSOC);

        // Specialties
        $spStmt = $pdo->prepare("SELECT specialty_id FROM doctor_specialties WHERE doctor_id = :id");
        $spStmt->execute(['id' => $docId]);
        $doctor['specialty_ids'] = $spStmt->fetchAll(PDO::FETCH_COLUMN);

        $allSpecs = $pdo->query("SELECT id, name FROM specialties ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $allCities = $pdo->query("SELECT id, name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $allAreas = $pdo->query("SELECT id, city_id, name FROM areas ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'doctor'          => $doctor,
            'chambers'        => $chambers,
            'schedules'       => $schedules,
            'all_specialties' => $allSpecs,
            'all_cities'      => $allCities,
            'all_areas'       => $allAreas
        ]);
    }

    public function updateDoctorProfile(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['doctor_name', 'phone', 'designation', 'consultation_fee', 'verification_status'])
            ->numeric('consultation_fee')
            ->in('verification_status', ['PENDING', 'VERIFIED', 'REJECTED', 'SUSPENDED'])
            ->validateOrAbort();

        $pdo = Database::getConnection();

        // Get doctor's user_id
        $uIdStmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = :id");
        $uIdStmt->execute(['id' => $docId]);
        $userId = (int)$uIdStmt->fetchColumn();
        if (!$userId) {
            Response::error('DOCTOR_NOT_FOUND', 'Doctor not found.', 404);
        }

        // Update User info
        $pdo->prepare("
            UPDATE users
            SET full_name = :name, email = :email, phone = :phone, updated_at = NOW()
            WHERE id = :uid
        ")->execute([
            'name'  => $body['doctor_name'],
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'],
            'uid'   => $userId
        ]);

        // Update Doctor info
        $pdo->prepare("
            UPDATE doctors
            SET bmdc_number = :bmdc, designation = :desig, experience_years = :exp, bio = :bio,
                consultation_fee = :cfee, follow_up_fee = :ffee, verification_status = :vst, updated_at = NOW()
            WHERE id = :did
        ")->execute([
            'bmdc'  => $body['bmdc_number'] ?? '',
            'desig' => $body['designation'],
            'exp'   => (int)($body['experience_years'] ?? 0),
            'bio'   => $body['bio'] ?? null,
            'cfee'  => (float)$body['consultation_fee'],
            'ffee'  => (float)($body['follow_up_fee'] ?? ($body['consultation_fee'] * 0.6)),
            'vst'   => $body['verification_status'],
            'did'   => $docId
        ]);

        // Update specialties
        if (isset($body['specialty_ids']) && is_array($body['specialty_ids'])) {
            $pdo->prepare("DELETE FROM doctor_specialties WHERE doctor_id = :did")->execute(['did' => $docId]);
            $ins = $pdo->prepare("INSERT INTO doctor_specialties (doctor_id, specialty_id) VALUES (:did, :sid)");
            foreach ($body['specialty_ids'] as $sid) {
                $ins->execute(['did' => $docId, 'sid' => (int)$sid]);
            }
        }

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_DOCTOR_UPDATED', 'doctors', $docId, null, $body);
        Response::success(null, 'Doctor profile updated by administrator.');
    }

    public function adminUpdateCapacity(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['chamber_id', 'total_capacity'])
            ->numeric('total_capacity')
            ->validateOrAbort();

        $chamberId = (int)$body['chamber_id'];
        $date = $body['date'] ?? date('Y-m-d');
        $newCapacity = (int)$body['total_capacity'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT booked_count FROM doctor_capacity
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
        ");
        $stmt->execute(['did' => $docId, 'cid' => $chamberId, 'dt' => $date]);
        $booked = (int)($stmt->fetchColumn() ?: 0);

        if ($newCapacity < $booked) {
            Response::error('CAPACITY_REDUCTION_INVALID', "Cannot reduce capacity below existing {$booked} confirmed bookings.", 422);
        }

        $upStmt = $pdo->prepare("
            INSERT INTO doctor_capacity (doctor_id, chamber_id, appointment_date, total_capacity, booked_count, version)
            VALUES (:did, :cid, :dt, :cap, :bk, 1)
            ON DUPLICATE KEY UPDATE total_capacity = :cap, version = version + 1
        ");
        $upStmt->execute(['did' => $docId, 'cid' => $chamberId, 'dt' => $date, 'cap' => $newCapacity, 'bk' => $booked]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_CAPACITY_OVERRIDE', 'doctor_capacity', $chamberId, null, [
            'doctor_id'      => $docId,
            'total_capacity' => $newCapacity,
            'date'           => $date
        ]);
        Response::success(['total_capacity' => $newCapacity, 'booked_count' => $booked], 'Capacity updated by administrator.');
    }

    public function adminBroadcastDelay(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['chamber_id', 'delay_minutes'])
            ->numeric('delay_minutes')
            ->validateOrAbort();

        $chamberId = (int)$body['chamber_id'];
        $delayMins = (int)$body['delay_minutes'];
        $today = date('Y-m-d');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO doctor_daily_availability (doctor_id, chamber_id, appointment_date, status, delay_minutes, last_status_updated_at)
            VALUES (:did, :cid, :dt, 'ON', :dm, NOW())
            ON DUPLICATE KEY UPDATE delay_minutes = :dm, last_status_updated_at = NOW()
        ");
        $stmt->execute(['did' => $docId, 'cid' => $chamberId, 'dt' => $today, 'dm' => $delayMins]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_DELAY_BROADCAST', 'doctor_daily_availability', $chamberId, null, [
            'doctor_id'     => $docId,
            'delay_minutes' => $delayMins
        ]);
        Response::success(['delay_minutes' => $delayMins], "Broadcasted {$delayMins} min delay by administrator.");
    }

    public function adminCreateChamber(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['name', 'address', 'city_id', 'area_id', 'contact_phone'])
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO doctor_chambers (doctor_id, name, address, city_id, area_id, contact_phone, room_number, token_prefix, is_active, created_at)
            VALUES (:did, :name, :addr, :city, :area, :phone, :room, :pref, 1, NOW())
        ");
        $stmt->execute([
            'did'   => $docId,
            'name'  => $body['name'],
            'addr'  => $body['address'],
            'city'  => (int)$body['city_id'],
            'area'  => (int)$body['area_id'],
            'phone' => $body['contact_phone'],
            'room'  => $body['room_number'] ?? null,
            'pref'  => $body['token_prefix'] ?? 'A'
        ]);

        $cid = (int)$pdo->lastInsertId();
        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_CHAMBER_CREATED', 'doctor_chambers', $cid, null, [
            'doctor_id' => $docId,
            'name'      => $body['name']
        ]);
        Response::success(['chamber_id' => $cid], 'Chamber created by administrator.', 201);
    }

    public function adminSaveSchedule(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $docId = (int)($params['id'] ?? 0);
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
            'did' => $docId,
            'cid' => (int)$body['chamber_id'],
            'day' => (int)$body['day_of_week'],
            'st'  => $body['start_time'],
            'et'  => $body['end_time'],
            'dur' => (int)($body['slot_duration_minutes'] ?? 10),
            'cap' => (int)$body['default_capacity']
        ]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_SCHEDULE_SAVED', 'doctor_schedules', (int)$body['chamber_id'], null, [
            'doctor_id'   => $docId,
            'day_of_week' => (int)$body['day_of_week']
        ]);
        Response::success(null, 'Weekly schedule saved by administrator.');
    }

    public function createDoctor(): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN', 'SUPER_ADMIN');
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['full_name', 'phone', 'password', 'bmdc_number', 'designation'])
            ->phone('phone')
            ->minLength('password', 6)
            ->validateOrAbort();

        $pdo = Database::getConnection();

        // Check if phone or email already exists
        $chkUser = $pdo->prepare("SELECT id FROM users WHERE phone = :p OR (email IS NOT NULL AND email = :e) LIMIT 1");
        $chkUser->execute([
            'p' => $body['phone'],
            'e' => !empty($body['email']) ? $body['email'] : '___no_email___'
        ]);
        if ($chkUser->fetch()) {
            Response::error('USER_ALREADY_EXISTS', 'A user with this phone or email already exists.', 409);
        }

        // Check BMDC number
        $chkBmdc = $pdo->prepare("SELECT id FROM doctors WHERE bmdc_number = :bmdc LIMIT 1");
        $chkBmdc->execute(['bmdc' => $body['bmdc_number']]);
        if ($chkBmdc->fetch()) {
            Response::error('BMDC_ALREADY_EXISTS', 'A doctor with this BMDC number is already registered.', 409);
        }

        $pdo->beginTransaction();
        try {
            // 1. Insert User
            $pwdHash = \App\Helpers\HashHelper::make($body['password']);
            $uStmt = $pdo->prepare("
                INSERT INTO users (role, full_name, email, phone, password_hash, status, phone_verified_at, created_at)
                VALUES ('DOCTOR', :name, :email, :phone, :pwd, 'ACTIVE', NOW(), NOW())
            ");
            $uStmt->execute([
                'name'  => $body['full_name'],
                'email' => !empty($body['email']) ? $body['email'] : null,
                'phone' => $body['phone'],
                'pwd'   => $pwdHash
            ]);
            $userId = (int)$pdo->lastInsertId();

            // 2. Insert Doctor
            $vStatus = in_array($body['verification_status'] ?? '', ['VERIFIED', 'PENDING', 'SUSPENDED'], true) 
                ? $body['verification_status'] 
                : 'VERIFIED';

            $dStmt = $pdo->prepare("
                INSERT INTO doctors (user_id, bmdc_number, designation, experience_years, consultation_fee, follow_up_fee, bio, verification_status, verified_at, verified_by, created_at)
                VALUES (:uid, :bmdc, :desig, :exp, :fee, :fufee, :bio, :vstat, " . ($vStatus === 'VERIFIED' ? 'NOW()' : 'NULL') . ", :vby, NOW())
            ");
            $dStmt->execute([
                'uid'   => $userId,
                'bmdc'  => $body['bmdc_number'],
                'desig' => $body['designation'],
                'exp'   => (int)($body['experience_years'] ?? 0),
                'fee'   => (float)($body['consultation_fee'] ?? 0.00),
                'fufee' => (float)($body['follow_up_fee'] ?? 0.00),
                'bio'   => $body['bio'] ?? null,
                'vstat' => $vStatus,
                'vby'   => $vStatus === 'VERIFIED' ? (int)$admin['id'] : null
            ]);
            $doctorId = (int)$pdo->lastInsertId();

            // 3. Specialties
            if (!empty($body['specialty_ids']) && is_array($body['specialty_ids'])) {
                $spStmt = $pdo->prepare("INSERT IGNORE INTO doctor_specialties (doctor_id, specialty_id) VALUES (:did, :sid)");
                foreach ($body['specialty_ids'] as $sid) {
                    $spStmt->execute(['did' => $doctorId, 'sid' => (int)$sid]);
                }
            } else {
                $spStmt = $pdo->prepare("INSERT IGNORE INTO doctor_specialties (doctor_id, specialty_id, is_primary) VALUES (:did, 1, 1)");
                $spStmt->execute(['did' => $doctorId]);
            }

            // 4. Default Chamber & Schedules
            $cStmt = $pdo->prepare("
                INSERT INTO doctor_chambers (doctor_id, name, address, city_id, area_id, contact_phone, room_number, token_prefix, is_active, created_at)
                VALUES (:did, :cname, :caddr, 1, 1, :phone, 'Room 101', 'D', 1, NOW())
            ");
            $cStmt->execute([
                'did'   => $doctorId,
                'cname' => 'Main Chamber',
                'caddr' => 'Dhanmondi, Dhaka',
                'phone' => $body['phone']
            ]);
            $chamberId = (int)$pdo->lastInsertId();

            $sStmt = $pdo->prepare("
                INSERT IGNORE INTO doctor_schedules (doctor_id, chamber_id, day_of_week, start_time, end_time, slot_duration_minutes, default_capacity, is_active, created_at)
                VALUES (:did, :cid, :dow, '09:00:00', '21:00:00', 10, 25, 1, NOW())
            ");
            for ($dow = 0; $dow <= 6; $dow++) {
                $sStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dow' => $dow]);
            }

            $pdo->commit();

            AuditService::log((int)$admin['id'], $admin['role'], 'ADMIN_DOCTOR_CREATED', 'doctors', $doctorId, null, [
                'doctor_name' => $body['full_name'],
                'bmdc'        => $body['bmdc_number']
            ]);

            Response::success([
                'doctor_id' => $doctorId,
                'user_id'   => $userId,
                'name'      => $body['full_name']
            ], 'Doctor account created and registered successfully.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function listAdministrators(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN', 'SUPER_ADMIN');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT id, role, full_name, email, phone, status, last_login_at, created_at
            FROM users
            WHERE role IN ('PLATFORM_ADMIN', 'SUPER_ADMIN')
            ORDER BY id ASC
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success($admins);
    }

    public function createAdministrator(): void {
        $caller = RoleMiddleware::requireRole('SUPER_ADMIN');
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['full_name', 'phone', 'password', 'role'])
            ->phone('phone')
            ->minLength('password', 6)
            ->validateOrAbort();

        $role = $body['role'];
        if (!in_array($role, ['PLATFORM_ADMIN', 'SUPER_ADMIN'], true)) {
            Response::error('INVALID_ROLE', 'Admin role must be PLATFORM_ADMIN or SUPER_ADMIN.', 422);
        }

        $pdo = Database::getConnection();

        // Check if phone or email already exists
        $chk = $pdo->prepare("SELECT id FROM users WHERE phone = :p OR (email IS NOT NULL AND email = :e) LIMIT 1");
        $chk->execute([
            'p' => $body['phone'],
            'e' => !empty($body['email']) ? $body['email'] : '___no_email___'
        ]);
        if ($chk->fetch()) {
            Response::error('USER_ALREADY_EXISTS', 'A user with this phone or email already exists.', 409);
        }

        $pwdHash = \App\Helpers\HashHelper::make($body['password']);
        $stmt = $pdo->prepare("
            INSERT INTO users (role, full_name, email, phone, password_hash, status, phone_verified_at, created_at)
            VALUES (:role, :name, :email, :phone, :pwd, 'ACTIVE', NOW(), NOW())
        ");
        $stmt->execute([
            'role'  => $role,
            'name'  => $body['full_name'],
            'email' => !empty($body['email']) ? $body['email'] : null,
            'phone' => $body['phone'],
            'pwd'   => $pwdHash
        ]);
        $newAdminId = (int)$pdo->lastInsertId();

        AuditService::log((int)$caller['id'], 'SUPER_ADMIN', 'SUPER_ADMIN_CREATED_ADMIN', 'users', $newAdminId, null, [
            'name' => $body['full_name'],
            'role' => $role
        ]);

        Response::success([
            'id'   => $newAdminId,
            'name' => $body['full_name'],
            'role' => $role
        ], 'Administrator account created successfully.');
    }

    public function updateAdministratorStatus(array $params): void {
        $caller = RoleMiddleware::requireRole('SUPER_ADMIN');
        $targetId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        Validator::make($body)->required(['status'])->validateOrAbort();
        $newStatus = $body['status'];
        if (!in_array($newStatus, ['ACTIVE', 'SUSPENDED', 'DEACTIVATED'], true)) {
            Response::error('INVALID_STATUS', 'Status must be ACTIVE, SUSPENDED, or DEACTIVATED.', 422);
        }

        if ($targetId === (int)$caller['id']) {
            Response::error('CANNOT_MODIFY_SELF', 'Super administrators cannot change their own account status.', 400);
        }

        $pdo = Database::getConnection();
        $chk = $pdo->prepare("SELECT role, status FROM users WHERE id = :id AND role IN ('PLATFORM_ADMIN', 'SUPER_ADMIN') LIMIT 1");
        $chk->execute(['id' => $targetId]);
        $user = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            Response::error('NOT_FOUND', 'Administrator not found.', 404);
        }

        $stmt = $pdo->prepare("UPDATE users SET status = :st WHERE id = :id");
        $stmt->execute(['st' => $newStatus, 'id' => $targetId]);

        AuditService::log((int)$caller['id'], 'SUPER_ADMIN', 'ADMIN_STATUS_CHANGED', 'users', $targetId, ['status' => $user['status']], ['status' => $newStatus]);
        Response::success(null, "Administrator status changed to {$newStatus}.");
    }

    public function listPatients(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $filters = $this->getQueryParams();

        $sql = "
            SELECT 
                p.id, p.user_id, p.dob, p.gender, p.blood_group, p.address,
                p.emergency_contact_name, p.emergency_contact_phone,
                u.full_name, u.phone, u.email, u.status, u.profile_photo, u.created_at,
                (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.id) AS total_appointments,
                (SELECT MAX(a.appointment_date) FROM appointments a WHERE a.patient_id = p.id) AS last_visit_date
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE 1=1
        ";

        $params = [];
        if (!empty($filters['query'])) {
            $sql .= " AND (u.full_name LIKE :q OR u.phone LIKE :q OR u.email LIKE :q)";
            $params['q'] = '%' . $filters['query'] . '%';
        }
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['gender'])) {
            $sql .= " AND p.gender = :gender";
            $params['gender'] = $filters['gender'];
        }
        if (!empty($filters['blood_group'])) {
            $sql .= " AND p.blood_group = :bg";
            $params['bg'] = $filters['blood_group'];
        }

        $sql .= " ORDER BY p.id DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success($patients, 'Patients retrieved successfully.');
    }

    public function getPatientDetails(array $params): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $patientId = (int)($params['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                p.*, p.dob AS date_of_birth, u.full_name, u.phone, u.email, u.status, u.status AS user_status, u.profile_photo, u.created_at AS user_created_at
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :pid LIMIT 1
        ");
        $stmt->execute(['pid' => $patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            Response::error('PATIENT_NOT_FOUND', 'Patient record not found.', 404);
        }

        // Family members
        $famStmt = $pdo->prepare("SELECT * FROM patient_family_members WHERE patient_id = :pid ORDER BY id ASC");
        $famStmt->execute(['pid' => $patientId]);
        $patient['family_members'] = $famStmt->fetchAll(PDO::FETCH_ASSOC);

        // Appointment History
        $aptStmt = $pdo->prepare("
            SELECT 
                a.id, a.appointment_number, a.token_number, a.token_display, a.appointment_date, a.patient_type, a.status,
                a.payment_status, a.consultation_fee, a.total_paid, a.created_at,
                du.full_name AS doctor_name, d.designation,
                c.name AS chamber_name, c.address AS chamber_address, c.token_prefix
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users du ON d.user_id = du.id
            JOIN doctor_chambers c ON a.chamber_id = c.id
            WHERE a.patient_id = :pid
            ORDER BY a.appointment_date DESC, a.token_number DESC
            LIMIT 50
        ");
        $aptStmt->execute(['pid' => $patientId]);
        $patient['appointments'] = $aptStmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success($patient);
    }

    public function updatePatientStatus(array $params): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $patientId = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        Validator::make($body)->required(['status'])->in('status', ['ACTIVE', 'SUSPENDED', 'DEACTIVATED'])->validateOrAbort();

        $pdo = Database::getConnection();
        $uStmt = $pdo->prepare("
            UPDATE users u
            JOIN patients p ON p.user_id = u.id
            SET u.status = :st, u.updated_at = NOW()
            WHERE p.id = :pid
        ");
        $uStmt->execute(['st' => $body['status'], 'pid' => $patientId]);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'PATIENT_STATUS_UPDATED', 'patients', $patientId, null, $body);
        Response::success(null, "Patient status updated to {$body['status']}.");
    }

    public function quickRegisterPatient(): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['full_name', 'phone'])
            ->minLength('full_name', 2)
            ->validateOrAbort();

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Check if user already exists
            $chk = $pdo->prepare("SELECT id FROM users WHERE phone = :ph LIMIT 1");
            $chk->execute(['ph' => $body['phone']]);
            $existingUser = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                $userId = (int)$existingUser['id'];
                // Ensure patient profile exists
                $pChk = $pdo->prepare("SELECT id FROM patients WHERE user_id = :uid LIMIT 1");
                $pChk->execute(['uid' => $userId]);
                $patId = $pChk->fetchColumn();

                if (!$patId) {
                    $insP = $pdo->prepare("
                        INSERT INTO patients (user_id, dob, gender, blood_group, address, created_at)
                        VALUES (:uid, :dob, :gender, :bg, :addr, NOW())
                    ");
                    $insP->execute([
                        'uid'    => $userId,
                        'dob'    => $body['dob'] ?? null,
                        'gender' => $body['gender'] ?? 'OTHER',
                        'bg'     => $body['blood_group'] ?? null,
                        'addr'   => $body['address'] ?? null
                    ]);
                    $patId = (int)$pdo->lastInsertId();
                }
            } else {
                // Create user
                $defaultPassHash = \App\Helpers\HashHelper::hash('Patient@123');
                $insU = $pdo->prepare("
                    INSERT INTO users (role, full_name, email, phone, password_hash, status, phone_verified_at, created_at)
                    VALUES ('PATIENT', :name, :email, :phone, :hash, 'ACTIVE', NOW(), NOW())
                ");
                $insU->execute([
                    'name'  => $body['full_name'],
                    'email' => $body['email'] ?? null,
                    'phone' => $body['phone'],
                    'hash'  => $defaultPassHash
                ]);
                $userId = (int)$pdo->lastInsertId();

                $insP = $pdo->prepare("
                    INSERT INTO patients (user_id, dob, gender, blood_group, address, created_at)
                    VALUES (:uid, :dob, :gender, :bg, :addr, NOW())
                ");
                $insP->execute([
                    'uid'    => $userId,
                    'dob'    => $body['dob'] ?? null,
                    'gender' => $body['gender'] ?? 'OTHER',
                    'bg'     => $body['blood_group'] ?? null,
                    'addr'   => $body['address'] ?? null
                ]);
                $patId = (int)$pdo->lastInsertId();
            }

            $pdo->commit();
            Response::success([
                'patient_id' => $patId,
                'user_id'    => $userId,
                'full_name'  => $body['full_name'],
                'phone'      => $body['phone']
            ], 'Patient registered successfully.', 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function adminBookForPatient(): void {
        $admin = RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();

        Validator::make($body)
            ->required(['doctor_id', 'chamber_id', 'appointment_date'])
            ->validateOrAbort();

        $pdo = Database::getConnection();

        // 1. Identify or retrieve Patient
        $patientId = !empty($body['patient_id']) ? (int)$body['patient_id'] : null;
        if (!$patientId && !empty($body['phone'])) {
            $uStmt = $pdo->prepare("
                SELECT p.id, p.user_id FROM patients p
                JOIN users u ON p.user_id = u.id
                WHERE u.phone = :ph LIMIT 1
            ");
            $uStmt->execute(['ph' => $body['phone']]);
            $found = $uStmt->fetch(PDO::FETCH_ASSOC);
            if ($found) {
                $patientId = (int)$found['id'];
                $userId = (int)$found['user_id'];
            }
        }

        if (!$patientId) {
            // Auto register patient if name and phone are provided
            if (!empty($body['full_name']) && !empty($body['phone'])) {
                $defaultPassHash = \App\Helpers\HashHelper::hash('Patient@123');
                $insU = $pdo->prepare("
                    INSERT INTO users (role, full_name, phone, password_hash, status, phone_verified_at, created_at)
                    VALUES ('PATIENT', :name, :phone, :hash, 'ACTIVE', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)
                ");
                $insU->execute([
                    'name'  => $body['full_name'],
                    'phone' => $body['phone'],
                    'hash'  => $defaultPassHash
                ]);
                $userId = (int)$pdo->query("SELECT id FROM users WHERE phone = " . $pdo->quote($body['phone']))->fetchColumn();

                $insP = $pdo->prepare("
                    INSERT INTO patients (user_id, gender, address, created_at)
                    VALUES (:uid, :gender, :addr, NOW())
                    ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
                ");
                $insP->execute([
                    'uid'    => $userId,
                    'gender' => $body['gender'] ?? 'OTHER',
                    'addr'   => $body['address'] ?? null
                ]);
                $patientId = (int)$pdo->query("SELECT id FROM patients WHERE user_id = {$userId}")->fetchColumn();
            } else {
                Response::error('PATIENT_REQUIRED', 'Please select an existing patient or provide full_name and phone.', 422);
            }
        } else {
            $uStmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = :pid LIMIT 1");
            $uStmt->execute(['pid' => $patientId]);
            $userId = (int)$uStmt->fetchColumn();
        }

        // 2. Prepare mock patient session for concurrency-safe BookingService
        $actingPatientUser = [
            'id'         => $userId,
            'role'       => 'PATIENT',
            'patient_id' => $patientId
        ];

        $bookingService = new \App\Services\BookingService();
        $result = $bookingService->bookAppointment($body, $actingPatientUser);

        AuditService::log((int)$admin['id'], 'PLATFORM_ADMIN', 'ADMIN_BOOKED_FOR_PATIENT', 'appointments', (int)$result['appointment_id'], null, [
            'patient_id' => $patientId,
            'doctor_id'  => $body['doctor_id'],
            'chamber_id' => $body['chamber_id'],
            'token'      => $result['token_number']
        ]);

        Response::success($result, 'Doctor serial successfully booked on behalf of patient.', 201);
    }
}

