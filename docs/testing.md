# MEDIQ — Comprehensive Testing Strategy & Concurrency Verification Plan

## 1. Testing Hierarchy & Philosophy
A healthcare platform handling live patient serials and doctor availability cannot tolerate concurrency glitches, overbooking, or data leaks.
Testing is structured across four layers:
1. **Unit Tests**: Pure business logic (Promotions, State Machines, Wait-Time Algorithms).
2. **Integration Tests**: API workflows (Registration -> Verification -> Schedule -> Booking -> Token -> Queue -> Rx).
3. **Concurrency Simulation Tests**: Multi-threaded race conditions (Pessimistic locks, atomic counters, idempotent callbacks).
4. **Security & Penetration Tests**: SQLi, XSS, CSRF, IDOR, and role escalation defenses.

---

## 2. Concurrency Verification Test Specifications

### Case 1: Two Patients Attempt to Book the Final Capacity Slot
- **Scenario**: Chamber has `total_capacity = 20`. Currently `booked_count = 19`. Only 1 slot remains.
- **Action**: Two concurrent HTTP requests (Patient A and Patient B) invoke `POST /api/v1/appointments/book` with identical parameters simultaneously using multi-threaded execution.
- **Expected Results**:
  - Exactly **one** request succeeds with `HTTP 201 Created` and receives token `A020`.
  - The second request fails with `HTTP 409 Conflict` and error code `CAPACITY_FULL`.
  - In `doctor_capacity`, `booked_count` equals exactly `20`.
  - The chamber status automatically updates to `FULL`.
  - Zero database deadlocks or unhandled exceptions.

### Case 2: Two Users Attempt Simultaneous Token Generation
- **Scenario**: Multiple patients book consecutive slots concurrently.
- **Action**: 10 concurrent requests hit the booking endpoint across 5 threads.
- **Expected Results**:
  - Each successful booking receives a unique, non-colliding integer `token_number` (e.g. 1, 2, 3... 10).
  - The compound unique key `(doctor_id, chamber_id, appointment_date, token_number)` is never violated.
  - Zero duplicate serials are assigned.

### Case 3: Duplicate Payment Gateway Webhook
- **Scenario**: Payment gateway experiences a network timeout and transmits the same `transaction_ref` webhook twice simultaneously.
- **Action**: Two parallel HTTP POST requests hit `/api/v1/payments/webhook/bkash` with identical payloads.
- **Expected Results**:
  - The first worker acquires row lock on `payments`, transitions status to `SUCCESSFUL`, issues the appointment token, and records ledger entry.
  - The second worker receives the row lock, detects status is already `SUCCESSFUL`, and immediately returns `HTTP 200 OK` with `{"idempotent": true}`.
  - No duplicate tokens, no duplicate appointments, and no duplicate financial credits occur.

### Case 4: Duplicate Queue Completion Request
- **Scenario**: Doctor double-clicks "Complete Consultation" or two web browser tabs submit the completion form at the exact same millisecond.
- **Action**: Two concurrent requests execute `POST /api/v1/queue/doctor/complete` for Appointment #1042.
- **Expected Results**:
  - Exactly one request updates the appointment to `COMPLETED` and advances the queue.
  - The second request sees `affected_rows == 0` (status is no longer `CONSULTING`), returns `HTTP 409 Conflict` with `INVALID_STATE_TRANSITION`, and prevents advancing the queue by two steps.

### Case 5: Two Waitlist Users Claim One Released Slot
- **Scenario**: A slot opens up due to cancellation; two waitlist users simultaneously attempt to claim the reservation.
- **Action**: Two requests submit `POST /api/v1/waitlist/{id}/claim` concurrently.
- **Expected Results**:
  - Only the first user whose transaction acquires the slot lock is granted the booking.
  - The second user receives `HTTP 409 Conflict` (`SLOT_ALREADY_CLAIMED`).

---

## 3. Unit Testing Matrix

| Component | Target Service | Test Cases |
| :--- | :--- | :--- |
| **Promotion Engine** | `PromotionService` | - Apply fixed discount (৳100 off)<br>- Apply percentage discount with cap (20% up to ৳200)<br>- Reject expired promo code<br>- Reject when usage limit exceeded<br>- Prioritize highest valid promotion when multiple apply |
| **State Machines** | `AppointmentStateService` | - Allow `BOOKED` -> `CHECKED_IN`<br>- Reject `BOOKED` -> `COMPLETED`<br>- Allow `WAITING` -> `CALLED`<br>- Reject `NO_SHOW` -> `CONSULTING` |
| **Wait-Time Engine**| `WaitTimeService` | - Calculate estimate with 5 patients ahead, 10 min avg = `32–55 min`<br>- Recalculate with 20 min doctor delay added = `48–75 min`<br>- Return `You are next` when 0 patients ahead |
| **Arrival Estimator**| `ArrivalService` | - Ensure recommended arrival subtracts 15-min safety buffer<br>- Ensure immediate arrival warning when buffer has passed |

---

## 4. Security Test Suite

- **SQL Injection**: Inject payloads (`' OR 1=1 --`, `UNION SELECT`) into doctor search filters, chamber IDs, and sorting parameters; verify PDO prepared statement sanitization.
- **XSS Testing**: Inject `<script>alert('XSS')</script>` into patient clinical symptoms, doctor bio, and review comments; verify proper escaping upon retrieval.
- **IDOR Testing**: Authenticate as Patient A; attempt to fetch `/api/v1/appointments/{Patient_B_ID}`; verify `403 Forbidden` response.
- **Role Escalation**: Authenticate as `PATIENT`; attempt to invoke `/api/v1/doctor/queue/call-next`; verify `403 Forbidden` response.
- **File Upload Abuse**: Attempt to upload a `.php` file masquerading as a JPEG; verify MIME type rejection.
