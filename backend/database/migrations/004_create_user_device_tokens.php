<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Env.php';
require_once __DIR__ . '/../../app/Helpers/Database.php';

use App\Helpers\Database;

try {
    $pdo = Database::getConnection();

    $sql = "
        CREATE TABLE IF NOT EXISTS `user_device_tokens` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `fcm_token` VARCHAR(512) NOT NULL,
            `device_type` ENUM('ANDROID', 'IOS', 'WEB') NOT NULL DEFAULT 'ANDROID',
            `device_name` VARCHAR(128) NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `last_used_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_user_device_fcm` (`user_id`, `fcm_token`(191)),
            INDEX `idx_user_tokens` (`user_id`, `is_active`),
            CONSTRAINT `fk_device_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Migration 004_create_user_device_tokens: SUCCESS\n";
} catch (Throwable $e) {
    echo "Migration 004 Error: " . $e->getMessage() . "\n";
    exit(1);
}