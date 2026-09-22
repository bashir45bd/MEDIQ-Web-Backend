<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ConsultationRepository;
use App\Middleware\RoleMiddleware;
use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Validators\Validator;

class ConsultationController extends BaseController {
    private ConsultationRepository $consultRepo;

    public function __construct() {
        $this->consultRepo = new ConsultationRepository();
    }

    public function save(): void {
        $user = RoleMiddleware::requireRole('DOCTOR');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id'])
            ->validateOrAbort();

        $body['actor_user_id'] = $user['id'];
        $result = $this->consultRepo->saveConsultation($body, (int)$user['doctor_id']);
        Response::success($result, 'Consultation and digital prescription saved successfully.', 201);
    }

    public function showByAppointment(array $params): void {
        $user = AuthMiddleware::handle();
        $aptId = (int)($params['appointment_id'] ?? 0);

        $consultation = $this->consultRepo->findByAppointment($aptId);
        if (!$consultation) {
            Response::error('NOT_FOUND', 'No consultation record found for this appointment.', 404);
        }

        // IDOR Check
        if ($user['role'] === 'PATIENT' && (int)$consultation['patient_id'] !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied to this consultation record.', 403);
        }
        if ($user['role'] === 'DOCTOR' && (int)$consultation['doctor_id'] !== (int)$user['doctor_id']) {
            Response::error('FORBIDDEN', 'Access denied to this consultation record.', 403);
        }

        Response::success($consultation);
    }
}
