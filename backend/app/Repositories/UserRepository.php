<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository extends BaseRepository {
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPhone(string $phone): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = :phone LIMIT 1");
        $stmt->execute(['phone' => $phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO users (role, full_name, email, phone, password_hash, status, created_at)
            VALUES (:role, :full_name, :email, :phone, :password_hash, :status, NOW())
        ");
        $stmt->execute([
            'role'          => $data['role'],
            'full_name'     => $data['full_name'],
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'],
            'password_hash' => $data['password_hash'],
            'status'        => $data['status'] ?? 'ACTIVE'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateLastLogin(int $id): void {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
