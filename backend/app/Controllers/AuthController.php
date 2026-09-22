<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Validators\Validator;
use App\Helpers\Response;

class AuthController extends BaseController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function register(): void {
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['full_name', 'phone', 'password', 'role'])
            ->in('role', ['PATIENT', 'DOCTOR'])
            ->minLength('password', 6)
            ->validateOrAbort();

        $result = $this->authService->register($body);
        Response::success($result, 'User registered successfully.', 201);
    }

    public function login(): void {
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['phone', 'password'])
            ->validateOrAbort();

        $result = $this->authService->login($body);
        Response::success($result, 'Logged in successfully.');
    }

    public function me(): void {
        $user = $this->requireUser();
        Response::success($user, 'Authenticated user profile.');
    }

    public function logout(): void {
        $user = $this->requireUser();
        // Clear cookie if present
        if (isset($_COOKIE['mediq_token'])) {
            setcookie('mediq_token', '', time() - 3600, '/');
        }
        Response::success(null, 'Logged out successfully.');
    }

    public function uploadAvatar(): void {
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $uploadDir = __DIR__ . '/../../public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imagePath = null;

        // 1. Check for standard multipart file upload
        $fileKey = null;
        foreach (['avatar', 'photo', 'image', 'file'] as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $fileKey = $key;
                break;
            }
        }

        if ($fileKey) {
            $file = $_FILES[$fileKey];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $ext = 'jpg';
            }
            $filename = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/avatars/' . $filename;
            }
        } else {
            // 2. Check for base64 encoded image in JSON body or POST
            $body = $this->getJsonBody();
            $base64Data = $body['avatar'] ?? $body['photo'] ?? $body['image'] ?? $_POST['avatar'] ?? $_POST['photo'] ?? null;
            if ($base64Data) {
                $ext = 'jpg';
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $ext = strtolower($type[1]);
                    if ($ext === 'jpeg') $ext = 'jpg';
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                }
                $decoded = base64_decode($base64Data);
                if ($decoded !== false && strlen($decoded) > 0) {
                    $filename = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $filename;
                    if (file_put_contents($targetPath, $decoded)) {
                        $imagePath = 'uploads/avatars/' . $filename;
                    }
                }
            }
        }

        if (!$imagePath) {
            Response::error('UPLOAD_FAILED', 'No valid image received. Send multipart file or base64 string.', 400);
        }

        // Update database record in users table
        $pdo = \App\Helpers\Database::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET profile_photo = :photo, updated_at = NOW() WHERE id = :uid");
        $stmt->execute(['photo' => $imagePath, 'uid' => $userId]);

        Response::success([
            'profile_photo' => $imagePath,
            'user_id'       => $userId
        ], 'Profile photo updated successfully.');
    }

    public function sendRegistrationOtp(): void {
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['full_name', 'phone', 'password', 'role'])
            ->in('role', ['PATIENT', 'DOCTOR'])
            ->minLength('password', 6)
            ->validateOrAbort();

        $phone = trim($body['phone']);
        $password = $body['password'];
        $confirmPassword = $body['confirm_password'] ?? $password;

        if ($password !== $confirmPassword) {
            Response::error('PASSWORD_MISMATCH', 'Password and confirm password do not match.', 422);
        }

        $pdo = \App\Helpers\Database::getConnection();

        // Check if phone already registered
        $chk = $pdo->prepare("SELECT id FROM users WHERE phone = :ph LIMIT 1");
        $chk->execute(['ph' => $phone]);
        if ($chk->fetch()) {
            Response::error('PHONE_EXISTS', 'An account is already registered with this mobile number.', 409);
        }

        // Generate 6-digit OTP code (demo/standard code 123456 or random)
        $otpCode = (string)mt_rand(100000, 999999);

        // Store in phone_verifications with 10-minute validity
        $ins = $pdo->prepare("
            INSERT INTO phone_verifications (phone, otp_code, purpose, payload, expires_at, created_at)
            VALUES (:ph, :otp, 'REGISTRATION', :payload, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())
        ");
        $ins->execute([
            'ph'      => $phone,
            'otp'     => $otpCode,
            'payload' => json_encode($body)
        ]);

        Response::success([
            'phone'   => $phone,
            'otp'     => $otpCode, // Returned for dev / testing convenience
            'message' => "Verification code sent to {$phone}. Please enter the 6-digit code to complete registration."
        ]);
    }

    public function verifyRegistrationOtp(): void {
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['phone', 'otp_code'])
            ->validateOrAbort();

        $phone = trim($body['phone']);
        $otp = trim($body['otp_code']);

        $pdo = \App\Helpers\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, payload FROM phone_verifications
            WHERE phone = :ph AND otp_code = :otp AND purpose = 'REGISTRATION' AND is_used = 0 AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['ph' => $phone, 'otp' => $otp]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$record) {
            Response::error('INVALID_OTP', 'Invalid or expired verification code. Please request a new OTP.', 422);
        }

        // Mark OTP as used
        $pdo->prepare("UPDATE phone_verifications SET is_used = 1 WHERE id = :id")->execute(['id' => $record['id']]);

        // Complete registration using stored payload
        $regData = json_decode($record['payload'], true);
        if (!is_array($regData)) {
            Response::error('DATA_CORRUPT', 'Registration session expired. Please register again.', 422);
        }

        $result = $this->authService->register($regData);

        // Mark phone as verified
        $pdo->prepare("UPDATE users SET phone_verified_at = NOW() WHERE phone = :ph")->execute(['ph' => $phone]);

        Response::success($result, 'Phone verified and account created successfully!', 201);
    }

    public function forgotPassword(): void {
        $body = $this->getJsonBody();
        Validator::make($body)->required(['phone'])->validateOrAbort();

        $phone = trim($body['phone']);
        $pdo = \App\Helpers\Database::getConnection();

        $chk = $pdo->prepare("SELECT id, full_name FROM users WHERE phone = :ph LIMIT 1");
        $chk->execute(['ph' => $phone]);
        $user = $chk->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error('USER_NOT_FOUND', 'No account found with this mobile number.', 404);
        }

        $otpCode = (string)mt_rand(100000, 999999);

        $ins = $pdo->prepare("
            INSERT INTO phone_verifications (phone, otp_code, purpose, expires_at, created_at)
            VALUES (:ph, :otp, 'PASSWORD_RESET', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())
        ");
        $ins->execute([
            'ph'  => $phone,
            'otp' => $otpCode
        ]);

        Response::success([
            'phone'   => $phone,
            'otp'     => $otpCode,
            'message' => "Password reset OTP sent to {$phone}."
        ]);
    }

    public function verifyResetOtp(): void {
        $body = $this->getJsonBody();
        Validator::make($body)->required(['phone', 'otp_code'])->validateOrAbort();

        $phone = trim($body['phone']);
        $otp = trim($body['otp_code']);

        $pdo = \App\Helpers\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id FROM phone_verifications
            WHERE phone = :ph AND otp_code = :otp AND purpose = 'PASSWORD_RESET' AND is_used = 0 AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['ph' => $phone, 'otp' => $otp]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$record) {
            Response::error('INVALID_OTP', 'Invalid or expired OTP code. Please request a new code.', 422);
        }

        Response::success(['valid' => true], 'OTP verified successfully.');
    }

    public function resetPassword(): void {
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['phone', 'otp_code', 'new_password'])
            ->minLength('new_password', 6)
            ->validateOrAbort();

        $phone = trim($body['phone']);
        $otp = trim($body['otp_code']);
        $newPassword = $body['new_password'];
        $confirm = $body['confirm_password'] ?? $newPassword;

        if ($newPassword !== $confirm) {
            Response::error('PASSWORD_MISMATCH', 'New password and confirm password do not match.', 422);
        }

        $pdo = \App\Helpers\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id FROM phone_verifications
            WHERE phone = :ph AND otp_code = :otp AND purpose = 'PASSWORD_RESET' AND is_used = 0 AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['ph' => $phone, 'otp' => $otp]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$record) {
            Response::error('INVALID_OTP', 'Invalid or expired OTP code. Please request a new one.', 422);
        }

        // Mark OTP used
        $pdo->prepare("UPDATE phone_verifications SET is_used = 1 WHERE id = :id")->execute(['id' => $record['id']]);

        // Update password in users table
        $newHash = \App\Helpers\HashHelper::hash($newPassword);
        $up = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE phone = :ph");
        $up->execute(['hash' => $newHash, 'ph' => $phone]);

        Response::success(null, 'Password has been reset successfully! You can now log in with your new password.');
    }

    public function saveDeviceToken(): void {
        $user = \App\Middleware\AuthMiddleware::handle();
        $body = $this->getJsonBody();
        Validator::make($body)->required(['fcm_token'])->validateOrAbort();

        $fcmToken = trim($body['fcm_token']);
        $deviceType = strtoupper(trim($body['device_type'] ?? 'ANDROID'));
        if (!in_array($deviceType, ['ANDROID', 'IOS', 'WEB'], true)) {
            $deviceType = 'ANDROID';
        }
        $deviceName = !empty($body['device_name']) ? substr(trim($body['device_name']), 0, 128) : null;

        $pdo = \App\Helpers\Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO user_device_tokens (user_id, fcm_token, device_type, device_name, is_active, last_used_at, created_at, updated_at)
            VALUES (:uid, :token, :dtype, :dname, 1, NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                is_active = 1,
                device_type = VALUES(device_type),
                device_name = VALUES(device_name),
                last_used_at = NOW(),
                updated_at = NOW()
        ");
        $stmt->execute([
            'uid'   => (int)$user['id'],
            'token' => $fcmToken,
            'dtype' => $deviceType,
            'dname' => $deviceName
        ]);

        Response::success(null, 'Device token registered successfully.');
    }
}


