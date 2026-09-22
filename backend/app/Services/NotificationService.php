<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use PDO;
use Throwable;

class NotificationService {
    public static function dispatch(
        int $userId,
        string $eventKey,
        string $idempotencyKey,
        string $channel,
        string $title,
        string $body,
        array $data = []
    ): bool {
        try {
            $pdo = Database::getConnection();

            // 1. Idempotency Check: prevent duplicate notification delivery
            $check = $pdo->prepare("SELECT id FROM notifications WHERE idempotency_key = :ikey LIMIT 1");
            $check->execute(['ikey' => $idempotencyKey]);
            if ($check->fetchColumn()) {
                // Already dispatched
                return true;
            }

            // 2. Insert notification record into database
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, event_key, idempotency_key, channel, title, body, status, sent_at, created_at)
                VALUES (:uid, :ev, :ikey, :ch, :title, :body, 'SENT', NOW(), NOW())
            ");
            $stmt->execute([
                'uid'   => $userId,
                'ev'    => $eventKey,
                'ikey'  => $idempotencyKey,
                'ch'    => $channel,
                'title' => $title,
                'body'  => $body
            ]);

            // 3. Dispatch FCM Push Notification to user's registered devices
            if ($channel === 'PUSH' || $channel === 'ALL') {
                $payloadData = array_merge([
                    'event_key'       => $eventKey,
                    'idempotency_key' => $idempotencyKey,
                    'title'           => $title,
                    'body'            => $body,
                ], $data);

                FcmService::sendToUser($userId, $title, $body, $payloadData);
            }

            return true;
        } catch (Throwable $e) {
            error_log("[NOTIFICATION ERROR] " . $e->getMessage());
            return false;
        }
    }
}
