<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Response;
use PDO;
use Throwable;

class BookingService {
    /**
     * Concurrency-safe atomic booking transaction
     */
    public function bookAppointment(array $payload, array $authUser): array {
        $doctorId   = (int)$payload['doctor_id'];
        $chamberId  = (int)$payload['chamber_id'];
        $date       = $payload['appointment_date'];
        $patientId  = (int)$authUser['patient_id'];
        $userId     = (int)$authUser['id'];
        $familyId   = !empty($payload['family_member_id']) ? (int)$payload['family_member_id'] : null;
        $patientType= $payload['patient_type'] ?? 'NEW';
        $promoCode  = $payload['promo_code'] ?? null;

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Verify doctor is verified and chamber belongs to doctor
            $docStmt = $pdo->prepare("
                SELECT d.id, d.consultation_fee, d.follow_up_fee, d.verification_status, c.token_prefix, c.is_active
                FROM doctors d
                JOIN doctor_chambers c ON d.id = c.doctor_id
                WHERE d.id = :did AND c.id = :cid LIMIT 1
            ");
            $docStmt->execute(['did' => $doctorId, 'cid' => $chamberId]);
            $docInfo = $docStmt->fetch(PDO::FETCH_ASSOC);

            if (!$docInfo) {
                Response::error('CHAMBER_NOT_FOUND', 'Specified doctor chamber not found.', 404);
            }
            if ($docInfo['verification_status'] !== 'VERIFIED') {
                Response::error('DOCTOR_NOT_VERIFIED', 'Doctor is currently under administrative verification.', 403);
            }
            if (!$docInfo['is_active']) {
                Response::error('CHAMBER_INACTIVE', 'This chamber is currently not accepting appointments.', 403);
            }

            // 1.1 Expire unvisited past appointments
            \App\Controllers\AppointmentController::expirePastAppointments($pdo);

            // 1.2 Validate appointment date is not in the past
            if ($date < date('Y-m-d')) {
                $pdo->rollBack();
                Response::error(
                    'INVALID_APPOINTMENT_DATE',
                    'Appointment date cannot be in the past. (অতীতের কোনো তারিখ নির্বাচন করা যাবে না।)',
                    422
                );
            }

            // 1.3 Validate doctor has an active schedule for this day of week
            $dayOfWeek = (int)date('w', strtotime($date));
            $schStmt = $pdo->prepare("
                SELECT id, start_time, end_time FROM doctor_schedules
                WHERE doctor_id = :did AND chamber_id = :cid AND day_of_week = :dow AND is_active = 1
                LIMIT 1
            ");
            $schStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dow' => $dayOfWeek]);
            $scheduleRow = $schStmt->fetch(PDO::FETCH_ASSOC);

            if (!$scheduleRow) {
                $pdo->rollBack();
                $allSch = $pdo->prepare("
                    SELECT day_of_week, start_time, end_time FROM doctor_schedules
                    WHERE doctor_id = :did AND chamber_id = :cid AND is_active = 1
                    ORDER BY day_of_week ASC
                ");
                $allSch->execute(['did' => $doctorId, 'cid' => $chamberId]);
                $availRows = $allSch->fetchAll(PDO::FETCH_ASSOC);

                $dayNamesEn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $dayNamesBn = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];

                if (!empty($availRows)) {
                    $dayStrings = [];
                    foreach ($availRows as $r) {
                        $d = (int)$r['day_of_week'];
                        $st = date('g:i A', strtotime($r['start_time']));
                        $et = date('g:i A', strtotime($r['end_time']));
                        $dayStrings[] = "{$dayNamesEn[$d]} ({$dayNamesBn[$d]}) [{$st} - {$et}]";
                    }
                    $availDaysStr = implode(', ', $dayStrings);
                } else {
                    $availDaysStr = 'No consultation schedule configured for this chamber yet.';
                }

                $selectedDayName = "{$dayNamesEn[$dayOfWeek]} ({$dayNamesBn[$dayOfWeek]})";
                Response::error(
                    'DOCTOR_NOT_AVAILABLE_ON_DATE',
                    "Doctor does not have a scheduled chamber session on {$selectedDayName}. Available chamber days: {$availDaysStr}. (ডাক্তার এই বারে এই চেম্বারে রোগী দেখেন না। চেম্বার সূচি: {$availDaysStr})",
                    422
                );
            }

            // 1.5 Prevent duplicate appointment for same patient at same doctor/chamber/date
            if ($familyId !== null) {
                $dupStmt = $pdo->prepare("
                    SELECT token_display, status FROM appointments
                    WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                      AND patient_id = :pid AND family_member_id = :fid
                      AND status NOT IN ('CANCELLED', 'NO_SHOW')
                    LIMIT 1
                ");
                $dupStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'pid' => $patientId, 'fid' => $familyId]);
            } else {
                $dupStmt = $pdo->prepare("
                    SELECT token_display, status FROM appointments
                    WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                      AND patient_id = :pid AND family_member_id IS NULL
                      AND status NOT IN ('CANCELLED', 'NO_SHOW')
                    LIMIT 1
                ");
                $dupStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date, 'pid' => $patientId]);
            }

            $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $pdo->rollBack();
                Response::error(
                    'DUPLICATE_BOOKING',
                    "You already have an active appointment (Serial: {$existing['token_display']}) with this doctor at this chamber for {$date}. (আপনি ইতোমধ্যে এই তারিখে এই ডাক্তারের চেম্বারে সিরিয়াল নিয়েছেন।)",
                    409
                );
            }

