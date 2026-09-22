<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

abstract class BaseController {
    protected function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    protected function getQueryParams(): array {
        return $_GET ?? [];
    }

    protected function getUser(): ?array {
        return AuthMiddleware::user();
    }

    protected function requireUser(): array {
        return AuthMiddleware::handle();
    }
}
