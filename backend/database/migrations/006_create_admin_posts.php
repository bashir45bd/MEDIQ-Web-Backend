<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Env.php';
require_once __DIR__ . '/../../app/Helpers/Database.php';

use App\Helpers\Database;

try {
    $pdo = Database::getConnection();

    // 1. Create admin_posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_posts` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `category` ENUM('DOCTOR_ANNOUNCEMENT', 'SPECIAL_OFFER', 'HEALTH_TIPS', 'GENERAL_NOTICE') NOT NULL DEFAULT 'DOCTOR_ANNOUNCEMENT',
            `doctor_id` BIGINT UNSIGNED NULL,
            `cover_image` VARCHAR(512) NULL,
            `summary` TEXT NOT NULL,
            `content` LONGTEXT NOT NULL,
            `badge_text` VARCHAR(50) NULL DEFAULT 'Announcement',
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_posts_status` (`is_published`, `category`, `created_at`),
            CONSTRAINT `fk_posts_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "[MIGRATION] admin_posts table created or verified.\n";

    // 2. Seed initial posts if table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `admin_posts`")->fetchColumn();
    if ($count === 0) {
        $firstDoctorId = $pdo->query("SELECT id FROM doctors ORDER BY id ASC LIMIT 1")->fetchColumn();
        $docId = $firstDoctorId ? (int)$firstDoctorId : null;

        $stmt = $pdo->prepare("
            INSERT INTO `admin_posts` 
            (title, category, doctor_id, cover_image, summary, content, badge_text, is_published, views_count, created_at)
            VALUES 
            (:title, :category, :doctor_id, :cover_image, :summary, :content, :badge_text, 1, :views, NOW())
        ");

        $stmt->execute([
            'title' => 'নতুন বিশেষজ্ঞ চিকিৎসক যুক্ত হয়েছেন MEDIQ-তে!',
            'category' => 'DOCTOR_ANNOUNCEMENT',
            'doctor_id' => $docId,
            'cover_image' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=800',
            'summary' => 'আমাদের প্ল্যাটফর্মে নতুন হৃদরোগ বিশেষজ্ঞ যোগ দিয়েছেন। এখন সহজেই তাঁর চেম্বারের সিরিয়াল বুকিং করুন।',
            'content' => "সম্মানিত সেবাগ্রহীতা,\n\nআমরা অত্যন্ত আনন্দের সাথে জানাচ্ছি যে MEDIQ প্ল্যাটফর্মে নতুন স্বনামধন্য বিশেষজ্ঞ চিকিৎসক যুক্ত হয়েছেন। এখন থেকে রোগীরা দীর্ঘক্ষণ লাইনে না দাঁড়িয়ে ডিজিটালভাবে তাঁর চেম্বারের রিয়েল-টাইম সিরিয়াল বুক করতে পারবেন।\n\nচেম্বারে আসার ২০-২৫ মিনিট আগে লাইভ কিউ ট্র্যাকার দেখে সরাসরি উপস্থিত হতে পারবেন। আজই আপনার বা আপনার পরিবারের জন্য অ্যাপয়েন্টমেন্ট নিশ্চিত করুন।",
            'badge_text' => '🎉 নতুন ডাক্তার',
            'views' => 142
        ]);

        $stmt->execute([
            'title' => 'স্পেশাল হেলথ চেকআপ প্যাকেজে ১৫% ছাড়!',
            'category' => 'SPECIAL_OFFER',
            'doctor_id' => null,
            'cover_image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800',
            'summary' => 'চলতি মাসে MEDIQ-র মাধ্যমে যেকোনো টেস্ট বা অ্যাপয়েন্টমেন্ট বুকিংয়ে উপভোগ করুন আকর্ষণীয় ক্যাশব্যাক ও ছাড়।',
            'content' => "সুসংবাদ!\n\nআপনার ও আপনার পরিবারের সুস্বাস্থ্য নিশ্চিত করতে চলতি মাসে MEDIQ-র মাধ্যমে সকল বিশেষজ্ঞ পরামর্শ ও স্বাস্থ্য পরীক্ষায় পাচ্ছেন বিশেষ ১৫% ছাড়।\n\nঅফারটি পেতে এখনই আপনার পছন্দের ডাক্তারের সিরিয়াল নিন এবং সরাসরি চেম্বারে উপস্থিত হয়ে ডিজিটাল ভাউচার প্রদর্শন করুন। অফারটি সীমিত সময়ের জন্য প্রযোজ্য।",
            'badge_text' => '🎁 স্পেশাল অফার',
            'views' => 285
        ]);

        $stmt->execute([
            'title' => 'গরমে পানিশূন্যতা ও হিটস্ট্রোক রোধে ৫টি জরুরি পরামর্শ',
            'category' => 'HEALTH_TIPS',
            'doctor_id' => null,
            'cover_image' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?w=800',
            'summary' => 'অতিরিক্ত গরমে সুস্থ থাকতে প্রচুর বিশুদ্ধ পানি পান করুন এবং চিকিৎসকের এই স্বাস্থ্যবিধিগুলো মেনে চলুন।',
            'content' => "তীব্র তাপদাহে আমাদের শরীরে নানারকম স্বাস্থ্য সমস্যা দেখা দিতে পারে। বিশেষ করে বয়োবৃদ্ধ এবং শিশুদের জন্য এই সময় বাড়তি সতর্কতা অবলম্বন করা উচিত।\n\n১. প্রতিদিন পর্যাপ্ত পরিমাণে (কমপক্ষে ৩-৪ লিটার) বিশুদ্ধ পানি, ডাবের পানি ও স্যালাইন পান করুন।\n২. দুপুর ১২টা থেকে বিকেল ৪টা পর্যন্ত সরাসরি প্রখর রোদ এড়িয়ে চলুন।\n৩. ঢিলেঢালা সুতি পোশাক পরিধান করুন।\n৪. বাইরের খোলা বা বাসি খাবার খাওয়া থেকে বিরত থাকুন।\n৫. মাথা ঘোরা বা তীব্র দুর্বলতা অনুভব করলে দ্রুত বিশেষজ্ঞ ডাক্তারের পরামর্শ নিন।",
            'badge_text' => '💡 স্বাস্থ্য টিপস',
            'views' => 410
        ]);

        echo "[MIGRATION] Seeded 3 sample admin posts.\n";
    }

} catch (PDOException $e) {
    echo "[MIGRATION ERROR] " . $e->getMessage() . "\n";
    exit(1);
}