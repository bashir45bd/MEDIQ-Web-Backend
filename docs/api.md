# MEDIQ — REST API Specification (/api/v1)

## 1. Global API Standards & Conventions
- **Base URI**: `https://api.mediq.com.bd/api/v1` (Local: `http://localhost:8000/api/v1`)
- **Content-Type**: `application/json; charset=utf-8`
- **Authentication**: `Authorization: Bearer <JWT_TOKEN>` (for mobile API) and secure HttpOnly SameSite cookies (for Web portals).
- **Timezone**: All timestamp payloads follow ISO 8601 in `Asia/Dhaka` timezone (`+06:00`).
- **Pagination**: Paginated endpoints return `meta: { "current_page": 1, "per_page": 15, "total": 142, "last_page": 10 }`.

---

## 2. Standardized Response Format

### Success Envelope
```json
{
  "success": true,
  "message": "Appointment confirmed successfully.",
  "data": {
    "appointment_id": 1042,
    "token_display": "A014",
    "status": "BOOKED"
  }
}
```

### Error Envelope
```json
{
  "success": false,
  "error": {
    "code": "CAPACITY_FULL",
    "message": "No appointment capacity remains for the selected chamber session."
  }
}
```

### Machine-Readable Error Codes
- `AUTH_REQUIRED`: Missing or invalid Bearer token.
- `INVALID_CREDENTIALS`: Phone/email or password mismatch.
- `FORBIDDEN`: User lacks role or ownership permission (IDOR protection).
- `VALIDATION_ERROR`: Missing or malformed payload fields.
- `DOCTOR_NOT_VERIFIED`: Doctor profile is still pending BMDC verification.
- `DOCTOR_SUSPENDED`: Doctor account has been suspended by administration.
- `CHAMBER_NOT_FOUND`: Specified chamber does not exist or is inactive.
- `AVAILABILITY_OFF`: Doctor session is currently OFF or CLOSED.
- `CAPACITY_FULL`: Booking slots exhausted.
- `DATE_NOT_AVAILABLE`: Date does not match doctor's active schedule.
- `APPOINTMENT_NOT_FOUND`: Appointment ID does not exist.
- `APPOINTMENT_ALREADY_CANCELLED`: Appointment cannot be cancelled again.
- `INVALID_STATE_TRANSITION`: Queue transition violates state machine rules.
- `PAYMENT_FAILED`: Platform fee transaction declined.
- `PAYMENT_NOT_VERIFIED`: Gateway webhook hash validation failed.
- `PROMOTION_EXPIRED`: Discount code is no longer valid.
- `PROMOTION_LIMIT_REACHED`: Campaign quota or per-user limit reached.
- `WAITLIST_UNAVAILABLE`: Waitlist full or closed for this session.
- `RATE_LIMITED`: Exceeded maximum request threshold per minute.
- `SERVER_ERROR`: Generic masked internal exception.

---

## 3. API Endpoints Catalog

### 3.1 Authentication (`/api/v1/auth`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/auth/register` | None | Any | Register Patient or Doctor |
| `POST` | `/auth/login` | None | Any | Authenticate with mobile/password; returns JWT + role |
| `POST` | `/auth/send-otp` | None | Any | Request SMS OTP for mobile verification |
| `POST` | `/auth/verify-otp` | None | Any | Verify mobile OTP code |
| `POST` | `/auth/forgot-password`| None | Any | Request password reset token |
| `POST` | `/auth/reset-password` | None | Any | Reset password with token |
| `GET`  | `/auth/me` | Bearer | Any | Retrieve current authenticated profile & role |
| `POST` | `/auth/logout` | Bearer | Any | Invalidate token / destroy session |

---

### 3.2 Doctor Discovery & Public Profiles (`/api/v1/doctors`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET`  | `/doctors` | Optional| Public | Search/filter doctors (specialty, city, area, availability) |
| `GET`  | `/doctors/{id}` | Optional| Public | Public doctor card (BMDC, bio, chambers, live status) |
| `GET`  | `/doctors/{id}/chambers`| Optional| Public | List active chambers with schedules and fees |
| `GET`  | `/doctors/{id}/reviews` | Optional| Public | List verified patient reviews |

---

### 3.3 Locations & Specialties (`/api/v1/taxonomy`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET`  | `/specialties` | None | Public | List medical specialties (Cardiology, Pediatrics, etc.) |
| `GET`  | `/locations/cities` | None | Public | List cities grouped by division |
| `GET`  | `/locations/cities/{id}/areas`| None | Public| List areas in a city |

---

### 3.4 Patient Self-Service & Family (`/api/v1/patients`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET`  | `/patients/profile` | Bearer | PATIENT | Get patient medical profile |
| `PUT`  | `/patients/profile` | Bearer | PATIENT | Update blood group, DOB, address, emergency contact |
| `GET`  | `/patients/family` | Bearer | PATIENT | List managed family members |
| `POST` | `/patients/family` | Bearer | PATIENT | Add family member (Father, Mother, Child, etc.) |
| `DELETE`|`/patients/family/{id}`| Bearer | PATIENT | Remove family member |
| `GET`  | `/patients/favorites` | Bearer | PATIENT | List saved favorite doctors |
| `POST` | `/patients/favorites/{id}`| Bearer | PATIENT | Add doctor to favorites |
| `DELETE`|`/patients/favorites/{id}`| Bearer | PATIENT | Remove doctor from favorites |

