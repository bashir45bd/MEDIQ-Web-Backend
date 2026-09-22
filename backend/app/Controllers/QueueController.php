<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\QueueService;
use App\Services\WaitTimeService;
use App\Middleware\RoleMiddleware;
use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Helpers\Database;
use App\Validators\Validator;
use PDO;

class QueueController extends BaseController {
    private QueueService $queueService;

    public function __construct() {
        $this->queueService = new QueueService();
    }

    public function patientLiveQueue(array $params): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $aptId = (int)($params['appointment_id'] ?? 0);

        $pdo = Database::getConnection();
        \App\Controllers\AppointmentController::expirePastAppointments($pdo);
        $check = $pdo->prepare("SELECT patient_id FROM appointments WHERE id = :id");
        $check->execute(['id' => $aptId]);
        $ownerPid = $check->fetchColumn();

        if (!$ownerPid || (int)$ownerPid !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied to this appointment live queue.', 403);
        }

        $queueData = WaitTimeService::calculateForAppointment($aptId);
        Response::success($queueData);
    }

    public function checkIn(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id'])
            ->validateOrAbort();

        $aptId = (int)$body['appointment_id'];
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :id");
        $stmt->execute(['id' => $aptId]);
        $apt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$apt || (int)$apt['patient_id'] !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied to this appointment.', 403);
        }

        if ($apt['status'] !== 'BOOKED') {
            Response::error('ALREADY_CHECKED_IN', "Appointment is currently {$apt['status']}.", 409);
        }

        $method = !empty($body['qr_code']) ? 'QR' : 'MANUAL';

        $pdo->beginTransaction();
        $up = $pdo->prepare("
            UPDATE appointments 
            SET status = 'CHECKED_IN', check_in_time = NOW(), check_in_method = :m
            WHERE id = :id
        ");
        $up->execute(['m' => $method, 'id' => $aptId]);

        $insCheck = $pdo->prepare("
            INSERT INTO check_ins (appointment_id, qr_code_hash, check_in_method, checked_in_at)
            VALUES (:aid, :hsh, :m, NOW())
        ");
        $insCheck->execute([
            'aid' => $aptId,
            'hsh' => !empty($body['qr_code']) ? hash('sha256', $body['qr_code']) : null,
            'm'   => $method
        ]);

        $pdo->commit();

        Response::success(null, 'Check-in confirmed. You are now placed in the chamber queue.');
    }

    public function doctorTodayQueue(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $doctorId = (int)$user['doctor_id'];
        $date = $_GET['date'] ?? date('Y-m-d');
        $chamberId = !empty($_GET['chamber_id']) ? (int)$_GET['chamber_id'] : null;

        $pdo = Database::getConnection();

        // Default to doctor's primary chamber if not specified
        if (!$chamberId) {
            $cStmt = $pdo->prepare("SELECT id FROM doctor_chambers WHERE doctor_id = :did AND is_active = 1 LIMIT 1");
            $cStmt->execute(['did' => $doctorId]);
            $chamberId = (int)$cStmt->fetchColumn();
        }

        $stmt = $pdo->prepare("
            SELECT a.id, a.appointment_number, a.token_display, a.token_number, a.patient_type,
                   a.status, a.check_in_time, a.consultation_start_time,
                   p.gender, p.blood_group, p.dob,
                   u.full_name AS patient_name, u.phone AS patient_phone
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE a.doctor_id = :did AND a.chamber_id = :cid AND a.appointment_date = :dt
            ORDER BY a.token_number ASC
        ");
        $stmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group into buckets
        $grouped = [
            'current'   => null,
            'called'    => null,
            'waiting'   => [],
            'completed' => [],
            'skipped'   => [],
            'no_show'   => []
        ];

        foreach ($appointments as $apt) {
            if ($apt['status'] === 'CONSULTING') {
                $grouped['current'] = $apt;
            } elseif ($apt['status'] === 'CALLED') {
                $grouped['called'] = $apt;
            } elseif (in_array($apt['status'], ['BOOKED', 'CHECKED_IN', 'WAITING'], true)) {
                $grouped['waiting'][] = $apt;
            } elseif ($apt['status'] === 'COMPLETED') {
                $grouped['completed'][] = $apt;
            } elseif ($apt['status'] === 'SKIPPED') {
                $grouped['skipped'][] = $apt;
            } elseif ($apt['status'] === 'NO_SHOW') {
                $grouped['no_show'][] = $apt;
            }
        }

        $availStmt = $pdo->prepare("SELECT status, doctor_live_status, presence_status, is_booking_open, status_note FROM doctor_daily_availability WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt LIMIT 1");
        $availStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
        $avail = $availStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'status'             => 'ON',
            'doctor_live_status' => 'AVAILABLE',
            'presence_status'    => 'IN_CHAMBER',
            'is_booking_open'    => 1,
            'status_note'        => null
        ];

        Response::success([
            'chamber_id'   => $chamberId,
            'date'         => $date,
            'availability' => $avail,
            'queue'        => $grouped
        ]);
    }

    public function callNext(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['chamber_id'])
            ->validateOrAbort();

        $doctorId  = (int)$user['doctor_id'];
        $chamberId = (int)$body['chamber_id'];
        $date      = $body['date'] ?? date('Y-m-d');

        $calledPatient = $this->queueService->callNext($doctorId, $chamberId, $date, (int)$user['id']);
        Response::success($calledPatient, "Token {$calledPatient['token_display']} called successfully.");
    }

    public function startConsultation(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id'])
            ->validateOrAbort();

        $this->queueService->startConsultation((int)$body['appointment_id'], (int)$user['doctor_id'], (int)$user['id']);
        Response::success(null, 'Consultation timer started.');
    }

    public function completeConsultation(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id'])
            ->validateOrAbort();

        $this->queueService->completeConsultation((int)$body['appointment_id'], (int)$user['doctor_id'], (int)$user['id']);
        Response::success(null, 'Consultation completed. Queue advanced.');
    }

    public function skipPatient(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id'])
            ->validateOrAbort();

        $this->queueService->skipPatient((int)$body['appointment_id'], (int)$user['doctor_id'], (int)$user['id']);
        Response::success(null, 'Patient skipped.');
    }

    public function markNoShow(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id'])
            ->validateOrAbort();

        $this->queueService->markNoShow((int)$body['appointment_id'], (int)$user['doctor_id'], (int)$user['id']);
        Response::success(null, 'Patient marked as No-Show.');
    }

    public function broadcastDelay(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['chamber_id', 'delay_minutes'])
            ->numeric('delay_minutes')
            ->validateOrAbort();

        $doctorId     = (int)$user['doctor_id'];
        $chamberId    = (int)$body['chamber_id'];
        $date         = $body['date'] ?? date('Y-m-d');
        $delayMinutes = (int)$body['delay_minutes'];
        $note         = $body['status_note'] ?? null;

        $this->queueService->broadcastDelay($doctorId, $chamberId, $date, $delayMinutes, $note, (int)$user['id']);
        Response::success(null, 'Doctor delay broadcasted to all waiting patients.');
    }

    public function recallSkipped(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)->required(['appointment_id'])->validateOrAbort();

        $apt = $this->queueService->recallSkippedPatient((int)$body['appointment_id'], (int)$user['doctor_id'], (int)$user['id']);
        Response::success($apt, "Token {$apt['token_display']} recalled successfully.");
    }

    public function swapQueue(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)->required(['appointment_id_1', 'appointment_id_2'])->validateOrAbort();

        $this->queueService->swapQueuePositions((int)$body['appointment_id_1'], (int)$body['appointment_id_2'], (int)$user['doctor_id'], (int)$user['id']);
        Response::success(null, 'Queue order swapped successfully.');
    }
}
