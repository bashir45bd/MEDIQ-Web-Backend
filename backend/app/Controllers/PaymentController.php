<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use App\Helpers\Response;
use App\Helpers\Database;
use App\Validators\Validator;
use PDO;
use Throwable;

class PaymentController extends BaseController {
    public function initiate(): void {
        $user = RoleMiddleware::requireRole('PATIENT');
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['appointment_id', 'gateway_name'])
            ->in('gateway_name', ['bkash', 'nagad', 'sslcommerz', 'mock'])
            ->validateOrAbort();

        $aptId = (int)$body['appointment_id'];
        $gateway = $body['gateway_name'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT total_paid, payment_status, patient_id FROM appointments WHERE id = :id");
        $stmt->execute(['id' => $aptId]);
        $apt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$apt || (int)$apt['patient_id'] !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied to this appointment.', 403);
        }

        if ($apt['payment_status'] === 'PAID') {
            Response::error('ALREADY_PAID', 'This appointment platform fee is already paid.', 409);
        }

        $amount = (float)$apt['total_paid'];
        $txnRef = 'TXN-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

        $ins = $pdo->prepare("
            INSERT INTO payments (appointment_id, user_id, transaction_ref, gateway_name, amount, currency, status, created_at)
            VALUES (:aid, :uid, :ref, :gw, :amt, 'BDT', 'INITIATED', NOW())
        ");
        $ins->execute([
            'aid' => $aptId,
            'uid' => $user['id'],
            'ref' => $txnRef,
            'gw'  => $gateway,
            'amt' => $amount
        ]);

        Response::success([
            'transaction_ref' => $txnRef,
            'amount'          => $amount,
            'currency'        => 'BDT',
            'checkout_url'    => "https://checkout.mediq.com.bd/pay/{$txnRef}"
        ], 'Payment checkout initiated.');
    }

    /**
     * Idempotent server-side payment webhook handler
     */
    public function webhook(array $params): void {
        $gateway = $params['gateway'] ?? 'mock';
        $body = $this->getJsonBody();

        $txnRef = $body['transaction_ref'] ?? $body['trx_id'] ?? null;
        if (!$txnRef) {
            Response::error('INVALID_PAYLOAD', 'transaction_ref is required.', 422);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Pessimistic Lock on payment record to prevent race conditions & double-booking
            $stmt = $pdo->prepare("SELECT id, appointment_id, amount, status FROM payments WHERE transaction_ref = :ref FOR UPDATE");
            $stmt->execute(['ref' => $txnRef]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $pdo->rollBack();
                Response::error('TRANSACTION_NOT_FOUND', 'Payment transaction not found.', 404);
            }

            // IDEMPOTENCY CHECK
            if ($payment['status'] === 'SUCCESSFUL') {
                $pdo->commit();
                Response::success(['idempotent' => true], 'Payment was already processed successfully.');
                return;
            }

            // Mark Payment SUCCESSFUL
            $upPay = $pdo->prepare("
                UPDATE payments 
                SET status = 'SUCCESSFUL', verified_at = NOW(), gateway_payload = :pld
                WHERE id = :id
            ");
            $upPay->execute([
                'pld' => json_encode($body),
                'id'  => $payment['id']
            ]);

            // Update Appointment status to PAID
            $upApt = $pdo->prepare("
                UPDATE appointments 
                SET payment_status = 'PAID'
                WHERE id = :aid
            ");
            $upApt->execute(['aid' => $payment['appointment_id']]);

            // Insert into Platform Financial Ledger
            $amount = (float)$payment['amount'];
            $lastBalStmt = $pdo->query("SELECT balance_after FROM platform_transactions ORDER BY id DESC LIMIT 1");
            $lastBal = (float)($lastBalStmt->fetchColumn() ?: 0.00);
            $newBal = $lastBal + $amount;

            $insLedger = $pdo->prepare("
                INSERT INTO platform_transactions (payment_id, transaction_type, amount, balance_after, created_at)
                VALUES (:pid, 'FEE_COLLECTION', :amt, :bal, NOW())
            ");
            $insLedger->execute([
                'pid' => $payment['id'],
                'amt' => $amount,
                'bal' => $newBal
            ]);

            $pdo->commit();

            Response::success([
                'status' => 'SUCCESSFUL',
                'appointment_id' => (int)$payment['appointment_id'],
                'balance_after'  => $newBal
            ], 'Payment verified and appointment confirmed.');

        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function applyPromo(): void {
        $body = $this->getJsonBody();
        Validator::make($body)
            ->required(['promo_code'])
            ->validateOrAbort();

        $code = trim((string)$body['promo_code']);
        $date = date('Y-m-d');
        $platformFee = 50.00;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM promotions 
            WHERE code = :code AND status = 'ACTIVE' AND start_date <= :dt1 AND end_date >= :dt2
            LIMIT 1
        ");
        $stmt->execute(['code' => $code, 'dt1' => $date, 'dt2' => $date]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            Response::error('PROMOTION_INVALID', 'Promo code is invalid, expired, or inactive.', 404);
        }

        $discount = 0.00;
        if ($promo['type'] === 'FREE') {
            $discount = $platformFee;
        } elseif ($promo['type'] === 'PERCENT') {
            $disc = ($platformFee * (float)$promo['discount_value']) / 100.0;
            if (!empty($promo['max_discount'])) {
                $disc = min($disc, (float)$promo['max_discount']);
            }
            $discount = $disc;
        } elseif ($promo['type'] === 'FIXED') {
            $discount = min((float)$promo['discount_value'], $platformFee);
        }

        Response::success([
            'promo_name'    => $promo['name'],
            'promo_code'    => $promo['code'],
            'discount'      => $discount,
            'payable_fee'   => max(0.00, $platformFee - $discount)
        ], 'Promotion applied successfully.');
    }
}
