<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use PDO;
use Throwable;

class AuditService {
    public static function log(
        int $actorId,
        string $actorRole,
        string $action,
        string $entity,
        int $entityId,
        ?array $oldValue = null,
        ?array $newValue = null
    ): void {
        try {
            $pdo = Database::getConnection();

            // Scrub passwords and sensitive tokens
            $oldJson = $oldValue ? json_encode(self::scrub($oldValue)) : null;
            $newJson = $newValue ? json_encode(self::scrub($newValue)) : null;

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 255);

            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (actor_id, actor_role, action, entity, entity_id, old_value_json, new_value_json, ip_address, user_agent, created_at)
                VALUES (:actor_id, :actor_role, :action, :entity, :entity_id, :old_val, :new_val, :ip, :ua, NOW())
            ");

            $stmt->execute([
                'actor_id'   => $actorId,
                'actor_role' => $actorRole,
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'old_val'    => $oldJson,
                'new_val'    => $newJson,
                'ip'         => $ip,
                'ua'         => $ua
            ]);
        } catch (Throwable $e) {
            // Error logged without halting application
            error_log("[AUDIT LOG FAILURE] " . $e->getMessage());
        }
    }

    private static function scrub(array $data): array {
        $sensitiveKeys = ['password', 'password_hash', 'token', 'jwt', 'secret', 'signature', '2fa_secret'];
        foreach ($data as $k => $v) {
            if (in_array(strtolower((string)$k), $sensitiveKeys, true)) {
                $data[$k] = '***REDACTED***';
            } elseif (is_array($v)) {
                $data[$k] = self::scrub($v);
            }
        }
        return $data;
    }
}
