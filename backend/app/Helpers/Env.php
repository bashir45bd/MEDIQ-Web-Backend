<?php
declare(strict_types=1);

namespace App\Helpers;

class Env {
    private static array $variables = [];
    private static bool $loaded = false;

    public static function load(string $path): void {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Strip surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                self::$variables[$key] = $value;
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed {
        if (!self::$loaded) {
            self::load(dirname(__DIR__, 2) . '/.env');
        }

        if (array_key_exists($key, self::$variables)) {
            $val = self::$variables[$key];
            if (strtolower($val) === 'true') return true;
            if (strtolower($val) === 'false') return false;
            if (strtolower($val) === 'null') return null;
            return $val;
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return $envVal;
        }

        return $default;
    }
}
