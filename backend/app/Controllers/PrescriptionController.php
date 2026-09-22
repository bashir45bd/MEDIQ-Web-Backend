<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ConsultationRepository;
use App\Helpers\Response;
use App\Helpers\Database;
use App\Middleware\AuthMiddleware;
use PDO;

class PrescriptionController extends BaseController {
    private ConsultationRepository $consultRepo;

    public function __construct() {
        $this->consultRepo = new ConsultationRepository();
    }

    public function verifyPublicQr(array $params): void {
        $token = $params['token'] ?? '';
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT rx.prescription_code, rx.created_at, rx.is_signed,
                   d.designation, d.bmdc_number, udoc.full_name AS doctor_name,
                   ch.name AS chamber_name, ci.name AS city_name
            FROM prescriptions rx
            JOIN consultations c ON rx.consultation_id = c.id
            JOIN doctors d ON c.doctor_id = d.id
            JOIN users udoc ON d.user_id = udoc.id
            JOIN appointments a ON c.appointment_id = a.id
            JOIN doctor_chambers ch ON a.chamber_id = ch.id
            JOIN cities ci ON ch.city_id = ci.id
            WHERE rx.verification_token = :tok LIMIT 1
        ");
        $stmt->execute(['tok' => $token]);
        $rx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rx) {
            Response::error('INVALID_PRESCRIPTION', 'Prescription QR verification failed. Record not found or invalid token.', 404);
        }

