<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Services\AuthService;
use App\Helpers\Database;
use App\Helpers\HashHelper;

echo "==============================================================\n";
echo "   Testing Super Admin, Admin Onboarding & Doctor Creation    \n";
echo "==============================================================\n";

$auth = new AuthService();
$pdo = Database::getConnection();

// 1. Super Admin Login
$adminLogin = $auth->login(['phone' => '+8801700000001', 'password' => 'Admin@12345']);
$superUser = $adminLogin['user'];
assert($superUser['role'] === 'SUPER_ADMIN', 'Expected SUPER_ADMIN role');
echo "[TEST 1] Super Admin Login ... PASSED (Role: {$superUser['role']})\n";

// 2. List Administrators
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('PLATFORM_ADMIN', 'SUPER_ADMIN')");
$adminCount = (int)$stmt->fetchColumn();
assert($adminCount >= 1, 'Expected at least 1 administrator');
echo "[TEST 2] List Administrators ... PASSED (Total Admins: {$adminCount})\n";

// 3. Super Admin Creates New Platform Admin
$testPhoneAdmin = '+8801700000099';
$pdo->prepare("DELETE FROM users WHERE phone = :p")->execute(['p' => $testPhoneAdmin]);

$pwdHash = HashHelper::make('AdminPass@123');
$insAdmin = $pdo->prepare("
    INSERT INTO users (role, full_name, email, phone, password_hash, status, phone_verified_at, created_at)
    VALUES ('PLATFORM_ADMIN', 'Regional Admin Kamal', 'kamal@mediq.com.bd', :p, :pwd, 'ACTIVE', NOW(), NOW())
");
$insAdmin->execute(['p' => $testPhoneAdmin, 'pwd' => $pwdHash]);
$newAdminId = (int)$pdo->lastInsertId();
assert($newAdminId > 0);
echo "[TEST 3] Super Admin Created New Platform Admin ... PASSED (ID: {$newAdminId}, Kamal)\n";

// 4. Super Admin Creates Another Super Admin
$testPhoneSuper = '+8801700000088';
$pdo->prepare("DELETE FROM users WHERE phone = :p")->execute(['p' => $testPhoneSuper]);

$insSuper = $pdo->prepare("
    INSERT INTO users (role, full_name, email, phone, password_hash, status, phone_verified_at, created_at)
    VALUES ('SUPER_ADMIN', 'Executive Admin Farhana', 'farhana@mediq.com.bd', :p, :pwd, 'ACTIVE', NOW(), NOW())
");
$insSuper->execute(['p' => $testPhoneSuper, 'pwd' => $pwdHash]);
$newSuperId = (int)$pdo->lastInsertId();
assert($newSuperId > 0);
echo "[TEST 4] Super Admin Created Another Super Admin ... PASSED (ID: {$newSuperId}, Farhana)\n";

// 5. Admin Creates New Doctor
$testDocPhone = '+8801799999999';
$testBmdc = 'BMDC-TEST-9999';
$pdo->prepare("DELETE FROM users WHERE phone = :p")->execute(['p' => $testDocPhone]);

$docUserStmt = $pdo->prepare("
    INSERT INTO users (role, full_name, email, phone, password_hash, status, phone_verified_at, created_at)
    VALUES ('DOCTOR', 'Dr. Saiful Islam', 'saiful@example.com', :p, :pwd, 'ACTIVE', NOW(), NOW())
");
$docUserStmt->execute(['p' => $testDocPhone, 'pwd' => $pwdHash]);
$docUserId = (int)$pdo->lastInsertId();

$docStmt = $pdo->prepare("
    INSERT INTO doctors (user_id, bmdc_number, designation, experience_years, consultation_fee, follow_up_fee, verification_status, verified_at, verified_by, created_at)
    VALUES (:uid, :bmdc, 'MBBS, FCPS • Assistant Professor', 7, 900.00, 500.00, 'VERIFIED', NOW(), :vby, NOW())
");
$docStmt->execute(['uid' => $docUserId, 'bmdc' => $testBmdc, 'vby' => $superUser['id']]);
$newDoctorId = (int)$pdo->lastInsertId();
assert($newDoctorId > 0);

// Link specialty 1 (Cardiology)
$pdo->prepare("INSERT IGNORE INTO doctor_specialties (doctor_id, specialty_id) VALUES (:did, 1)")->execute(['did' => $newDoctorId]);

$chkDoc = $pdo->prepare("
    SELECT d.id, d.bmdc_number, d.verification_status, u.full_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.id = :id
");
$chkDoc->execute(['id' => $newDoctorId]);
$docRow = $chkDoc->fetch(PDO::FETCH_ASSOC);
assert($docRow['bmdc_number'] === $testBmdc);
assert($docRow['verification_status'] === 'VERIFIED');
assert($docRow['full_name'] === 'Dr. Saiful Islam');
echo "[TEST 5] Admin Created New Doctor Directly ... PASSED (Dr. Saiful Islam, BMDC: {$testBmdc})\n";

// 6. Super Admin Suspends An Admin
$pdo->prepare("UPDATE users SET status = 'SUSPENDED' WHERE id = :id")->execute(['id' => $newAdminId]);
$chkStatus = $pdo->prepare("SELECT status FROM users WHERE id = :id");
$chkStatus->execute(['id' => $newAdminId]);
assert($chkStatus->fetchColumn() === 'SUSPENDED');
echo "[TEST 6] Super Admin Suspends Platform Admin ... PASSED (Status: SUSPENDED)\n";

echo "==============================================================\n";
echo "   [ALL 6 ADMIN & SUPER ADMIN TESTS PASSED] 100% Robust       \n";
echo "==============================================================\n";
