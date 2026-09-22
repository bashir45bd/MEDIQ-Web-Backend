# MEDIQ — System Architecture Specification

## 1. Architectural Philosophy & Principles
MEDIQ is engineered to address the healthcare serial and chamber queue bottleneck in Bangladesh.
The architecture enforces the following non-negotiable principles:
1. **Backend-Authoritative**: The frontend (Flutter, Doctor Web, Admin Web) is never trusted. All business rules, capacity checks, promotion validations, token assignments, and queue state transitions are determined and executed exclusively on the backend.
2. **ACID & Concurrency Safe**: Every booking, queue advancement, and financial ledger entry operates inside pessimistic database transactions with row-level locks and atomic increment counters.
3. **Single Codebase Mobile App**: A single Flutter codebase dynamically loads either `PatientShell` or `DoctorShell` based on cryptographically verified JWT claims, ensuring clean UI separation without dual app overhead.
4. **Pure Web Standards**: Doctor Web and Admin Web utilize semantic HTML5, modern CSS3, and Vanilla JavaScript ES6+ communicating via REST API, avoiding framework churn and heavyweight build dependencies.
5. **Healthcare Data Privacy**: Strict access control, isolation of medical records, minimal public exposure on prescription QR codes, and immutable audit trails for every access event.

---

## 2. Technology Stack Breakdown

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENT TIER                                    │
│                                                                             │
│   ┌────────────────────────────────┐    ┌───────────────────────────────┐   │
│   │        MEDIQ Mobile App        │    │    Doctor & Admin Portals     │   │
│   │     Flutter 3.x / Dart 3.x     │    │  HTML5 + Modern CSS3 + ES6+   │   │
│   │ (Dynamic Patient/Doctor Shell) │    │  Vanilla JS (Zero Framework)  │   │
│   └───────────────┬────────────────┘    └───────────────┬───────────────┘   │
└───────────────────┼─────────────────────────────────────┼───────────────────┘
                    │                                     │
                    │ HTTPS / REST API (JSON)             │
                    ▼                                     ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              API / BACKEND TIER                             │
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                         PHP 8.2+ REST API                           │   │
│   │                                                                     │   │
│   │   [Router] ──> [RateLimiter] ──> [AuthMiddleware] ──> [Policies]    │   │
│   │                                                              │      │   │
│   │   [Controllers] <── [Validators] <───────────────────────────┘      │   │
│   │        │                                                            │   │
│   │        ▼                                                            │   │
│   │   [Service Layer] (Booking, Queue, WaitTime, Promotion, Rx)        │   │
│   │        │                                                            │   │
│   │        ▼                                                            │   │
│   │   [Repository Layer] (Prepared PDO Statements, Parameter Binding)   │   │
│   └──────────────────────────────────┬──────────────────────────────────┘   │
└──────────────────────────────────────┼──────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                             DATA & STORAGE TIER                             │
│                                                                             │
│   ┌───────────────────────────────┐     ┌───────────────────────────────┐   │
│   │  MySQL 8+ / MariaDB 10.4+     │     │     Private File Storage      │   │
│   │  - InnoDB Engine              │     │  - Doctor BMDC / NID Docs     │   │
│   │  - Row-Level Locking (Pess.)  │     │  - Prescription PDFs          │   │
│   │  - Atomic Sequence Counters   │     │  - Secure QR Verification     │   │
│   │  - Timezone: Asia/Dhaka       │     │  - Access-controlled Streams  │   │
│   └───────────────────────────────┘     └───────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Request Lifecycle & Layered Responsibilities

1. **HTTP Request Arrival**: Front Controller (`backend/public/index.php`) captures the request, sets standard security headers, establishes the global error/exception handler, and initializes the dependency container.
2. **Router & Pipeline**: `Router` matches the URI and method against `/api/v1/*` routes and executes the middleware pipeline:
   - `CorsMiddleware`: Handles cross-origin headers.
   - `RateLimiterMiddleware`: Enforces IP and user-based throttling.
   - `AuthMiddleware`: Validates Bearer JWT or authenticated session cookies.
   - `RoleMiddleware`: Enforces `PATIENT`, `DOCTOR`, or `PLATFORM_ADMIN` roles.
