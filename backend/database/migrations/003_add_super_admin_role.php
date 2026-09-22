<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/autoload.php';

use App\Helpers\Database;

try {
    $pdo = Database::getConnection();
    echo "Updating users and audit_logs tables to support SUPER_ADMIN role...\n";
    
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('PATIENT', 'DOCTOR', 'PLATFORM_ADMIN', 'SUPER_ADMIN') NOT NULL");
    $pdo->exec("ALTER TABLE audit_logs MODIFY COLUMN actor_role ENUM('PATIENT', 'DOCTOR', 'PLATFORM_ADMIN', 'SUPER_ADMIN', 'SYSTEM') NOT NULL");
    $pdo->exec("UPDATE users SET role = 'SUPER_ADMIN' WHERE phone = '+8801700000001'");
    
    echo "SUCCESS: SUPER_ADMIN role added and initial admin upgraded to SUPER_ADMIN.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
