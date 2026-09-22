<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Response;
use PDO;

class TaxonomyController extends BaseController {
    public function getSpecialties(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, name, slug, icon, description FROM specialties WHERE is_active = 1 ORDER BY name ASC");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getCities(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, name, division FROM cities WHERE is_active = 1 ORDER BY division, name ASC");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAreas(array $params): void {
        $cityId = (int)($params['id'] ?? 0);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, postal_code FROM areas WHERE city_id = :cid AND is_active = 1 ORDER BY name ASC");
        $stmt->execute(['cid' => $cityId]);
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAllSpecialtiesAdmin(): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT s.*, (SELECT COUNT(*) FROM doctor_specialties ds WHERE ds.specialty_id = s.id) AS doctor_count 
            FROM specialties s 
            ORDER BY s.name ASC
        ");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createSpecialty(): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();
        \App\Validators\Validator::make($body)->required(['name'])->validateOrAbort();

        $name = trim($body['name']);
        $slug = trim($body['slug'] ?? '') ?: strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $icon = trim($body['icon'] ?? 'stethoscope');
        $description = trim($body['description'] ?? '');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO specialties (name, slug, icon, description, is_active, created_at)
            VALUES (:name, :slug, :icon, :desc, 1, NOW())
        ");
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'desc' => $description
        ]);

        Response::success(['id' => (int)$pdo->lastInsertId()], 'Specialty created successfully.', 201);
    }

    public function updateSpecialty(array $params): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $id = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        $pdo = Database::getConnection();
        if (isset($body['is_active']) && !isset($body['name'])) {
            $pdo->prepare("UPDATE specialties SET is_active = :act WHERE id = :id")
                ->execute(['act' => (int)$body['is_active'], 'id' => $id]);
            Response::success(null, 'Specialty status updated successfully.');
            return;
        }

        \App\Validators\Validator::make($body)->required(['name'])->validateOrAbort();

        $stmt = $pdo->prepare("
            UPDATE specialties 
            SET name = :name, slug = :slug, icon = :icon, description = :desc, is_active = :act
            WHERE id = :id
        ");
        $stmt->execute([
            'name' => trim($body['name']),
            'slug' => trim($body['slug'] ?? '') ?: strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $body['name'])),
            'icon' => trim($body['icon'] ?? 'stethoscope'),
            'desc' => trim($body['description'] ?? ''),
            'act'  => isset($body['is_active']) ? (int)$body['is_active'] : 1,
            'id'   => $id
        ]);

        Response::success(null, 'Specialty updated successfully.');
    }

    public function deleteSpecialty(array $params): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE specialties SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
        Response::success(null, 'Specialty deactivated successfully.');
    }

    // --------------------------------------------------------------------------
    // Marketing Banners & Featured Doctors
    // --------------------------------------------------------------------------
    public function getMarketingBanners(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT b.*, 
                   d.designation, d.consultation_fee, d.rating_avg, d.rating_count, d.experience_years,
                   u.full_name AS doctor_name, u.profile_photo AS doctor_avatar,
                   (SELECT GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ')
                    FROM doctor_specialties ds 
                    JOIN specialties s ON ds.specialty_id = s.id 
                    WHERE ds.doctor_id = d.id) AS doctor_specialties
            FROM marketing_banners b
            LEFT JOIN doctors d ON b.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE b.is_active = 1
            ORDER BY b.display_order ASC, b.id DESC
            LIMIT 10
        ");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }


    public function getAllBannersAdmin(): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT b.*, u.full_name AS doctor_name 
            FROM marketing_banners b
            LEFT JOIN doctors d ON b.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY b.display_order ASC, b.id DESC
        ");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createBanner(): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();
        \App\Validators\Validator::make($body)->required(['title'])->validateOrAbort();

        $pdo = Database::getConnection();

        // Validate and sanitize doctor_id
        $doctorId = null;
        if (!empty($body['doctor_id']) && (int)$body['doctor_id'] > 0) {
            $cStmt = $pdo->prepare("SELECT id FROM doctors WHERE id = :did LIMIT 1");
            $cStmt->execute(['did' => (int)$body['doctor_id']]);
            if ($cStmt->fetchColumn()) {
                $doctorId = (int)$body['doctor_id'];
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO marketing_banners (title, subtitle, badge_text, banner_type, doctor_id, image_url, action_url, display_order, is_active, created_at)
            VALUES (:title, :sub, :badge, :btype, :did, :img, :act_url, :ord, 1, NOW())
        ");
        $stmt->execute([
            'title'   => trim($body['title']),
            'sub'     => trim($body['subtitle'] ?? ''),
            'badge'   => trim($body['badge_text'] ?? 'Featured'),
            'btype'   => $body['banner_type'] ?? 'DOCTOR',
            'did'     => $doctorId,
            'img'     => !empty($body['image_url']) ? trim($body['image_url']) : null,
            'act_url' => !empty($body['action_url']) ? trim($body['action_url']) : null,
            'ord'     => (int)($body['display_order'] ?? 0)
        ]);

        $bannerId = (int)$pdo->lastInsertId();

        // Optional Instant Notification Broadcast to all Patients
        if (!empty($body['notify_patients'])) {
            try {
                $patients = $pdo->query("SELECT user_id FROM patients")->fetchAll(PDO::FETCH_COLUMN);
                $title = "🌟 " . trim($body['title']);
                $msg = trim($body['subtitle'] ?? '') ?: "MEDIQ-তে নতুন আপডেট বা বিশেষ অফার এসেছে। বিস্তারিত জানতে অ্যাপ ওপেন করুন।";

                foreach ($patients as $pUid) {
                    \App\Services\NotificationService::dispatch(
                        (int)$pUid,
                        'MARKETING_ANNOUNCEMENT',
                        'MKT_' . $bannerId . '_' . $pUid . '_' . time(),
                        'PUSH',
                        $title,
                        $msg,
                        [
                            'banner_id'  => (string)$bannerId,
                            'type'       => 'marketing',
                            'doctor_id'  => (string)($doctorId ?? '')
                        ]
                    );
                }
            } catch (\Throwable $e) {
                error_log("[MARKETING BROADCAST ERROR] " . $e->getMessage());
            }
        }

        Response::success(['id' => $bannerId], 'Marketing banner created successfully.', 201);
    }

    public function updateBanner(array $params): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $id = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        $pdo = Database::getConnection();

        // Allow toggling active status without re-sending all fields
        if (isset($body['is_active']) && !isset($body['title'])) {
            $pdo->prepare("UPDATE marketing_banners SET is_active = :act, updated_at = NOW() WHERE id = :id")
                ->execute(['act' => (int)$body['is_active'], 'id' => $id]);
            Response::success(null, 'Marketing banner status updated successfully.');
            return;
        }

        \App\Validators\Validator::make($body)->required(['title'])->validateOrAbort();

        // Validate and sanitize doctor_id
        $doctorId = null;
        if (!empty($body['doctor_id']) && (int)$body['doctor_id'] > 0) {
            $cStmt = $pdo->prepare("SELECT id FROM doctors WHERE id = :did LIMIT 1");
            $cStmt->execute(['did' => (int)$body['doctor_id']]);
            if ($cStmt->fetchColumn()) {
                $doctorId = (int)$body['doctor_id'];
            }
        }

        $stmt = $pdo->prepare("
            UPDATE marketing_banners 
            SET title = :title, subtitle = :sub, badge_text = :badge, banner_type = :btype,
                doctor_id = :did, image_url = :img, action_url = :act_url, display_order = :ord,
                is_active = :act, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'title'   => trim($body['title']),
            'sub'     => trim($body['subtitle'] ?? ''),
            'badge'   => trim($body['badge_text'] ?? 'Featured'),
            'btype'   => $body['banner_type'] ?? 'DOCTOR',
            'did'     => $doctorId,
            'img'     => !empty($body['image_url']) ? trim($body['image_url']) : null,
            'act_url' => !empty($body['action_url']) ? trim($body['action_url']) : null,
            'ord'     => (int)($body['display_order'] ?? 0),
            'act'     => isset($body['is_active']) ? (int)$body['is_active'] : 1,
            'id'      => $id
        ]);

        Response::success(null, 'Marketing banner updated successfully.');
    }

    public function deleteBanner(array $params): void {
        \App\Middleware\RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $id = (int)($params['id'] ?? 0);
        $pdo = Database::getConnection();
        $pdo->prepare("DELETE FROM marketing_banners WHERE id = :id")->execute(['id' => $id]);
        Response::success(null, 'Marketing banner deleted successfully.');
    }
}