            // 2. Lock and verify daily availability
            $availStmt = $pdo->prepare("
                SELECT status FROM doctor_daily_availability
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                FOR UPDATE
            ");
            $availStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $availStatus = $availStmt->fetchColumn();

            if (!$availStatus) {
                // If not explicitly created, initialize to ON
                $initAvail = $pdo->prepare("
                    INSERT INTO doctor_daily_availability (doctor_id, chamber_id, appointment_date, status, last_status_updated_at)
                    VALUES (:did, :cid, :dt, 'ON', NOW())
                ");
                $initAvail->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
                $availStatus = 'ON';
            }

            if (in_array($availStatus, ['OFF', 'CLOSED', 'COMPLETED'], true)) {
                Response::error('AVAILABILITY_OFF', "Doctor session is currently {$availStatus} for {$date}.", 409);
            }
            if ($availStatus === 'PAUSED') {
                Response::error('AVAILABILITY_PAUSED', 'Doctor has temporarily paused bookings for this chamber.', 409);
            }

            // 3. PESSIMISTIC LOCK: Lock capacity row to prevent race conditions on last remaining slot
            $capStmt = $pdo->prepare("
                SELECT id, total_capacity, booked_count 
                FROM doctor_capacity
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                FOR UPDATE
            ");
            $capStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $capacityRow = $capStmt->fetch(PDO::FETCH_ASSOC);

            if (!$capacityRow) {
                // Initialize default capacity
                $initCap = $pdo->prepare("
                    INSERT INTO doctor_capacity (doctor_id, chamber_id, appointment_date, total_capacity, booked_count, version)
                    VALUES (:did, :cid, :dt, 20, 0, 1)
                ");
                $initCap->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
                $capacityRow = ['total_capacity' => 20, 'booked_count' => 0];
            }

            $totalCapacity = (int)$capacityRow['total_capacity'];
            $bookedCount   = (int)$capacityRow['booked_count'];

            if ($bookedCount >= $totalCapacity) {
                // Update availability to FULL
                $pdo->prepare("UPDATE doctor_daily_availability SET status = 'FULL' WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt")
                    ->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
                $pdo->commit();
                Response::error('CAPACITY_FULL', 'Chamber capacity has been reached. No slots remaining.', 409);
            }

            // 4. Atomic increment of booked count
            $newBooked = $bookedCount + 1;
            $upCapStmt = $pdo->prepare("
                UPDATE doctor_capacity 
                SET booked_count = :nb, version = version + 1
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
            ");
            $upCapStmt->execute(['nb' => $newBooked, 'did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);

            // 5. Atomic sequence counter generation for serial uniqueness
            $seqStmt = $pdo->prepare("
                INSERT INTO doctor_token_sequences (doctor_id, chamber_id, appointment_date, current_sequence)
                VALUES (:did, :cid, :dt, 1)
                ON DUPLICATE KEY UPDATE current_sequence = current_sequence + 1
            ");
            $seqStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);

            $getSeq = $pdo->prepare("
                SELECT current_sequence FROM doctor_token_sequences
                WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
            ");
            $getSeq->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $tokenNumber = (int)$getSeq->fetchColumn();

            $tokenPrefix = $docInfo['token_prefix'] ?: 'A';
            $tokenDisplay = sprintf('%s%03d', $tokenPrefix, $tokenNumber);

            // 6. Pricing & Promotions Calculation
            $consultationFee = ($patientType === 'FOLLOW_UP') ? (float)$docInfo['follow_up_fee'] : (float)$docInfo['consultation_fee'];

            // Platform fee from system_settings
            $setStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE key_name = 'default_platform_fee'");
            $platformFee = (float)($setStmt->fetchColumn() ?: 50.00);

            $discountAmount = 0.00;
            $appliedPromoId = null;

            if ($promoCode) {
                $pStmt = $pdo->prepare("
                    SELECT * FROM promotions 
                    WHERE code = :code AND status = 'ACTIVE' AND start_date <= :dt1 AND end_date >= :dt2
                    LIMIT 1 FOR UPDATE
                ");
                $pStmt->execute(['code' => $promoCode, 'dt1' => $date, 'dt2' => $date]);
                $promo = $pStmt->fetch(PDO::FETCH_ASSOC);

                if ($promo) {
                    if ($promo['type'] === 'FREE') {
                        $discountAmount = $platformFee;
                    } elseif ($promo['type'] === 'PERCENT') {
                        $disc = ($platformFee * (float)$promo['discount_value']) / 100.0;
                        if (!empty($promo['max_discount'])) {
                            $disc = min($disc, (float)$promo['max_discount']);
                        }
                        $discountAmount = $disc;
                    } elseif ($promo['type'] === 'FIXED') {
                        $discountAmount = min((float)$promo['discount_value'], $platformFee);
                    }
                    $appliedPromoId = (int)$promo['id'];
                }
            }

            $totalPaid = max(0.00, $platformFee - $discountAmount);
            $paymentStatus = ($totalPaid == 0.00) ? 'PAID' : 'PENDING';

            // Generate unique appointment number
            $aptNumber = 'APT-' . str_replace('-', '', $date) . '-' . $tokenDisplay . '-' . substr(bin2hex(random_bytes(3)), 0, 4);

            // 7. Insert Appointment Record (Protected by compound unique key)
            $insApt = $pdo->prepare("
                INSERT INTO appointments (
                    appointment_number, doctor_id, chamber_id, appointment_date, patient_id,
                    booked_by_user_id, family_member_id, patient_type, token_number, token_display,
                    status, consultation_fee, platform_fee, discount_amount, total_paid,
                    payment_status, created_at
                ) VALUES (
                    :num, :did, :cid, :dt, :pid, :buid, :fid, :ptype, :tnum, :tdisp,
                    'CHECKED_IN', :cfee, :pfee, :disc, :tpaid, :pstat, NOW()
                )
            ");
            $insApt->execute([
                'num'   => $aptNumber,
                'did'   => $doctorId,
                'cid'   => $chamberId,
                'dt'    => $date,
                'pid'   => $patientId,
                'buid'  => $userId,
                'fid'   => $familyId,
                'ptype' => $patientType,
                'tnum'  => $tokenNumber,
                'tdisp' => $tokenDisplay,
                'cfee'  => $consultationFee,
                'pfee'  => $platformFee,
                'disc'  => $discountAmount,
                'tpaid' => $totalPaid,
                'pstat' => $paymentStatus
            ]);
            $appointmentId = (int)$pdo->lastInsertId();

            // 8. Insert into Queue
            $insQueue = $pdo->prepare("
                INSERT INTO appointment_queue (appointment_id, doctor_id, chamber_id, appointment_date, sequence_number, status)
                VALUES (:aid, :did, :cid, :dt, :seq, 'WAITING')
            ");
            $insQueue->execute([
                'aid' => $appointmentId,
                'did' => $doctorId,
                'cid' => $chamberId,
                'dt'  => $date,
                'seq' => $tokenNumber
            ]);

            // 9. Record Promotion Usage if applied
            if ($appliedPromoId) {
                $insPromo = $pdo->prepare("
                    INSERT INTO promotion_usage (promotion_id, user_id, appointment_id, discount_applied, created_at)
                    VALUES (:prid, :uid, :aid, :disc, NOW())
                ");
                $insPromo->execute([
                    'prid' => $appliedPromoId,
                    'uid'  => $userId,
                    'aid'  => $appointmentId,
                    'disc' => $discountAmount
                ]);
            }

            // 10. Automatically mark FULL if capacity is now reached
            if ($newBooked >= $totalCapacity) {
                $pdo->prepare("
                    UPDATE doctor_daily_availability 
                    SET status = 'FULL' 
                    WHERE doctor_id = :did AND chamber_id = :cid AND appointment_date = :dt
                ")->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            }

            $pdo->commit();

            // Audit
            AuditService::log($userId, 'PATIENT', 'APPOINTMENT_BOOKED', 'appointments', $appointmentId, null, [
                'token' => $tokenDisplay,
                'total_paid' => $totalPaid
            ]);

            // Push notification to patient
            try {
                $docQ = $pdo->prepare("
                    SELECT u.full_name, c.name AS chamber_name 
                    FROM doctors d 
                    JOIN users u ON d.user_id = u.id 
                    JOIN doctor_chambers c ON c.id = :cid 
                    WHERE d.id = :did LIMIT 1
                ");
                $docQ->execute(['did' => $doctorId, 'cid' => $chamberId]);
                $docRow = $docQ->fetch(PDO::FETCH_ASSOC);
                $docName = $docRow['full_name'] ?? 'ডাক্তার';
                $chName  = $docRow['chamber_name'] ?? 'চেম্বার';

                NotificationService::dispatch(
                    $userId,
                    'APPOINTMENT_BOOKED',
                    'APT_' . $appointmentId . '_CONFIRMED',
                    'ALL',
                    "সিরিয়াল নিশ্চিত: {$tokenDisplay}",
                    "{$docName}-এর চেম্বারে ({$chName}) আপনার সিরিয়াল {$tokenDisplay} নিশ্চিত হয়েছে। তারিখ: {$date}।",
                    [
                        'appointment_id'   => (string)$appointmentId,
                        'token_display'    => $tokenDisplay,
                        'appointment_date' => $date,
                        'type'             => 'appointment_booked'
                    ]
                );
            } catch (Throwable $ne) {
                error_log("[NOTIFICATION BOOKING ERROR] " . $ne->getMessage());
            }


            // Calculate estimated consultation time & expected arrival time (20-30 mins prior)
            $schStmt = $pdo->prepare("SELECT start_time, slot_duration_minutes FROM doctor_schedules WHERE doctor_id = :did AND chamber_id = :cid AND day_of_week = (DAYOFWEEK(:dt) - 1) LIMIT 1");
            $schStmt->execute(['did' => $doctorId, 'cid' => $chamberId, 'dt' => $date]);
            $schInfo = $schStmt->fetch(PDO::FETCH_ASSOC);

            $slotDur = (int)($schInfo['slot_duration_minutes'] ?? 10);
            $startTime = $schInfo['start_time'] ?? '16:00:00';
            $offsetMinutes = ($tokenNumber - 1) * $slotDur;

            $estConsultTime = date('h:i A', strtotime("$date $startTime + $offsetMinutes minutes"));
            $arrivalOffset = max(0, $offsetMinutes - 25);
            $expectedArrivalTime = date('h:i A', strtotime("$date $startTime + $arrivalOffset minutes"));

            return [
                'appointment_id'             => $appointmentId,
                'appointment_number'         => $aptNumber,
                'token_display'              => $tokenDisplay,
                'token_number'               => $tokenNumber,
                'appointment_date'           => $date,
                'status'                     => 'CHECKED_IN',
                'payment_status'             => $paymentStatus,
                'consultation_fee'           => $consultationFee,
                'platform_fee'               => $platformFee,
                'discount_amount'            => $discountAmount,
                'total_payable'              => $totalPaid,
                'estimated_consultation_time'=> $estConsultTime,
                'expected_arrival_time'      => $expectedArrivalTime,
                'arrival_note'               => 'অনুগ্রহ করে ডাক্তার দেখার আনুমানিক সময়ের ২০-২৫ মিনিট আগে চেম্বারে উপস্থিত থাকুন।'
            ];

        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
