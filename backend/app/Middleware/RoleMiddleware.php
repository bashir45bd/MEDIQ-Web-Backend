<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;

class RoleMiddleware {
    public static function requireRole(string ...$allowedRoles): array {
        $user = AuthMiddleware::handle();
        $role = $user['role'] ?? '';

        // SUPER_ADMIN inherits all PLATFORM_ADMIN capabilities
        $isAllowed = in_array($role, $allowedRoles, true)
            || ($role === 'SUPER_ADMIN' && in_array('PLATFORM_ADMIN', $allowedRoles, true));

        if (!$isAllowed) {
            Response::error('FORBIDDEN', 'You do not have permission to access this resource.', 403);
        }

        return $user;
    }
}
