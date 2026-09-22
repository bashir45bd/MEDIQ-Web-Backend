<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class PatientRepository extends BaseRepository {
    public function findByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name, u.email, u.phone, u.profile_photo
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = :uid LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $patientId): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name, u.email, u.phone, u.profile_photo
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :pid LIMIT 1
        ");
        $stmt->execute(['pid' => $patientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateProfile(int $patientId, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE patients
            SET dob = :dob, gender = :gender, blood_group = :bg, address = :addr,
                emergency_contact_name = :ecn, emergency_contact_phone = :ecp
            WHERE id = :pid
        ");
        $stmt->execute([
            'dob'    => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? 'OTHER',
            'bg'     => $data['blood_group'] ?? null,
            'addr'   => $data['address'] ?? null,
            'ecn'    => $data['emergency_contact_name'] ?? null,
            'ecp'    => $data['emergency_contact_phone'] ?? null,
            'pid'    => $patientId
        ]);

        if (!empty($data['full_name']) || !empty($data['email'])) {
            $uStmt = $this->db->prepare("
                UPDATE users u
                JOIN patients p ON p.user_id = u.id
                SET u.full_name = COALESCE(:name, u.full_name),
                    u.email = COALESCE(:email, u.email),
                    u.updated_at = NOW()
                WHERE p.id = :pid
            ");
            $uStmt->execute([
                'name'  => !empty($data['full_name']) ? $data['full_name'] : null,
                'email' => !empty($data['email']) ? $data['email'] : null,
                'pid'   => $patientId
            ]);
        }
    }

    public function getFamilyMembers(int $patientId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM patient_family_members
            WHERE patient_id = :pid ORDER BY id ASC
        ");
        $stmt->execute(['pid' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addFamilyMember(int $patientId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO patient_family_members (patient_id, full_name, relationship, dob, gender, blood_group, emergency_info, created_at)
            VALUES (:pid, :name, :rel, :dob, :gender, :bg, :info, NOW())
        ");
        $stmt->execute([
            'pid'    => $patientId,
            'name'   => $data['full_name'],
            'rel'    => $data['relationship'],
            'dob'    => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? 'OTHER',
            'bg'     => $data['blood_group'] ?? null,
            'info'   => $data['emergency_info'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteFamilyMember(int $patientId, int $memberId): bool {
        $stmt = $this->db->prepare("DELETE FROM patient_family_members WHERE id = :mid AND patient_id = :pid");
        $stmt->execute(['mid' => $memberId, 'pid' => $patientId]);
        return $stmt->rowCount() > 0;
    }

    public function getFavorites(int $patientId): array {
        $stmt = $this->db->prepare("
            SELECT f.created_at AS favorited_at, d.id AS doctor_id, u.full_name AS doctor_name,
                   d.designation, d.consultation_fee, d.rating_avg
            FROM favorites f
            JOIN doctors d ON f.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE f.patient_id = :pid
            ORDER BY f.created_at DESC
        ");
        $stmt->execute(['pid' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addFavorite(int $patientId, int $doctorId): void {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO favorites (patient_id, doctor_id, created_at)
            VALUES (:pid, :did, NOW())
        ");
        $stmt->execute(['pid' => $patientId, 'did' => $doctorId]);
    }

    public function removeFavorite(int $patientId, int $doctorId): void {
        $stmt = $this->db->prepare("DELETE FROM favorites WHERE patient_id = :pid AND doctor_id = :did");
        $stmt->execute(['pid' => $patientId, 'did' => $doctorId]);
    }

    public function getMedicalRecords(int $patientId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.appointment_id, u.full_name AS uploaded_by_name
            FROM medical_records r
            LEFT JOIN consultations c ON r.consultation_id = c.id
            JOIN users u ON r.uploaded_by = u.id
            WHERE r.patient_id = :pid
            ORDER BY r.created_at DESC
        ");
        $stmt->execute(['pid' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
