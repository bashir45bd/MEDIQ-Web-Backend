<?php
declare(strict_types=1);

/**
 * MEDIQ REST API Front Controller
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Middleware\CorsMiddleware;
use App\Helpers\Router;
use App\Helpers\Response;

// Handle CORS preflight and origin headers
CorsMiddleware::handle();

// Set global error handling (never leak raw PHP notices/warnings in JSON API)
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log("[PHP Error {$errno}] {$errstr} in {$errfile}:{$errline}");
    return true;
});

set_exception_handler(function (Throwable $e): void {
    error_log("[UNCAUGHT EXCEPTION] " . $e->getMessage() . "\n" . $e->getTraceAsString());
    Response::error('SERVER_ERROR', 'An internal server error occurred.', 500);
});

// Load route mappings
require_once __DIR__ . '/../routes/api.php';

// Dispatch request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

Router::dispatch($method, $uri);
