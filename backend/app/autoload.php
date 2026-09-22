<?php
declare(strict_types=1);

/**
 * MEDIQ Custom PSR-4 Autoloader
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load .env automatically
App\Helpers\Env::load(dirname(__DIR__) . '/.env');

// Set standardized timezone
date_default_timezone_set(App\Helpers\Env::get('APP_TIMEZONE', 'Asia/Dhaka'));
