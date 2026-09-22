<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class DoctorRepository extends BaseRepository {
    public function search(array $filters): array {
        $sql = "
            SELECT 
                d.id, d.user_id, u.full_name AS doctor_name, u.profile_photo,
                d.bmdc_number, d.designation, d.experience_years,
                d.consultation_fee, d.follow_up_fee, d.follow_up_validity_days,
                d.bio, d.languages, d.rating_avg, d.rating_count,
                GROUP_CONCAT(DISTINCT s.name ORDER BY ds.is_primary DESC SEPARATOR ', ') AS specialties,
                GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') AS chamber_names
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            LEFT JOIN doctor_specialties ds ON d.id = ds.doctor_id
            LEFT JOIN specialties s ON ds.specialty_id = s.id
            LEFT JOIN doctor_chambers c ON d.id = c.doctor_id AND c.is_active = 1
            WHERE d.verification_status = 'VERIFIED' AND u.status = 'ACTIVE'
        ";

        $params = [];

        if (!empty($filters['query'])) {
            $sql .= " AND (u.full_name LIKE :q OR d.designation LIKE :q OR d.bio LIKE :q)";
            $params['q'] = '%' . $filters['query'] . '%';
        }

        if (!empty($filters['specialty_id'])) {
            $sql .= " AND ds.specialty_id = :spec_id";
            $params['spec_id'] = (int)$filters['specialty_id'];
        }

        if (!empty($filters['city_id'])) {
            $sql .= " AND c.city_id = :city_id";
            $params['city_id'] = (int)$filters['city_id'];
        }

        if (!empty($filters['area_id'])) {
            $sql .= " AND c.area_id = :area_id";
            $params['area_id'] = (int)$filters['area_id'];
        }

        $sql .= " GROUP BY d.id ORDER BY d.rating_avg DESC, d.experience_years DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch active chambers for each doctor with schedules
        foreach ($doctors as &$doc) {
            $chStmt = $this->db->prepare("
                SELECT c.id, c.name, c.address, c.contact_phone, c.room_number, c.token_prefix,
                       ci.name AS city_name, ar.name AS area_name
                FROM doctor_chambers c
                JOIN cities ci ON c.city_id = ci.id
                JOIN areas ar ON c.area_id = ar.id
                WHERE c.doctor_id = :did AND c.is_active = 1
            ");
            $chStmt->execute(['did' => $doc['id']]);
            $doc['chambers'] = $chStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($doc['chambers'] as &$ch) {
                $schStmt = $this->db->prepare("
                    SELECT id, chamber_id, day_of_week, start_time, end_time, slot_duration_minutes, default_capacity, is_active
                    FROM doctor_schedules
                    WHERE doctor_id = :did AND chamber_id = :cid AND is_active = 1
                    ORDER BY day_of_week ASC
                ");
                $schStmt->execute(['did' => $doc['id'], 'cid' => $ch['id']]);
                $ch['schedules'] = $schStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return $doctors;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                d.*, u.full_name AS doctor_name, u.email, u.phone, u.profile_photo
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doc) return null;

        // Attach specialties
        $spStmt = $this->db->prepare("
            SELECT s.id, s.name, s.slug, ds.is_primary
            FROM doctor_specialties ds
            JOIN specialties s ON ds.specialty_id = s.id
            WHERE ds.doctor_id = :did
        ");
        $spStmt->execute(['did' => $id]);
        $doc['specialties'] = $spStmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach chambers with schedules
        $chStmt = $this->db->prepare("
            SELECT c.*, ci.name AS city_name, ar.name AS area_name
            FROM doctor_chambers c
            JOIN cities ci ON c.city_id = ci.id
            JOIN areas ar ON c.area_id = ar.id
            WHERE c.doctor_id = :did AND c.is_active = 1
        ");
        $chStmt->execute(['did' => $id]);
        $doc['chambers'] = $chStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($doc['chambers'] as &$ch) {
            $schStmt = $this->db->prepare("
                SELECT id, chamber_id, day_of_week, start_time, end_time, slot_duration_minutes, default_capacity, is_active
                FROM doctor_schedules
                WHERE doctor_id = :did AND chamber_id = :cid AND is_active = 1
                ORDER BY day_of_week ASC
            ");
            $schStmt->execute(['did' => $id, 'cid' => $ch['id']]);
            $ch['schedules'] = $schStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $doc;
    }

    public function findByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM doctors WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getChambersByDoctor(int $doctorId): array {
        $stmt = $this->db->prepare("
            SELECT c.*, ci.name AS city_name, ar.name AS area_name
            FROM doctor_chambers c
            JOIN cities ci ON c.city_id = ci.id
            JOIN areas ar ON c.area_id = ar.id
            WHERE c.doctor_id = :did
            ORDER BY c.name ASC
        ");
        $stmt->execute(['did' => $doctorId]);
        $chambers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($chambers as &$ch) {
            $schStmt = $this->db->prepare("
                SELECT id, chamber_id, day_of_week, start_time, end_time, slot_duration_minutes, default_capacity, is_active
                FROM doctor_schedules
                WHERE doctor_id = :did AND chamber_id = :cid
                ORDER BY day_of_week ASC
            ");
            $schStmt->execute(['did' => $doctorId, 'cid' => $ch['id']]);
            $ch['schedules'] = $schStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $chambers;
    }

    public function getTodayAvailability(int $doctorId, int $chamberId, string $date): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, c.total_capacity, c.booked_count, (c.total_capacity - c.booked_count) AS remaining_slots
            FROM doctor_daily_availability a
            JOIN doctor_capacity c ON a.doctor_id = c.doctor_id AND a.chamber_id = c.chamber_id AND a.appointment_date = c.appointment_date
            WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
            LIMIT 1
        ");
        $stmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function updateAvailabilityStatus(int $doctorId, int $chamberId, string $date, string $status, ?string $statusNote = null, ?string $liveStatus = null, int $delayMinutes = 0): void {
        $validLiveStatuses = ['AVAILABLE', 'CONSULTING', 'SHORT_BREAK', 'DELAYED', 'EMERGENCY_DELAY', 'FINISHED'];
        if ($liveStatus !== null && !in_array($liveStatus, $validLiveStatuses, true)) {
            if ($liveStatus === 'OFF' || $liveStatus === 'CLOSED') {
                $liveStatus = 'FINISHED';
            } elseif ($liveStatus === 'PAUSED' || $liveStatus === 'BREAK') {
                $liveStatus = 'SHORT_BREAK';
            } else {
                $liveStatus = null;
            }
        }
        $lst = $liveStatus ?: ($status === 'ON' ? 'AVAILABLE' : ($status === 'PAUSED' ? 'SHORT_BREAK' : 'FINISHED'));
        $stmt = $this->db->prepare("
            INSERT INTO doctor_daily_availability (doctor_id, chamber_id, appointment_date, status, doctor_live_status, status_note, delay_minutes, last_status_updated_at)
            VALUES (:did, :cid, :dt, :st, :lst, :nt, :dm, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                doctor_live_status = VALUES(doctor_live_status), 
                status_note = VALUES(status_note), 
                delay_minutes = CASE WHEN VALUES(delay_minutes) > 0 THEN VALUES(delay_minutes) ELSE delay_minutes END,
                last_status_updated_at = NOW()
        ");
        $stmt->execute([
            'did' => $doctorId,
            'cid' => $chamberId,
            'dt'  => $date,
            'st'  => $status,
            'lst' => $lst,
            'nt'  => $statusNote,
            'dm'  => $delayMinutes
        ]);
    }

    public function updateDoctorLiveStatus(int $doctorId, int $chamberId, string $date, string $liveStatus, int $delayMinutes = 0, ?string $note = null): void {
        $stmt = $this->db->prepare("
            UPDATE doctor_daily_availability 
            SET doctor_live_status = :lst, delay_minutes = :dm, status_note = :nt, last_status_updated_at = NOW()
            WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
        ");
        $stmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'lst' => $liveStatus, 'dm' => $delayMinutes, 'nt' => $note]);
    }
}
