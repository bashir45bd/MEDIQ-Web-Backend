<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

class ConsultationRepository extends BaseRepository {
    public function saveConsultation(array $data, int $doctorId): array {
        $this->db->beginTransaction();

        try {
            $appointmentId = (int)$data['appointment_id'];

            // Fetch patient ID from appointment
            $aptStmt = $this->db->prepare("SELECT patient_id FROM appointments WHERE id = :aid LIMIT 1");
            $aptStmt->execute(['aid' => $appointmentId]);
            $patientId = (int)$aptStmt->fetchColumn();

            // 1. Insert Consultation
            $cStmt = $this->db->prepare("
                INSERT INTO consultations (appointment_id, doctor_id, patient_id, symptoms, clinical_notes, follow_up_date, created_at)
                VALUES (:aid, :did, :pid, :sym, :notes, :fdate, NOW())
                ON DUPLICATE KEY UPDATE symptoms = :sym, clinical_notes = :notes, follow_up_date = :fdate, updated_at = NOW()
            ");
            $cStmt->execute([
                'aid'   => $appointmentId,
                'did'   => $doctorId,
                'pid'   => $patientId,
                'sym'   => $data['symptoms'] ?? null,
                'notes' => $data['clinical_notes'] ?? null,
                'fdate' => $data['follow_up_date'] ?? null
            ]);

            $consultationId = (int)($this->db->lastInsertId() ?: $this->getConsultationIdByAppointment($appointmentId));

            // 2. Insert Diagnoses
            if (!empty($data['diagnoses'])) {
                $this->db->prepare("DELETE FROM diagnoses WHERE consultation_id = :cid")->execute(['cid' => $consultationId]);
                $dStmt = $this->db->prepare("
                    INSERT INTO diagnoses (consultation_id, condition_name, icd10_code, severity, type)
                    VALUES (:cid, :cond, :icd, :sev, :type)
                ");
                foreach ($data['diagnoses'] as $diag) {
                    $dStmt->execute([
                        'cid'  => $consultationId,
                        'cond' => $diag['condition_name'],
                        'icd'  => $diag['icd10_code'] ?? null,
                        'sev'  => $diag['severity'] ?? 'MODERATE',
                        'type' => $diag['type'] ?? 'PRIMARY'
                    ]);
                }
            }

            // 3. Insert Prescription
            $rxCode = 'RX-' . date('Ymd') . '-' . sprintf('%05d', $consultationId);
            $verifToken = hash('sha256', "MEDIQ:{$consultationId}:{$appointmentId}:" . microtime());

            $rxStmt = $this->db->prepare("
                INSERT INTO prescriptions (consultation_id, prescription_code, verification_token, is_signed, created_at)
                VALUES (:cid, :code, :vtok, 1, NOW())
                ON DUPLICATE KEY UPDATE is_signed = 1
            ");
            $rxStmt->execute([
                'cid'  => $consultationId,
                'code' => $rxCode,
                'vtok' => $verifToken
            ]);

            $prescriptionId = (int)($this->db->lastInsertId() ?: $this->getPrescriptionIdByConsultation($consultationId));

            // 4. Insert Prescription Items
            if (!empty($data['medicines'])) {
                $this->db->prepare("DELETE FROM prescription_items WHERE prescription_id = :rid")->execute(['rid' => $prescriptionId]);
                $mStmt = $this->db->prepare("
                    INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, instructions)
                    VALUES (:rid, :med, :dos, :freq, :dur, :inst)
                ");
                foreach ($data['medicines'] as $med) {
                    $mStmt->execute([
                        'rid'  => $prescriptionId,
                        'med'  => $med['medicine_name'],
                        'dos'  => $med['dosage'] ?? '1 tablet',
                        'freq' => $med['frequency'] ?? '1+0+1',
                        'dur'  => $med['duration'] ?? '7 days',
                        'inst' => $med['instructions'] ?? 'After meals'
                    ]);
                }
            }

            // 5. Insert Investigations
            if (!empty($data['investigations'])) {
                $this->db->prepare("DELETE FROM investigations WHERE consultation_id = :cid")->execute(['cid' => $consultationId]);
                $iStmt = $this->db->prepare("
                    INSERT INTO investigations (consultation_id, test_name, notes, urgent)
                    VALUES (:cid, :tname, :notes, :urg)
                ");
                foreach ($data['investigations'] as $inv) {
                    $iStmt->execute([
                        'cid'   => $consultationId,
                        'tname' => is_array($inv) ? $inv['test_name'] : (string)$inv,
                        'notes' => is_array($inv) ? ($inv['notes'] ?? null) : null,
                        'urg'   => is_array($inv) ? (int)($inv['urgent'] ?? 0) : 0
                    ]);
                }
            }

            // 6. Insert Advice
            if (!empty($data['advice'])) {
                $this->db->prepare("DELETE FROM medical_advice WHERE consultation_id = :cid")->execute(['cid' => $consultationId]);
                $aStmt = $this->db->prepare("INSERT INTO medical_advice (consultation_id, advice_text) VALUES (:cid, :adv)");
                foreach ($data['advice'] as $adv) {
                    $aStmt->execute([
                        'cid' => $consultationId,
                        'adv' => is_array($adv) ? $adv['advice_text'] : (string)$adv
                    ]);
                }
            }

            // 7. Auto-create entry in medical_records for patient timeline
            $this->db->prepare("
                INSERT INTO medical_records (patient_id, record_type, title, file_path, consultation_id, uploaded_by, created_at)
                VALUES (:pid, 'PRESCRIPTION', :title, :fpath, :cid, :uid, NOW())
            ")->execute([
                'pid'   => $patientId,
                'title' => "Prescription #{$rxCode}",
                'fpath' => "/api/v1/prescriptions/{$prescriptionId}/pdf",
                'cid'   => $consultationId,
                'uid'   => (int)$data['actor_user_id']
            ]);

            $this->db->commit();

            return [
                'consultation_id'   => $consultationId,
                'prescription_id'   => $prescriptionId,
                'prescription_code' => $rxCode,
                'verification_token'=> $verifToken
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findByAppointment(int $appointmentId): ?array {
        $stmt = $this->db->prepare("
            SELECT c.*, d.designation, d.bmdc_number, udoc.full_name AS doctor_name,
                   upat.full_name AS patient_name, upat.phone AS patient_phone,
                   p.gender, p.dob, p.blood_group,
                   ch.name AS chamber_name, ch.address AS chamber_address,
                   rx.id AS prescription_id, rx.prescription_code, rx.verification_token, rx.is_signed
            FROM consultations c
            JOIN appointments a ON c.appointment_id = a.id
            JOIN doctors d ON c.doctor_id = d.id
            JOIN users udoc ON d.user_id = udoc.id
            JOIN patients p ON c.patient_id = p.id
            JOIN users upat ON p.user_id = upat.id
            JOIN doctor_chambers ch ON a.chamber_id = ch.id
            LEFT JOIN prescriptions rx ON c.id = rx.consultation_id
            WHERE c.appointment_id = :aid LIMIT 1
        ");
        $stmt->execute(['aid' => $appointmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $cid = (int)$row['id'];

        // Diagnoses
        $dStmt = $this->db->prepare("SELECT * FROM diagnoses WHERE consultation_id = :cid");
        $dStmt->execute(['cid' => $cid]);
        $row['diagnoses'] = $dStmt->fetchAll(PDO::FETCH_ASSOC);

        // Medicines
        if (!empty($row['prescription_id'])) {
            $mStmt = $this->db->prepare("SELECT * FROM prescription_items WHERE prescription_id = :rid");
            $mStmt->execute(['rid' => (int)$row['prescription_id']]);
            $row['medicines'] = $mStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $row['medicines'] = [];
        }

        // Investigations
        $iStmt = $this->db->prepare("SELECT * FROM investigations WHERE consultation_id = :cid");
        $iStmt->execute(['cid' => $cid]);
        $row['investigations'] = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        // Advice
        $aStmt = $this->db->prepare("SELECT * FROM medical_advice WHERE consultation_id = :cid");
        $aStmt->execute(['cid' => $cid]);
        $row['advice'] = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        return $row;
    }

    private function getConsultationIdByAppointment(int $aid): ?int {
        $stmt = $this->db->prepare("SELECT id FROM consultations WHERE appointment_id = :aid LIMIT 1");
        $stmt->execute(['aid' => $aid]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function getPrescriptionIdByConsultation(int $cid): ?int {
        $stmt = $this->db->prepare("SELECT id FROM prescriptions WHERE consultation_id = :cid LIMIT 1");
        $stmt->execute(['cid' => $cid]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }
}
