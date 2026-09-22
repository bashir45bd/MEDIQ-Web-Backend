<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/autoload.php';

use App\Helpers\Database;

$pdo = Database::getConnection();

$sql = "
CREATE TABLE IF NOT EXISTS `phone_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL,
  `otp_code` VARCHAR(6) NOT NULL,
  `purpose` ENUM('REGISTRATION', 'PASSWORD_RESET') NOT NULL DEFAULT 'REGISTRATION',
  `payload` TEXT NULL,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_phone_purpose` (`phone`, `purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$pdo->exec($sql);
echo "TABLE_PHONE_VERIFICATIONS_CREATED_SUCCESSFULLY\n";
