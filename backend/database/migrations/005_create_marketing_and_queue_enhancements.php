<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Env.php';
require_once __DIR__ . '/../../app/Helpers/Database.php';

use App\Helpers\Database;

try {
    $pdo = Database::getConnection();

    // 1. Create marketing_banners table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `marketing_banners` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `subtitle` VARCHAR(255) NULL,
            `badge_text` VARCHAR(50) NULL DEFAULT 'Featured',
            `banner_type` ENUM('DOCTOR', 'OFFER', 'ANNOUNCEMENT') NOT NULL DEFAULT 'DOCTOR',
            `doctor_id` BIGINT UNSIGNED NULL,
            `image_url` VARCHAR(512) NULL,
            `action_url` VARCHAR(512) NULL,
            `display_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_banners_active` (`is_active`, `display_order`),
            CONSTRAINT `fk_banners_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Add presence_status and is_booking_open to doctor_daily_availability if not existing
    $cols = $pdo->query("SHOW COLUMNS FROM `doctor_daily_availability` LIKE 'presence_status'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("
            ALTER TABLE `doctor_daily_availability`
            ADD COLUMN `presence_status` ENUM('IN_CHAMBER', 'AWAY', 'PRAYER_BREAK', 'MEAL_BREAK', 'SHORT_PAUSE') NOT NULL DEFAULT 'IN_CHAMBER' AFTER `doctor_live_status`,
            ADD COLUMN `is_booking_open` TINYINT(1) NOT NULL DEFAULT 1 AFTER `presence_status`;
        ");
    }

    // 3. Populate default featured marketing doctors if empty
    $chk = $pdo->query("SELECT COUNT(*) FROM `marketing_banners`")->fetchColumn();
    if ((int)$chk === 0) {
        $docs = $pdo->query("
            SELECT d.id, u.full_name, d.designation, u.profile_photo AS avatar_url,
                   (SELECT GROUP_CONCAT(s.name SEPARATOR ', ') 
                    FROM doctor_specialties ds 
                    JOIN specialties s ON ds.specialty_id = s.id 
                    WHERE ds.doctor_id = d.id) AS specialties
            FROM doctors d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.verification_status = 'VERIFIED' 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $order = 1;
        $ins = $pdo->prepare("
            INSERT INTO `marketing_banners` (title, subtitle, badge_text, banner_type, doctor_id, image_url, display_order, is_active)
            VALUES (:title, :sub, :badge, 'DOCTOR', :did, :img, :ord, 1)
        ");
        foreach ($docs as $d) {
            $specText = $d['specialties'] ? $d['specialties'] . ' Specialist' : 'Consultant Physician';
            $ins->execute([
                'title' => $d['full_name'],
                'sub'   => $specText . ' • ' . ($d['designation'] ?: 'Top Consultant'),
                'badge' => 'Featured Doctor',
                'did'   => (int)$d['id'],
                'img'   => $d['avatar_url'] ?: null,
                'ord'   => $order++
            ]);
        }
    }

    echo "Migration 005_create_marketing_and_queue_enhancements: SUCCESS\n";
} catch (Throwable $e) {
    echo "Migration 005 Error: " . $e->getMessage() . "\n";
    exit(1);
}