        // Minimalist response: Zero clinical diagnosis or medicine leak! (Section 49 & 69 compliant)
        Response::success([
            'verification_status' => 'AUTHENTIC_MEDIQ_PRESCRIPTION',
            'prescription_code'   => $rx['prescription_code'],
            'doctor_name'         => $rx['doctor_name'],
            'bmdc_number'         => $rx['bmdc_number'],
            'designation'         => $rx['designation'],
            'chamber_name'        => $rx['chamber_name'] . ', ' . $rx['city_name'],
            'issued_at'           => $rx['created_at'],
            'privacy_notice'      => 'Patient clinical diagnosis, symptoms, and prescribed medications are confidential and accessible only to the authenticated patient via their MEDIQ account.'
        ], 'Prescription authenticity successfully verified.');
    }

    public function streamPdf(array $params): void {
        $user = AuthMiddleware::handle();
        $rxId = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT rx.prescription_code, rx.created_at, c.id AS consultation_id, c.appointment_id, c.patient_id, c.doctor_id
            FROM prescriptions rx
            JOIN consultations c ON rx.consultation_id = c.id
            WHERE rx.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $rxId]);
        $rx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rx) {
            Response::error('NOT_FOUND', 'Prescription not found.', 404);
        }

        // Authorization check
        if ($user['role'] === 'PATIENT' && (int)$rx['patient_id'] !== (int)$user['patient_id']) {
            Response::error('FORBIDDEN', 'Access denied to this prescription.', 403);
        }
        if ($user['role'] === 'DOCTOR' && (int)$rx['doctor_id'] !== (int)$user['doctor_id']) {
            Response::error('FORBIDDEN', 'Access denied to this prescription.', 403);
        }

        $fullRecord = $this->consultRepo->findByAppointment((int)$rx['appointment_id']);

        // Return clean printable HTML/PDF structure
        header('Content-Type: text/html; charset=utf-8');
        echo $this->renderPrintablePrescription($fullRecord);
        exit;
    }

    private function renderPrintablePrescription(array $data): string {
        $doctorName   = htmlspecialchars($data['doctor_name'] ?? 'Doctor', ENT_QUOTES, 'UTF-8');
        $desig        = htmlspecialchars($data['designation'] ?? 'Medical Practitioner', ENT_QUOTES, 'UTF-8');
        $bmdc         = htmlspecialchars($data['bmdc_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $patientName  = htmlspecialchars($data['patient_name'] ?? 'Patient', ENT_QUOTES, 'UTF-8');
        $patientPhone = htmlspecialchars($data['patient_phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $gender       = htmlspecialchars($data['gender'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $bloodGroup   = htmlspecialchars($data['blood_group'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $dob          = $data['dob'] ?? null;
        $ageStr       = $dob ? (date_diff(date_create($dob), date_create('today'))->y . ' Yrs') : 'Adult';
        
        $chamberName  = htmlspecialchars($data['chamber_name'] ?? 'Chamber', ENT_QUOTES, 'UTF-8');
        $chamberAddr  = htmlspecialchars($data['chamber_address'] ?? '', ENT_QUOTES, 'UTF-8');
        $rxCode       = htmlspecialchars($data['prescription_code'] ?? 'RX-' . date('Ymd') . '-001', ENT_QUOTES, 'UTF-8');
        $verifToken   = htmlspecialchars($data['verification_token'] ?? '', ENT_QUOTES, 'UTF-8');
        $dateFormatted= date('d M Y', strtotime($data['created_at'] ?? 'now'));
        $timeFormatted= date('h:i A', strtotime($data['created_at'] ?? 'now'));
        $followUp     = !empty($data['follow_up_date']) ? date('d M Y', strtotime($data['follow_up_date'])) : 'As needed / প্রয়োজনমতো';

        $symptoms     = nl2br(htmlspecialchars($data['symptoms'] ?? 'None recorded', ENT_QUOTES, 'UTF-8'));
        $clinicalNotes= nl2br(htmlspecialchars($data['clinical_notes'] ?? 'Normal examination', ENT_QUOTES, 'UTF-8'));

        // Diagnoses
        $diagHtml = '';
        if (!empty($data['diagnoses'])) {
            foreach ($data['diagnoses'] as $d) {
                $cname = htmlspecialchars($d['condition_name'], ENT_QUOTES, 'UTF-8');
                $icd   = !empty($d['icd10_code']) ? ' <span style="color:#64748b; font-size:11px;">[' . htmlspecialchars($d['icd10_code'], ENT_QUOTES, 'UTF-8') . ']</span>' : '';
                $diagHtml .= "<div style='margin-bottom: 5px; font-weight: 600; color: #0f172a;'>• {$cname}{$icd}</div>";
            }
        } else {
            $diagHtml = "<div style='color:#64748b; font-size:12px;'>Clinical evaluation</div>";
        }

        // Investigations
        $invHtml = '';
        if (!empty($data['investigations'])) {
            foreach ($data['investigations'] as $inv) {
                $tname = htmlspecialchars(is_array($inv) ? $inv['test_name'] : $inv, ENT_QUOTES, 'UTF-8');
                $invHtml .= "<div style='margin-bottom: 4px; color: #1e293b;'>• {$tname}</div>";
            }
        } else {
            $invHtml = "<div style='color:#94a3b8; font-size:12px; font-style: italic;'>No tests advised</div>";
        }

        // Medicines Table Rows
        $medHtml = '';
        if (!empty($data['medicines'])) {
            foreach ($data['medicines'] as $idx => $m) {
                $num  = $idx + 1;
                $name = htmlspecialchars($m['medicine_name'], ENT_QUOTES, 'UTF-8');
                $dos  = htmlspecialchars($m['dosage'] ?? '1 tablet', ENT_QUOTES, 'UTF-8');
                $freq = htmlspecialchars($m['frequency'] ?? '1+0+1', ENT_QUOTES, 'UTF-8');
                $dur  = htmlspecialchars($m['duration'] ?? '7 days', ENT_QUOTES, 'UTF-8');
                $inst = htmlspecialchars($m['instructions'] ?? 'After meals', ENT_QUOTES, 'UTF-8');

                $medHtml .= "
                <div style='background:#ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);'>
                    <div style='display: flex; justify-content: space-between; align-items: baseline;'>
                        <div style='font-size: 15px; font-weight: 700; color: #0f172a;'>
                            <span style='color: #0284c7; margin-right: 6px;'>{$num}.</span> {$name}
                        </div>
                        <div style='font-size: 13px; font-weight: 600; color: #0369a1; background: #e0f2fe; padding: 2px 10px; border-radius: 99px;'>
                            {$dur}
                        </div>
                    </div>
                    <div style='display: flex; gap: 20px; margin-top: 6px; font-size: 13px;'>
                        <div><strong>Dose:</strong> <span style='color: #334155;'>{$dos}</span></div>
                        <div><strong>Schedule:</strong> <span style='color: #0f172a; font-weight: 700;'>{$freq}</span></div>
                        <div><strong>Instruction:</strong> <span style='color: #047857; font-weight: 600;'>{$inst}</span></div>
                    </div>
                </div>";
            }
        } else {
            $medHtml = "<div style='color:#64748b; padding: 16px;'>No medicines prescribed.</div>";
        }

        // Advice
        $advHtml = '';
        if (!empty($data['advice'])) {
            foreach ($data['advice'] as $adv) {
                $atext = htmlspecialchars(is_array($adv) ? $adv['advice_text'] : $adv, ENT_QUOTES, 'UTF-8');
                $advHtml .= "<li style='margin-bottom: 6px; color: #1e293b;'>{$atext}</li>";
            }
        } else {
            $advHtml = "<li>Take prescribed medicines regularly and follow general health guidelines.</li>";
        }

        // Public QR Verification URL
        $qrVerifyUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/mediq/backend/public/api/v1/prescriptions/verify/{$verifToken}";

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescription {$rxCode} — MEDIQ Healthcare</title>
<style>
  @page {
    size: A4 portrait;
    margin: 12mm 15mm 15mm 15mm;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #0f172a;
    background: #f8fafc;
    line-height: 1.45;
  }
  .print-actions {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    gap: 10px;
  }
  .btn-print {
    background: #0284c7;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 700;
    border-radius: 6px;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
  }
  .btn-print:hover { background: #0369a1; }
  
  .page-container {
    max-width: 850px;
    margin: 20px auto;
    background: #ffffff;
    padding: 30px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
    position: relative;
  }
  
  /* Top Hospital Header */
  .rx-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px solid #0284c7;
    padding-bottom: 18px;
  }
  .doc-name {
    font-size: 22px;
    font-weight: 800;
    color: #0369a1;
    letter-spacing: -0.3px;
  }
  .doc-degrees {
    font-size: 13px;
    color: #334155;
    font-weight: 600;
    margin-top: 3px;
    line-height: 1.3;
  }
  .doc-bmdc {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 2px 8px;
    background: #e0f2fe;
    color: #0284c7;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
  }
  .chamber-info {
    text-align: right;
    font-size: 13px;
    color: #475569;
  }
  .brand-logo {
    font-size: 20px;
    font-weight: 900;
    color: #0284c7;
    letter-spacing: 0.5px;
  }
  
  /* Patient Bar */
  .patient-strip {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 16px;
    margin: 16px 0 20px 0;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.2fr;
    gap: 12px;
    font-size: 13px;
  }
  .patient-strip div strong { color: #475569; font-size: 11px; text-transform: uppercase; display: block; }
  .patient-strip div span { color: #0f172a; font-weight: 700; font-size: 14px; }
  
  /* Body Two-Column */
  .rx-body {
    display: flex;
    gap: 24px;
    min-height: 580px;
  }
  .rx-sidebar {
    width: 32%;
    border-right: 1.5px solid #e2e8f0;
    padding-right: 18px;
  }
  .sidebar-section {
    margin-bottom: 20px;
  }
  .sidebar-heading {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #0284c7;
    border-bottom: 1.5px solid #bae6fd;
    padding-bottom: 4px;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
  }
  .sidebar-content {
    font-size: 12.5px;
    color: #334155;
    line-height: 1.5;
  }
  
  .rx-main {
    width: 68%;
    padding-left: 6px;
  }
  .rx-symbol {
    font-size: 32px;
    font-weight: 900;
    font-family: serif;
    color: #0284c7;
    line-height: 1;
    margin-bottom: 12px;
  }
  
  /* Advice & Follow-up */
  .advice-box {
    margin-top: 24px;
    padding: 14px 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #10b981;
    border-radius: 6px;
  }
  .advice-title {
    font-size: 13px;
    font-weight: 700;
    color: #047857;
    margin-bottom: 6px;
    text-transform: uppercase;
  }
  .advice-box ul {
    padding-left: 18px;
    font-size: 13px;
  }
  
  .followup-strip {
    margin-top: 14px;
    padding: 10px 14px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 6px;
    font-size: 13px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  /* Footer */
  .rx-footer {
    margin-top: 40px;
    border-top: 2px solid #e2e8f0;
    padding-top: 16px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
  }
  .qr-box {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .qr-code-placeholder {
    width: 58px;
    height: 58px;
    border: 1.5px solid #0284c7;
    padding: 3px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .qr-meta {
    font-size: 11px;
    color: #64748b;
    max-width: 250px;
  }
  .signature-box {
    text-align: right;
  }
  .sig-line {
    width: 180px;
    border-top: 1px dashed #64748b;
    margin-bottom: 6px;
  }
  .sig-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
  }
  .sig-title {
    font-size: 11px;
    color: #64748b;
  }
  .bottom-disclaimer {
    margin-top: 14px;
    text-align: center;
    font-size: 10.5px;
    color: #94a3b8;
    border-top: 1px dotted #e2e8f0;
    padding-top: 6px;
  }
  
  @media print {
    body { background: #ffffff; }
    .print-actions { display: none !important; }
    .page-container {
      margin: 0 !important;
      padding: 0 !important;
      border: none !important;
      box-shadow: none !important;
      max-width: 100% !important;
    }
  }
</style>
</head>
<body>

<div class="print-actions">
  <button class="btn-print" onclick="window.print()">🖨️ Print Prescription</button>
</div>

<div class="page-container">
  <!-- Header -->
  <div class="rx-header">
    <div>
      <div class="doc-name">{$doctorName}</div>
      <div class="doc-degrees">{$desig}</div>
      <div class="doc-bmdc">
        <span>&#10003; BMDC Reg No:</span> <strong>{$bmdc}</strong>
      </div>
    </div>
    <div class="chamber-info">
      <div class="brand-logo">MEDIQ HEALTHCARE</div>
      <div style="font-weight: 700; color: #1e293b; margin-top: 2px;">{$chamberName}</div>
      <div>{$chamberAddr}</div>
      <div style="font-size: 12px; margin-top: 4px; color: #0284c7; font-weight: 600;">Rx Code: {$rxCode}</div>
    </div>
  </div>

  <!-- Patient Details -->
  <div class="patient-strip">
    <div>
      <strong>Patient Name</strong>
      <span>{$patientName}</span>
    </div>
    <div>
      <strong>Age / Gender</strong>
      <span>{$ageStr} &bull; {$gender}</span>
    </div>
    <div>
      <strong>Blood Group</strong>
      <span>{$bloodGroup}</span>
    </div>
    <div style="text-align: right;">
      <strong>Date & Time</strong>
      <span>{$dateFormatted}, {$timeFormatted}</span>
    </div>
  </div>

  <!-- Clinical Two-Column Body -->
  <div class="rx-body">
    <!-- Left Sidebar: Symptoms, Findings, Dx, Tests -->
    <div class="rx-sidebar">
      <div class="sidebar-section">
        <div class="sidebar-heading">Chief Complaints (C/C)</div>
        <div class="sidebar-content">{$symptoms}</div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-heading">On Examination (O/E)</div>
        <div class="sidebar-content">{$clinicalNotes}</div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-heading">Provisional Diagnosis</div>
        <div class="sidebar-content">{$diagHtml}</div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-heading">Investigations Advised</div>
        <div class="sidebar-content">{$invHtml}</div>
      </div>
    </div>

    <!-- Right Main Column: ℞ Medicines, Advice, Follow-up -->
    <div class="rx-main">
      <div class="rx-symbol">&#8478;</div>

      <div class="medicines-list">
        {$medHtml}
      </div>

      <!-- Doctor Advice -->
      <div class="advice-box">
        <div class="advice-title">পরামর্শ ও নির্দেশিকা / Clinical Advice</div>
        <ul>
          {$advHtml}
        </ul>
      </div>

      <!-- Follow-up Date -->
      <div class="followup-strip">
        <span><strong>পরবর্তী সাক্ষাতের সম্ভাব্য তারিখ / Next Visit:</strong></span>
        <span style="font-weight: 800; color: #92400e; font-size: 14px;">{$followUp}</span>
      </div>
    </div>
  </div>

  <!-- Footer with QR Verification & Signature -->
  <div class="rx-footer">
    <div class="qr-box">
      <div class="qr-code-placeholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
      </div>
      <div class="qr-meta">
        <strong style="color: #0284c7;">MEDIQ Verified Digital Prescription</strong><br>
        Scan QR to authenticate practitioner credentials & validity.<br>
        <span style="font-family: monospace; font-size: 9.5px; color: #94a3b8;">Ref: {$verifToken}</span>
      </div>
    </div>

    <div class="signature-box">
      <div class="sig-line"></div>
      <div class="sig-name">{$doctorName}</div>
      <div class="sig-title">{$desig} &bull; BMDC: {$bmdc}</div>
    </div>
  </div>

  <div class="bottom-disclaimer">
    This digital prescription is electronically generated & secured by MEDIQ Healthcare Platform. No manual signature required when authenticated via QR verification.
  </div>
</div>

</body>
</html>
HTML;
    }
}
