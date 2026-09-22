<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Env;
use PDO;
use Throwable;

class FcmService {
    private static ?string $cachedAccessToken = null;
    private static int $tokenExpiresAt = 0;

    /**
     * Get path to Firebase service account JSON
     */
    private static function getServiceAccountPath(): string {
        $configured = Env::get('FIREBASE_CREDENTIALS');
        if ($configured && file_exists($configured)) {
            return $configured;
        }
        $defaultPath = dirname(__DIR__, 2) . '/config/firebase_service_account.json';
        return $defaultPath;
    }

    /**
     * Generate Google OAuth2 Bearer Access Token using Service Account Key via OpenSSL RS256
     */
    private static function getAccessToken(): ?array {
        if (self::$cachedAccessToken && time() < self::$tokenExpiresAt) {
            return [
                'token'      => self::$cachedAccessToken,
                'project_id' => Env::get('FIREBASE_PROJECT_ID')
            ];
        }

        $keyFile = self::getServiceAccountPath();
        if (!file_exists($keyFile)) {
            return null;
        }

        try {
            $keyData = json_decode(file_get_contents($keyFile), true);
            if (!$keyData || empty($keyData['private_key']) || empty($keyData['client_email'])) {
                return null;
            }

            $projectId = $keyData['project_id'] ?? Env::get('FIREBASE_PROJECT_ID', 'mediq-app');
            $now = time();
            $expiry = $now + 3600;

            // Header & Claim
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $claim = json_encode([
                'iss'   => $keyData['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $expiry,
                'iat'   => $now
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlClaim  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));
            $signatureInput  = $base64UrlHeader . '.' . $base64UrlClaim;

            $signature = '';
            $success = openssl_sign($signatureInput, $signature, $keyData['private_key'], OPENSSL_ALGO_SHA256);
            if (!$success) {
                return null;
            }

            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $jwt = $signatureInput . '.' . $base64UrlSignature;

            // Request OAuth2 Token
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            curl_close($ch);

            if (!$response) return null;

            $respData = json_decode($response, true);
            if (!empty($respData['access_token'])) {
                self::$cachedAccessToken = $respData['access_token'];
                self::$tokenExpiresAt = $now + 3000; // Cache for 50 minutes
                return [
                    'token'      => self::$cachedAccessToken,
                    'project_id' => $projectId
                ];
            }
            return null;
        } catch (Throwable $e) {
            error_log('[FCM AUTH ERROR] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send push notification to a specific device token via FCM HTTP v1 API
     */
    public static function sendToToken(string $deviceToken, string $title, string $body, array $data = []): bool {
        $auth = self::getAccessToken();
        if (!$auth) {
            error_log('[FCM DISPATCH] Google service account not configured; skipping remote push.');
            return false;
        }

        $projectId = $auth['project_id'];
        $accessToken = $auth['token'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Convert data values to strings
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string)$k] = is_array($v) ? json_encode($v) : (string)$v;
        }

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id'   => 'mediq_live_channel',
                        'sound'        => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                ]
            ]
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json; UTF-8'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return true;
            }

            // Stale or unregistered token
            if ($httpCode === 404 || str_contains((string)$response, 'UNREGISTERED')) {
                self::deactivateToken($deviceToken);
            }

            error_log("[FCM SEND ERROR] HTTP {$httpCode}: {$response}");
            return false;
        } catch (Throwable $e) {
            error_log('[FCM EXCEPTION] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to all active devices of a user
     */
    public static function sendToUser(int $userId, string $title, string $body, array $data = []): int {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT fcm_token FROM user_device_tokens
                WHERE user_id = :uid AND is_active = 1
            ");
            $stmt->execute(['uid' => $userId]);
            $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($tokens)) {
                return 0;
            }

            $successCount = 0;
            foreach ($tokens as $token) {
                if (self::sendToToken($token, $title, $body, $data)) {
                    $successCount++;
                }
            }
            return $successCount;
        } catch (Throwable $e) {
            error_log('[FCM USER DISPATCH ERROR] ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark an invalid token as inactive
     */
    private static function deactivateToken(string $deviceToken): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE user_device_tokens SET is_active = 0 WHERE fcm_token = :t");
            $stmt->execute(['t' => $deviceToken]);
        } catch (Throwable $_) {}
    }
}