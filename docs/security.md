# MEDIQ — Security & Healthcare Privacy Architecture

## 1. Healthcare Data Privacy Standards
Medical records, diagnoses, and doctor-patient interactions represent highly sensitive personal information.
MEDIQ enforces the following healthcare privacy rules:
1. **Zero Public Medical Leaks**: No medical conditions, diagnoses, medicines, dosages, or private clinical notes are ever transmitted over unauthenticated endpoints or printed openly on public verification URLs.
2. **Minimalist QR Prescription Verification**:
   - The printed prescription contains a verification QR code pointing to `https://verify.mediq.com.bd/rx/{token}`.
   - When scanned by a third party (e.g. pharmacy or employer), the endpoint returns **only**:
     - Status: `GENUINE_MEDIQ_PRESCRIPTION` or `INVALID`
     - Doctor Name, Degrees & BMDC Number
     - Chamber / Hospital Name
     - Date & Time Issued
     - Prescription Serial Code
   - **No medicines, dosages, clinical symptoms, or diagnoses are displayed on the public verification page**. Full clinical details are accessible only by the verified patient logging into their MEDIQ account.
3. **Strict Isolation of Patient Files**:
   - Medical records and doctor credential documents (BMDC certificates, NID scans) are stored in `storage/documents/` and `storage/prescriptions/` located **outside** the web server's public document root.
   - File downloads are served exclusively through an authenticated PHP stream controller that verifies ownership before streaming bytes (`readfile`).

---

## 2. Role-Based Access Control (RBAC) Matrix

MEDIQ recognizes exactly three primary roles:
- `PATIENT`
- `DOCTOR`
- `PLATFORM_ADMIN`

| Resource / Capability | `PATIENT` | `DOCTOR` | `PLATFORM_ADMIN` |
| :--- | :---: | :---: | :---: |
| Browse Verified Doctors & Chambers | Yes | Yes | Yes |
| View Public Doctor Reviews | Yes | Yes | Yes |
| Book Appointment & Select Family Member | Yes | No | No |
| Access Own Appointment History & QR | Yes | No | Yes (Audit) |
| Scan Check-in QR / Click "I'm Here" | Yes (Own) | No | Yes (Chamber Desk) |
| Track Live Queue & Wait-Time Estimate | Yes (Own) | Yes (Chamber) | Yes (Global) |
| Access Own Medical Timeline & Rx PDF | Yes (Own) | No | No |
| Modify Chamber Weekly Schedules & Capacity | No | Yes (Own) | Yes (Emergency Override) |
| Call Next, Start, Complete, Skip, No-show | No | Yes (Own Chamber) | No |
| Write Prescription & Digital Signature | No | Yes (Own Consultation) | No |
| Broadcast Chamber Delay to Patients | No | Yes (Own Chamber) | Yes |
| Review Doctor BMDC Documents & Verify/Reject | No | No | Yes |
| Configure Platform Fee & System Settings | No | No | Yes |
| Inspect System-Wide Immutable Audit Logs | No | No | Yes |

---

## 3. IDOR (Insecure Direct Object Reference) Prevention

Every incoming request that references an entity by ID undergoes an authorization policy check:
```php
class AppointmentPolicy {
    public static function canView(User $user, Appointment $appointment): bool {
        if ($user->role === 'PLATFORM_ADMIN') return true;
        if ($user->role === 'DOCTOR' && $appointment->doctor_id === $user->doctor->id) return true;
        if ($user->role === 'PATIENT' && $appointment->patient_id === $user->patient->id) return true;
        return false;
    }

    public static function canCancel(User $user, Appointment $appointment): bool {
        if ($user->role === 'PLATFORM_ADMIN') return true;
        if ($user->role === 'PATIENT' && $appointment->patient_id === $user->patient->id) {
            // Cannot cancel if already in consultation or completed
            return in_array($appointment->status, ['BOOKED', 'CHECKED_IN']);
        }
        return false;
    }
}
```
Repository queries for sensitive entities always append ownership criteria to the `WHERE` clause:
```sql
SELECT * FROM medical_records WHERE id = :id AND patient_id = :auth_patient_id;
```

---

## 4. Authentication & Password Security
- **Password Storage**: Passwords are encrypted using PHP's native BCrypt algorithm:
  ```php
  $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
  ```
- **Password Verification**: `password_verify($password, $hash)`. Plaintext passwords are never logged, echoed, or stored.
- **JWT Architecture**:
  - Signed using HMAC-SHA256 (`HS256`) with a cryptographically secure key loaded from `.env`.
  - Expiration: Access tokens expire in 60 minutes; Refresh tokens expire in 30 days.
  - Revocation: Stored token version / revocation list invalidates all active sessions on password change or admin suspension.

---

## 5. File Upload Security Standard
1. **MIME Type Inspection**: The backend verifies the file contents using PHP's Fileinfo extension (`finfo_file(FILEINFO_MIME_TYPE)`), refusing to trust client-supplied extensions.
2. **Whitelist**: Only `image/jpeg`, `image/png`, and `application/pdf` are accepted. Executable extensions (`.php`, `.exe`, `.sh`, `.phtml`) are rejected immediately.
3. **Randomized Filenames**: Files are saved with cryptographically secure random names:
   ```php
   $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;
   ```
4. **File Size Limits**: Max 5 MB for credentials, max 10 MB for patient radiology/imaging reports.

---

## 6. Web & API Security Hardening
- **SQL Injection Prevention**: 100% of database interactions use PDO prepared statements with parameter binding. Direct SQL concatenation is strictly prohibited.
- **Cross-Site Scripting (XSS)**: All user-supplied output rendered in Doctor Web or Admin Web is escaped using `htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Cross-Site Request Forgery (CSRF)**: Web portal sessions use synchronizer CSRF tokens passed in custom headers or hidden form fields for all state-modifying requests (`POST`, `PUT`, `DELETE`).
- **Rate Limiting**:
  - Auth endpoints (`/auth/login`, `/auth/send-otp`): 5 attempts per minute per IP.
  - Appointment booking endpoints: 10 requests per minute per user.
- **HTTP Security Headers**:
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;`

---

## 7. Immutable Audit Trail Specification
Every sensitive administrative, clinical, or financial mutation writes an unalterable log to `audit_logs`:
- `actor_id`: User who executed the change.
- `actor_role`: Role at execution time.
- `action`: Specific operation code (e.g. `DOCTOR_VERIFIED`, `AVAILABILITY_OVERRIDE`, `APPOINTMENT_CANCELLED`).
- `entity` & `entity_id`: Target table and record.
- `old_value_json` & `new_value_json`: State difference before and after execution.
- `ip_address` & `user_agent`: Network origin metadata.
- `created_at`: ISO timestamp in `Asia/Dhaka`.
- **Note**: Passwords, encryption keys, and payment credentials are automatically scrubbed from JSON payloads before persistence.
