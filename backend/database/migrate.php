<?php
declare(strict_types=1);

/**
 * MEDIQ Database Migration & Seeder CLI Runner
 */

$host = '127.0.0.1';
$port = 3306;
$db   = 'mediq_db';
$user = 'root';
$pass = '';

echo "==============================================================\n";
echo "           MEDIQ Database Migration & Seed Runner             \n";
echo "==============================================================\n";

try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Ensure database exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db}`");
    echo "[OK] Connected to MySQL and selected database `{$db}`.\n";

    // 1. Run Migrations
    $migrationFile = __DIR__ . '/migrations/001_create_all_tables.sql';
    if (!file_exists($migrationFile)) {
        throw new RuntimeException("Migration file not found: {$migrationFile}");
    }
    echo "[RUN] Executing schema migration: 001_create_all_tables.sql ...\n";
    $sqlMigration = file_get_contents($migrationFile);
    $pdo->exec($sqlMigration);
    echo "[OK] Schema migration completed successfully.\n";

    // 2. Run Seeders
    $seederFile = __DIR__ . '/seeders/002_seed_data.sql';
    if (!file_exists($seederFile)) {
        throw new RuntimeException("Seeder file not found: {$seederFile}");
    }
    echo "[RUN] Executing seed data: 002_seed_data.sql ...\n";
    $sqlSeeder = file_get_contents($seederFile);
    $pdo->exec($sqlSeeder);
    echo "[OK] Seed data inserted successfully.\n";

    // 3. Verification Report
    echo "--------------------------------------------------------------\n";
    echo "                   Schema Verification Report                 \n";
    echo "--------------------------------------------------------------\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables created: " . count($tables) . "\n";

    $criticalTables = [
        'users', 'doctors', 'doctor_documents', 'patients', 'patient_family_members',
        'specialties', 'doctor_specialties', 'cities', 'areas', 'doctor_chambers',
        'doctor_schedules', 'doctor_daily_availability', 'doctor_capacity',
        'doctor_token_sequences', 'appointments', 'appointment_queue', 'queue_events',
        'check_ins', 'consultations', 'diagnoses', 'prescriptions', 'prescription_items',
        'medical_records', 'payments', 'platform_transactions', 'promotions', 'waitlists',
        'reviews', 'favorites', 'notifications', 'audit_logs', 'system_settings'
    ];

    $missing = [];
    foreach ($criticalTables as $tbl) {
        if (!in_array($tbl, $tables, true)) {
            $missing[] = $tbl;
        } else {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
            printf("  %-28s : %4d records\n", $tbl, $cnt);
        }
    }

    if (!empty($missing)) {
        echo "[ERROR] Missing critical tables: " . implode(', ', $missing) . "\n";
        exit(1);
    }

    echo "==============================================================\n";
    echo "[SUCCESS] MEDIQ Phase 1 Database is verified and ready!\n";
    echo "==============================================================\n";

} catch (Throwable $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
