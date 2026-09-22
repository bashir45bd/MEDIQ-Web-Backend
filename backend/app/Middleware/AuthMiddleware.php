<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\JwtHelper;
use App\Helpers\Response;
use App\Helpers\Database;
use PDO;

class AuthMiddleware {
    private static ?array $authenticatedUser = null;

    public static function handle(): array {
        $token = self::extractToken();
        if (!$token) {
            Response::error('AUTH_REQUIRED', 'Authentication token required.', 401);
        }

        $payload = JwtHelper::verify($token);
        if (!$payload || !isset($payload['sub'])) {
            Response::error('AUTH_REQUIRED', 'Invalid or expired authentication token.', 401);
        }

        // Verify user exists and is active
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, role, full_name, email, phone, profile_photo, status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $payload['sub']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error('AUTH_REQUIRED', 'User account not found.', 401);
        }

        if ($user['status'] === 'SUSPENDED') {
            Response::error('FORBIDDEN', 'Your account has been suspended. Please contact support.', 403);
        }

        // Attach doctor or patient profile ID
        if ($user['role'] === 'DOCTOR') {
            $docStmt = $pdo->prepare("SELECT id, bmdc_number, verification_status FROM doctors WHERE user_id = :uid LIMIT 1");
            $docStmt->execute(['uid' => $user['id']]);
            $doc = $docStmt->fetch(PDO::FETCH_ASSOC);
            $user['doctor_id'] = $doc ? (int)$doc['id'] : null;
            $user['verification_status'] = $doc ? $doc['verification_status'] : null;
        } elseif ($user['role'] === 'PATIENT') {
            $patStmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = :uid LIMIT 1");
            $patStmt->execute(['uid' => $user['id']]);
            $pat = $patStmt->fetch(PDO::FETCH_ASSOC);
            $user['patient_id'] = $pat ? (int)$pat['id'] : null;
        }

        self::$authenticatedUser = $user;
        return $user;
    }

    public static function user(): ?array {
        return self::$authenticatedUser;
    }

    public static function setUser(array $user): void {
        self::$authenticatedUser = $user;
    }

    private static function extractToken(): ?string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Fallback to cookie for Web portals
        if (isset($_COOKIE['mediq_token'])) {
            return $_COOKIE['mediq_token'];
        }

        return null;
    }
}