3. **Controller**: Extracts payload, delegates validation to dedicated `Validator` class.
4. **Validator**: Sanitizes inputs and validates constraints. Throws `ValidationException` on failure.
5. **Authorization Policy**: Policies (e.g. `AppointmentPolicy`, `DoctorPolicy`) verify if the authenticated user owns or is authorized to interact with the specific resource (IDOR mitigation).
6. **Service Layer**: Implements business transactions, state machine transitions, concurrency locks, event dispatching, and audit logging.
7. **Repository Layer**: Encapsulates PDO queries with parameter binding. Never writes business logic or formats responses.
8. **Response Formatter**: Formats JSON envelopes (`success`, `message`, `data`, `error`).

---

## 4. Mobile Architecture (Single Codebase, Dynamic Shell)

```
                            main.dart
                                │
                        AppLifecycleWatcher
                                │
                        AuthRoutingGuard
                                │
             ┌──────────────────┴──────────────────┐
             ▼                                     ▼
      Unauthenticated                         Authenticated
             │                                     │
         AuthFlow                    ┌─────────────┴─────────────┐
   (Login/Register/OTP)              │                           │
                                     ▼                           ▼
                              role == 'PATIENT'           role == 'DOCTOR'
                                     │                           │
                                     ▼                           ▼
                                PatientShell                DoctorShell
                             ┌─────────────────┐         ┌─────────────────┐
                             │ 1. Home         │         │ 1. Dashboard    │
                             │ 2. Doctors      │         │ 2. Live Queue   │
                             │ 3. Appointments │         │ 3. Appointments │
                             │ 4. Live Queue   │         │ 4. Patients/Rx  │
                             │ 5. Records      │         │ 5. Profile      │
                             └─────────────────┘         └─────────────────┘
```

### Mobile State & Polling Architecture:
- **State Management**: Clean repository pattern combined with reactive ChangeNotifier / StateNotifier providers.
- **Smart Polling Service**:
  - Live Queue screen polls `GET /api/v1/queue/live` at 10-second intervals.
  - Pauses polling when the screen loses focus or app enters background state (`AppLifecycleState.paused`).
  - Exponential backoff upon network interruption.
  - Zero simulated or fake states.

---

## 5. Doctor Web & Admin Web Architecture

- **Zero Build Step**: Native browser ES6 modules (`import/export`) and modern CSS variables.
- **Doctor Web Capabilities**:
  - Today's Chamber Queue Controller: Call Next, Start Consultation, Skip, Mark No-show, Broadcast Delay.
  - Rapid Availability Switcher: Toggle between `ON`, `PAUSED`, and `OFF` with a single click.
  - Digital Prescription Builder: Interactive medicine row addition, dosage, frequency, duration, diagnostic notes, digital signature integration.
  - Chamber & Schedule Manager: Set weekly hours, consultation durations, breaks, and daily capacities.
- **Admin Web Capabilities**:
  - Real-time Analytics & Revenue: Visual charts of daily bookings, platform fee earnings, free promotions.
  - Doctor Verification Workflow: View doctor license and BMDC certificate uploads, verify or reject with reason and audit trail.
  - Emergency Chamber Overrides: Admin capacity adjustment and session reopening with mandatory rationale logging.
  - Audit Trail Inspector: Complete log of who changed what, previous vs new values, IP, and timestamp.

---

## 6. Critical Architectural Risks & Mitigations

| Risk | Impact | Architectural Mitigation |
| :--- | :--- | :--- |
| **Race condition on final booking slot** | Overbooking chamber capacity | Pessimistic locking (`SELECT ... FOR UPDATE`) on `doctor_capacity` row + atomic increment within InnoDB transaction. |
| **Duplicate tokens issued under concurrency** | Chamber confusion, duplicate patient numbers | Dedicated `doctor_token_sequences` table with atomic `ON DUPLICATE KEY UPDATE current_sequence = current_sequence + 1`. Unique compound key `(doctor_id, chamber_id, appointment_date, token_number)` on `appointments`. |
| **IDOR / Patient data leak** | Severe healthcare privacy breach | Backend policy checks: Every endpoint verifies patient ownership (`patient_id == auth_user_patient_id`) or assigned doctor ownership. |
| **Tampered prescription printout** | Illegal drug dispensing | Prescription QR code links to a verification endpoint that checks a cryptographic hash against the database without leaking patient condition publicly. |
| **Duplicate payment confirmation** | Double tokens and double ledger entries | Unique constraint on `transaction_ref` in `payments` table. Webhook verification checks existing status under row lock before processing. |
