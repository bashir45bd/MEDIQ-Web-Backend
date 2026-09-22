<?php
declare(strict_types=1);

namespace App\Helpers;

class HashHelper {
    public static function make(string $plainPassword): string {
        return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function hash(string $plainPassword): string {
        return self::make($plainPassword);
    }

    public static function verify(string $plainPassword, string $hash): bool {
        return password_verify($plainPassword, $hash);
    }
}