---

### 3.5 Appointments & Concurrency Booking (`/api/v1/appointments`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/appointments/validate` | Bearer | PATIENT | Check availability, capacity, and calculate fees/discount |
| `POST` | `/appointments/book` | Bearer | PATIENT | Concurrency-safe slot reservation & token issuance |
| `GET`  | `/appointments` | Bearer | Any | List user's appointments (filter: upcoming, past) |
| `GET`  | `/appointments/{id}` | Bearer | Any | Detailed appointment card with token & arrival advice |
| `POST` | `/appointments/{id}/cancel`| Bearer | PATIENT/ADMIN| Cancel appointment with refund trigger & slot release |

---

### 3.6 Live Queue Tracking & Control (`/api/v1/queue`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET`  | `/queue/live/{appointment_id}`| Bearer | PATIENT | Live queue status (current token, ahead count, wait range) |
| `GET`  | `/queue/doctor/today` | Bearer | DOCTOR | Doctor's active chamber queue (waiting, completed, skipped) |
| `POST` | `/queue/doctor/call-next` | Bearer | DOCTOR | Call next patient in queue |
| `POST` | `/queue/doctor/start` | Bearer | DOCTOR | Mark patient consultation started |
| `POST` | `/queue/doctor/complete` | Bearer | DOCTOR | Conclude consultation, advance queue |
| `POST` | `/queue/doctor/skip` | Bearer | DOCTOR | Temporarily skip patient |
| `POST` | `/queue/doctor/no-show` | Bearer | DOCTOR | Mark patient as no-show |
| `POST` | `/queue/doctor/delay` | Bearer | DOCTOR | Broadcast delay minutes to waiting patients |
| `POST` | `/queue/checkin` | Bearer | PATIENT | QR check-in or "I'm Here" confirmation |

---

### 3.7 Doctor Management & Chambers (`/api/v1/doctor`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET`  | `/doctor/dashboard` | Bearer | DOCTOR | Daily overview: today's chambers, token counts, availability |
| `POST` | `/doctor/availability/toggle`| Bearer| DOCTOR | Rapid toggle `ON` / `PAUSED` / `OFF` for today's session |
| `POST` | `/doctor/capacity/update` | Bearer | DOCTOR | Increase capacity (cannot decrease below booked count) |
| `GET`  | `/doctor/chambers` | Bearer | DOCTOR | List doctor's chambers |
| `POST` | `/doctor/chambers` | Bearer | DOCTOR | Create new chamber |
| `PUT`  | `/doctor/chambers/{id}` | Bearer | DOCTOR | Update chamber details |
| `GET`  | `/doctor/schedules` | Bearer | DOCTOR | List weekly chamber consultation schedules |
| `POST` | `/doctor/schedules` | Bearer | DOCTOR | Create or update chamber schedule & breaks |

---

### 3.8 Consultations, Digital Prescriptions & Records (`/api/v1/consultations`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/consultations` | Bearer | DOCTOR | Save consultation diagnosis, medicines, tests, advice |
| `GET`  | `/consultations/{appointment_id}`| Bearer| DOCTOR/PATIENT| View clinical consultation details |
| `GET`  | `/prescriptions/{id}/pdf` | Bearer | DOCTOR/PATIENT| Stream secure digital prescription PDF |
| `GET`  | `/prescriptions/verify/{token}` | None | Public | Public QR authenticity check (zero medical condition leak) |
| `GET`  | `/patients/medical-records` | Bearer | PATIENT | Complete historical timeline of patient records |

---

### 3.9 Payments & Promotions (`/api/v1/payments`, `/api/v1/promotions`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/payments/initiate` | Bearer | PATIENT | Initiate platform fee checkout |
| `POST` | `/payments/webhook/{gateway}` | None | Webhook | Server-side verified callback (idempotent) |
| `POST` | `/promotions/apply` | Bearer | PATIENT | Validate campaign promo code or coupon |

---

### 3.10 Platform Administration (`/api/v1/admin`)
| Method | Path | Auth | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET`  | `/admin/dashboard` | Bearer | ADMIN | System KPIs: total revenue, active doctors, waiting averages |
| `GET`  | `/admin/doctors` | Bearer | ADMIN | List doctors with filter by verification status |
| `POST` | `/admin/doctors/{id}/verify`| Bearer| ADMIN | Approve BMDC documents & verify doctor |
| `POST` | `/admin/doctors/{id}/reject`| Bearer| ADMIN | Reject credentials with reason |
| `POST` | `/admin/doctors/{id}/suspend`| Bearer| ADMIN| Suspend doctor account |
| `POST` | `/admin/availability/override`|Bearer| ADMIN| Emergency admin override of chamber status or capacity |
| `GET`  | `/admin/revenue` | Bearer | ADMIN | Financial transaction ledger & breakdown |
| `POST` | `/admin/promotions` | Bearer | ADMIN | Create promotion campaign / coupon batch |
| `GET`  | `/admin/audit-logs` | Bearer | ADMIN | Search immutable audit trail with diffs |
| `GET`  | `/admin/settings` | Bearer | ADMIN | View system settings |
| `PUT`  | `/admin/settings` | Bearer | ADMIN | Update platform fee or grace periods |
