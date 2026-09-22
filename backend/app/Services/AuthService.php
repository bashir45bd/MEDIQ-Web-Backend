<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Helpers\HashHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Database;
use App\Helpers\Response;
use PDO;

class AuthService {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    public function register(array $data): array {
        // Check uniqueness of phone
        if ($this->userRepo->findByPhone($data['phone'])) {
            Response::error('PHONE_ALREADY_EXISTS', 'A user with this mobile number already exists.', 409);
        }

        // Check uniqueness of email if provided
        if (!empty($data['email']) && $this->userRepo->findByEmail($data['email'])) {
            Response::error('EMAIL_ALREADY_EXISTS', 'A user with this email address already exists.', 409);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $data['password_hash'] = HashHelper::make($data['password']);
            $userId = $this->userRepo->create($data);

            // If role is DOCTOR, create doctor profile with PENDING verification
            if ($data['role'] === 'DOCTOR') {
                $docStmt = $pdo->prepare("
                    INSERT INTO doctors (user_id, bmdc_number, designation, experience_years, consultation_fee, follow_up_fee, verification_status, created_at)
                    VALUES (:uid, :bmdc, :desig, :exp, :cfee, :ffee, 'PENDING', NOW())
                ");
                $docStmt->execute([
                    'uid'   => $userId,
                    'bmdc'  => $data['bmdc_number'] ?? ('BMDC-TMP-' . $userId),
                    'desig' => $data['designation'] ?? 'MBBS',
                    'exp'   => (int)($data['experience_years'] ?? 1),
                    'cfee'  => (float)($data['consultation_fee'] ?? 500.00),
                    'ffee'  => (float)($data['follow_up_fee'] ?? 300.00)
                ]);
                $doctorId = (int)$pdo->lastInsertId();

                // Create default chamber for this doctor
                $cStmt = $pdo->prepare("
                    INSERT INTO doctor_chambers (doctor_id, name, address, city_id, area_id, contact_phone, room_number, token_prefix, is_active, created_at)
                    VALUES (:did, :cname, :caddr, 1, 1, :phone, 'Room 101', 'D', 1, NOW())
                ");
                $cStmt->execute([
                    'did'   => $doctorId,
                    'cname' => 'Main Chamber',
                    'caddr' => 'Dhanmondi, Dhaka',
                    'phone' => $data['phone']
                ]);
                $chamberId = (int)$pdo->lastInsertId();

                // Create default schedules for all 7 days
                $sStmt = $pdo->prepare("
                    INSERT IGNORE INTO doctor_schedules (doctor_id, chamber_id, day_of_week, start_time, end_time, slot_duration_minutes, default_capacity, is_active, created_at)
                    VALUES (:did, :cid, :dow, '09:00:00', '21:00:00', 10, 25, 1, NOW())
                ");
                for ($dow = 0; $dow <= 6; $dow++) {
                    $sStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dow' => $dow]);
                }

                // Default specialty (General)
                $spStmt = $pdo->prepare("INSERT IGNORE INTO doctor_specialties (doctor_id, specialty_id, is_primary) VALUES (:did, :sid, 1)");
                $spStmt->execute(['did' => $doctorId, 'sid' => (int)($data['specialty_id'] ?? 1)]);
            } elseif ($data['role'] === 'PATIENT') {
                $patStmt = $pdo->prepare("
                    INSERT INTO patients (user_id, dob, gender, blood_group, address, created_at)
                    VALUES (:uid, :dob, :gender, :blood, :addr, NOW())
                ");
                $patStmt->execute([
                    'uid'    => $userId,
                    'dob'    => $data['dob'] ?? null,
                    'gender' => $data['gender'] ?? 'OTHER',
                    'blood'  => $data['blood_group'] ?? null,
                    'addr'   => $data['address'] ?? null
                ]);
            }

            $pdo->commit();

            AuditService::log($userId, $data['role'], 'USER_REGISTERED', 'users', $userId, null, [
                'full_name' => $data['full_name'],
                'role'      => $data['role'],
                'phone'     => $data['phone']
            ]);

            // DOCTOR accounts require admin approval before logging in
            if ($data['role'] === 'DOCTOR') {
                return [
                    'pending_approval' => true,
                    'role'             => 'DOCTOR',
                    'user_id'          => $userId,
                    'message'          => 'Doctor registration submitted successfully. Your account is pending administrator approval before you can log in.'
                ];
            }

            return $this->login(['phone' => $data['phone'], 'password' => $data['password']]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function login(array $credentials): array {
        $user = $this->userRepo->findByPhone($credentials['phone']);
        if (!$user) {
            // Also check email
            $user = $this->userRepo->findByEmail($credentials['phone']);
        }

        if (!$user || !HashHelper::verify($credentials['password'], $user['password_hash'])) {
            Response::error('INVALID_CREDENTIALS', 'Invalid mobile number or password.', 401);
        }

        if ($user['status'] === 'SUSPENDED') {
            Response::error('FORBIDDEN', 'Your account has been suspended by administration.', 403);
        }

        $this->userRepo->updateLastLogin((int)$user['id']);

        // Fetch sub-profile details
        $pdo = Database::getConnection();
        $doctorProfile = null;
        $patientProfile = null;

        if ($user['role'] === 'DOCTOR') {
            $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = :uid LIMIT 1");
            $stmt->execute(['uid' => $user['id']]);
            $doctorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            // Enforce admin verification for doctors
            if (!$doctorProfile || ($doctorProfile['verification_status'] ?? 'PENDING') !== 'VERIFIED') {
                Response::error(
                    'DOCTOR_NOT_VERIFIED',
                    'Your doctor account is currently pending admin verification. You will be able to log in once the administrator approves your BMDC credentials.',
                    403,
                    ['verification_status' => $doctorProfile['verification_status'] ?? 'PENDING']
                );
            }
        } elseif ($user['role'] === 'PATIENT') {
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = :uid LIMIT 1");
            $stmt->execute(['uid' => $user['id']]);
            $patientProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Generate JWT
        $jwtPayload = [
            'sub'   => $user['id'],
            'role'  => $user['role'],
            'name'  => $user['full_name'],
            'phone' => $user['phone']
        ];

        if ($doctorProfile) {
            $jwtPayload['doctor_id'] = $doctorProfile['id'];
            $jwtPayload['verification_status'] = $doctorProfile['verification_status'];
        }
        if ($patientProfile) {
            $jwtPayload['patient_id'] = $patientProfile['id'];
        }

        $token = JwtHelper::generate($jwtPayload, 86400 * 30); // 30 days session

        AuditService::log((int)$user['id'], $user['role'], 'USER_LOGIN', 'users', (int)$user['id']);

        return [
            'token' => $token,
            'user'  => [
                'id'        => (int)$user['id'],
                'role'      => $user['role'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'phone'     => $user['phone'],
                'profile_photo' => $user['profile_photo'] ?? null,
                'doctor'    => $doctorProfile,
                'patient'   => $patientProfile
            ]
        ];
    }
}
