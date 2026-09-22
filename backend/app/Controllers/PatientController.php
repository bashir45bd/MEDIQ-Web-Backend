<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PatientRepository;
use App\Middleware\RoleMiddleware;
use App\Helpers\Response;
use App\Validators\Validator;

class PatientController extends BaseController {
    private PatientRepository $patientRepo;

    public function __construct() {
        $this->patientRepo = new PatientRepository();
    }

    public function getProfile(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $patient = $this->patientRepo->findById((int)$user['patient_id']);
        Response::success($patient);
    }

    public function updateProfile(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $body = $this->getJsonBody();

        $this->patientRepo->updateProfile((int)$user['patient_id'], $body);
        Response::success(null, 'Patient profile updated successfully.');
    }

    public function getFamily(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $family = $this->patientRepo->getFamilyMembers((int)$user['patient_id']);
        Response::success($family);
    }

    public function addFamily(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['full_name', 'relationship'])
            ->in('relationship', ['FATHER', 'MOTHER', 'SPOUSE', 'CHILD', 'SIBLING', 'OTHER'])
            ->validateOrAbort();

        $memberId = $this->patientRepo->addFamilyMember((int)$user['patient_id'], $body);
        Response::success(['family_member_id' => $memberId], 'Family member added successfully.', 201);
    }

    public function deleteFamily(array $params): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $mid = (int)($params['id'] ?? 0);
        $deleted = $this->patientRepo->deleteFamilyMember((int)$user['patient_id'], $mid);
        if (!$deleted) {
            Response::error('FAMILY_MEMBER_NOT_FOUND', 'Family member not found.', 404);
        }
        Response::success(null, 'Family member removed successfully.');
    }

    public function getFavorites(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $favs = $this->patientRepo->getFavorites((int)$user['patient_id']);
        Response::success($favs);
    }

    public function addFavorite(array $params): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $doctorId = (int)($params['id'] ?? 0);
        $this->patientRepo->addFavorite((int)$user['patient_id'], $doctorId);
        Response::success(null, 'Doctor added to favorites.');
    }

    public function removeFavorite(array $params): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $doctorId = (int)($params['id'] ?? 0);
        $this->patientRepo->removeFavorite((int)$user['patient_id'], $doctorId);
        Response::success(null, 'Doctor removed from favorites.');
    }

    public function getMedicalRecords(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $records = $this->patientRepo->getMedicalRecords((int)$user['patient_id']);
        Response::success($records);
    }
}
