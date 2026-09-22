<?php
declare(strict_types=1);

namespace App\Helpers;

use Exception;

class JwtHelper {
    public static function generate(array $payload, int $ttlSeconds = 3600): string {
        $secret = Env::get('JWT_SECRET', 'mediq_default_dev_secret_key_change_in_prod');
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $issuedAt = time();
        $expire = $issuedAt + $ttlSeconds;

        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expire;

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "{$base64UrlHeader}.{$base64UrlPayload}", $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return "{$base64UrlHeader}.{$base64UrlPayload}.{$base64UrlSignature}";
    }

    public static function verify(string $jwt): ?array {
        $secret = Env::get('JWT_SECRET', 'mediq_default_dev_secret_key_change_in_prod');
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;

        $signature = self::base64UrlDecode($signature64);
        $expectedSignature = hash_hmac('sha256', "{$header64}.{$payload64}", $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payload64), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
