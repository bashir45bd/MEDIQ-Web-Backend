<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Database;
use App\Middleware\RoleMiddleware;
use App\Validators\Validator;
use App\Services\NotificationService;
use PDO;

class PostController extends BaseController {

    /**
     * Public endpoint: Get active/published posts for mobile app & web
     */
    public function getPosts(): void {
        $pdo = Database::getConnection();
        $params = $this->getQueryParams();

        $sql = "
            SELECT p.*,
                   u.full_name AS doctor_name, u.profile_photo AS doctor_avatar,
                   d.designation AS doctor_designation, d.consultation_fee,
                   d.rating_avg, d.experience_years
            FROM admin_posts p
            LEFT JOIN doctors d ON p.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE p.is_published = 1
        ";

        $binds = [];
        if (!empty($params['category'])) {
            $sql .= " AND p.category = :cat";
            $binds['cat'] = trim($params['category']);
        }

        if (!empty($params['query'])) {
            $sql .= " AND (p.title LIKE :q OR p.summary LIKE :q OR p.content LIKE :q)";
            $binds['q'] = '%' . trim($params['query']) . '%';
        }

        $sql .= " ORDER BY p.id DESC LIMIT 30";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success($posts, 'Posts retrieved successfully.');
    }

    /**
     * Public endpoint: Get single post details and increment views count
     */
    public function getPost(array $params): void {
        $id = (int)($params['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT p.*,
                   u.full_name AS doctor_name, u.profile_photo AS doctor_avatar,
                   d.designation AS doctor_designation, d.consultation_fee,
                   d.rating_avg, d.rating_count, d.experience_years,
                   (SELECT GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ')
                    FROM doctor_specialties ds 
                    JOIN specialties s ON ds.specialty_id = s.id 
                    WHERE ds.doctor_id = d.id) AS doctor_specialties
            FROM admin_posts p
            LEFT JOIN doctors d ON p.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            Response::error('POST_NOT_FOUND', 'Post not found.', 404);
            return;
        }

        // Increment view count asynchronously
        $pdo->prepare("UPDATE admin_posts SET views_count = views_count + 1 WHERE id = :id")
            ->execute(['id' => $id]);
        $post['views_count'] = (int)$post['views_count'] + 1;

        // If a doctor is linked, fetch chambers
        if (!empty($post['doctor_id'])) {
            $cStmt = $pdo->prepare("
                SELECT c.id, c.name, c.address, c.contact_phone, c.room_number,
                       ci.name AS city_name
                FROM doctor_chambers c
                LEFT JOIN cities ci ON c.city_id = ci.id
                WHERE c.doctor_id = :did AND c.is_active = 1
            ");
            $cStmt->execute(['did' => $post['doctor_id']]);
            $post['doctor_chambers'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $post['doctor_chambers'] = [];
        }

        Response::success($post, 'Post details retrieved successfully.');
    }

    /**
     * Admin endpoint: List all posts
     */
    public function getAllPostsAdmin(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT p.*, u.full_name AS doctor_name
            FROM admin_posts p
            LEFT JOIN doctors d ON p.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY p.id DESC
        ");
        Response::success($stmt->fetchAll(PDO::FETCH_ASSOC), 'All posts retrieved.');
    }

    /**
     * Admin endpoint: Create a new post and optionally broadcast instant push notifications
     */
    public function createPost(): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $body = $this->getJsonBody();

        Validator::make($body)->required(['title', 'summary', 'content'])->validateOrAbort();

        $pdo = Database::getConnection();

        // Doctor id validation
        $doctorId = null;
        if (!empty($body['doctor_id']) && (int)$body['doctor_id'] > 0) {
            $cStmt = $pdo->prepare("SELECT id FROM doctors WHERE id = :did LIMIT 1");
            $cStmt->execute(['did' => (int)$body['doctor_id']]);
            if ($cStmt->fetchColumn()) {
                $doctorId = (int)$body['doctor_id'];
            }
        }

        $category = in_array($body['category'] ?? '', ['DOCTOR_ANNOUNCEMENT', 'SPECIAL_OFFER', 'HEALTH_TIPS', 'GENERAL_NOTICE'], true)
            ? $body['category']
            : 'DOCTOR_ANNOUNCEMENT';

        $badgeText = !empty($body['badge_text']) ? trim($body['badge_text']) : null;
        if (!$badgeText) {
            $badgeText = match($category) {
                'DOCTOR_ANNOUNCEMENT' => '🎉 নতুন ডাক্তার',
                'SPECIAL_OFFER'       => '🎁 স্পেশাল অফার',
                'HEALTH_TIPS'         => '💡 স্বাস্থ্য পরামর্শ',
                default               => '📢 নোটিশ'
            };
        }

        $stmt = $pdo->prepare("
            INSERT INTO admin_posts 
            (title, category, doctor_id, cover_image, summary, content, badge_text, is_published, views_count, created_at)
            VALUES 
            (:title, :category, :doctor_id, :cover_image, :summary, :content, :badge_text, :is_published, 0, NOW())
        ");

        $stmt->execute([
            'title'        => trim($body['title']),
            'category'     => $category,
            'doctor_id'    => $doctorId,
            'cover_image'  => !empty($body['cover_image']) ? trim($body['cover_image']) : null,
            'summary'      => trim($body['summary']),
            'content'      => trim($body['content']),
            'badge_text'   => $badgeText,
            'is_published' => isset($body['is_published']) ? (int)$body['is_published'] : 1
        ]);

        $postId = (int)$pdo->lastInsertId();

        // Broadcast instant push notifications to all patient mobile apps
        if (!empty($body['notify_patients'])) {
            try {
                $patients = $pdo->query("SELECT user_id FROM patients")->fetchAll(PDO::FETCH_COLUMN);
                $notifTitle = ($badgeText ? "{$badgeText} • " : "") . trim($body['title']);
                $notifMsg = trim($body['summary']);

                foreach ($patients as $pUid) {
                    NotificationService::dispatch(
                        (int)$pUid,
                        'ADMIN_POST_ANNOUNCEMENT',
                        'POST_' . $postId . '_' . $pUid . '_' . time(),
                        'PUSH',
                        $notifTitle,
                        $notifMsg,
                        [
                            'type'      => 'post',
                            'post_id'   => (string)$postId,
                            'category'  => $category,
                            'doctor_id' => (string)($doctorId ?? '')
                        ]
                    );
                }
            } catch (\Throwable $e) {
                error_log("[POST BROADCAST ERROR] " . $e->getMessage());
            }
        }

        Response::success(['id' => $postId], 'Post created and broadcasted successfully.', 201);
    }

    /**
     * Admin endpoint: Update post
     */
    public function updatePost(array $params): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $id = (int)($params['id'] ?? 0);
        $body = $this->getJsonBody();

        $pdo = Database::getConnection();

        // Fast publish status toggle
        if (isset($body['is_published']) && !isset($body['title'])) {
            $pdo->prepare("UPDATE admin_posts SET is_published = :pub, updated_at = NOW() WHERE id = :id")
                ->execute(['pub' => (int)$body['is_published'], 'id' => $id]);
            Response::success(null, 'Post status updated successfully.');
            return;
        }

        Validator::make($body)->required(['title', 'summary', 'content'])->validateOrAbort();

        $doctorId = null;
        if (!empty($body['doctor_id']) && (int)$body['doctor_id'] > 0) {
            $cStmt = $pdo->prepare("SELECT id FROM doctors WHERE id = :did LIMIT 1");
            $cStmt->execute(['did' => (int)$body['doctor_id']]);
            if ($cStmt->fetchColumn()) {
                $doctorId = (int)$body['doctor_id'];
            }
        }

        $category = in_array($body['category'] ?? '', ['DOCTOR_ANNOUNCEMENT', 'SPECIAL_OFFER', 'HEALTH_TIPS', 'GENERAL_NOTICE'], true)
            ? $body['category']
            : 'DOCTOR_ANNOUNCEMENT';

        $stmt = $pdo->prepare("
            UPDATE admin_posts
            SET title = :title, category = :category, doctor_id = :doctor_id,
                cover_image = :cover_image, summary = :summary, content = :content,
                badge_text = :badge_text, is_published = :is_published, updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'title'        => trim($body['title']),
            'category'     => $category,
            'doctor_id'    => $doctorId,
            'cover_image'  => !empty($body['cover_image']) ? trim($body['cover_image']) : null,
            'summary'      => trim($body['summary']),
            'content'      => trim($body['content']),
            'badge_text'   => trim($body['badge_text'] ?? ''),
            'is_published' => isset($body['is_published']) ? (int)$body['is_published'] : 1,
            'id'           => $id
        ]);

        Response::success(null, 'Post updated successfully.');
    }

    /**
     * Admin endpoint: Delete post
     */
    public function deletePost(array $params): void {
        RoleMiddleware::requireRole('PLATFORM_ADMIN');
        $id = (int)($params['id'] ?? 0);
        $pdo = Database::getConnection();
        $pdo->prepare("DELETE FROM admin_posts WHERE id = :id")->execute(['id' => $id]);
        Response::success(null, 'Post deleted successfully.');
    }
}