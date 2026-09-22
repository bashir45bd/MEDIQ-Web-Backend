<?php
$activePage = 'patients';
$pageTitle = 'Patient Management & Serial Booking';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patients & Serial Booking — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .avatar-circle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4f46e5, #06b6d4);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 15px;
      text-transform: uppercase;
      flex-shrink: 0;
    }
    .badge-blood {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      background: #fee2e2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
      z-index: 999;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow-y: auto;
    }
    .modal-content {
      background: #ffffff;
      border-radius: 12px;
      width: 100%;
      max-width: 680px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
      border: 1px solid #e2e8f0;
      animation: modalSlideUp 0.25s ease-out;
    }
    @keyframes modalSlideUp {
      from { opacity: 0; transform: translateY(16px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f8fafc;
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }
    .modal-header h3 {
      font-size: 18px;
      font-weight: 700;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .modal-close {
      background: transparent;
      border: none;
      font-size: 22px;
      cursor: pointer;
      color: #64748b;
      line-height: 1;
      padding: 4px 8px;
      border-radius: 4px;
      transition: background 0.15s;
    }
    .modal-close:hover {
      background: #e2e8f0;
      color: #0f172a;
    }
    .modal-body {
      padding: 24px;
    }
    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      background: #f8fafc;
      border-bottom-left-radius: 12px;
      border-bottom-right-radius: 12px;
    }
    /* Printable token card */
    .token-print-card {
      border: 2px dashed #4f46e5;
      background: #f8faff;
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      margin: 16px 0;
    }
    .token-number-hero {
      font-size: 48px;
      font-weight: 900;
      color: #4f46e5;
      letter-spacing: 2px;
      margin: 10px 0;
    }
    @media print {
      body * {
        visibility: hidden;
      }
      #printContainer, #printContainer * {
        visibility: visible;
      }
      #printContainer {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
      }
    }
  </style>
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <!-- Notice Alert Box -->
      <div id="noticeBox" style="display: none; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);"></div>

      <!-- Overview Metric Cards -->
      <div class="grid grid-cols-4" style="margin-bottom: 24px;">
        <div class="card" style="border-left: 4px solid #4f46e5;">
          <div class="card-header">
            <span class="card-title">Total Patients</span>
            <span style="font-size: 20px;">👥</span>
          </div>
          <div class="card-value" id="statTotalPatients">--</div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Registered on platform</div>
        </div>
        <div class="card" style="border-left: 4px solid #10b981;">
          <div class="card-header">
            <span class="card-title">Active Accounts</span>
            <span style="font-size: 20px;">✅</span>
          </div>
          <div class="card-value" id="statActivePatients">--</div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Verified & In Good Standing</div>
        </div>
        <div class="card" style="border-left: 4px solid #06b6d4;">
          <div class="card-header">
            <span class="card-title">Total Bookings</span>
            <span style="font-size: 20px;">🎫</span>
          </div>
          <div class="card-value" id="statTotalBookings">--</div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Across all chamber queues</div>
        </div>
        <div class="card" style="border-left: 4px solid #f59e0b;">
          <div class="card-header">
            <span class="card-title">Walk-In / Quick Desk</span>
            <span style="font-size: 20px;">🏥</span>
          </div>
          <div class="card-value" style="font-size: 18px; font-weight: 700; color: #d97706; padding-top: 8px;">Direct Booking</div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Instant serial & token issue</div>
        </div>
      </div>

      <!-- Action Toolbar & Search/Filter Card -->
      <div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <!-- Search & Filter Controls -->
          <div style="display: flex; gap: 12px; flex-wrap: wrap; flex-grow: 1; max-width: 750px;">
            <div style="position: relative; flex-grow: 1; min-width: 220px;">
              <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search patient name, phone, email..." style="padding-left: 36px;">
              <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></span>
            </div>
            <select id="statusFilter" class="form-control" style="width: auto; min-width: 140px;">
              <option value="">All Statuses</option>
              <option value="ACTIVE">Active</option>
              <option value="SUSPENDED">Suspended</option>
            </select>
            <select id="genderFilter" class="form-control" style="width: auto; min-width: 130px;">
              <option value="">All Genders</option>
              <option value="MALE">Male</option>
              <option value="FEMALE">Female</option>
              <option value="OTHER">Other</option>
            </select>
            <select id="bloodFilter" class="form-control" style="width: auto; min-width: 130px;">
              <option value="">Blood Group</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
              <option value="O+">O+</option>
              <option value="O-">O-</option>
            </select>
          </div>

          <!-- Action Buttons -->
          <div style="display: flex; align-items: center; gap: 10px;">
            <button id="btnOpenQuickRegister" class="btn btn-outline" style="font-weight: 600;">
              ➕ Quick Register
            </button>
            <button id="btnOpenBookSerial" class="btn btn-primary" style="font-weight: 700; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);">
              🎫 Book Doctor Serial
            </button>
          </div>
        </div>
      </div>

      <!-- Patients Table -->
      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Patient Details</th>
                <th>Contact Info</th>
                <th>Demographics</th>
                <th>Appointments</th>
                <th>Last Visit</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
              </tr>
            </thead>
            <tbody id="patientsTableBody">
              <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Loading patients data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BOOK SERIAL ON BEHALF OF PATIENT -->
<!-- ========================================================================= -->
<div id="bookSerialModal" class="modal-backdrop">
  <div class="modal-content">
    <div class="modal-header">
      <h3>🎫 Book Doctor Serial For Patient</h3>
      <button class="modal-close" data-close="bookSerialModal">&times;</button>
    </div>
    <form id="bookSerialForm">
      <div class="modal-body">
        <div style="background: #eef2ff; border-left: 4px solid #4f46e5; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #3730a3; margin-bottom: 20px;">
          <strong>Pessimistic Concurrency Protection:</strong> Serials booked from this desk lock the chamber capacity in real-time, preventing token collision and generating an immediate guaranteed queue slip.
        </div>

        <!-- Mode Toggle: Existing vs New Walk-in -->
        <div class="form-group">
          <label class="form-label" style="font-weight: 700;">Patient Booking Type</label>
          <div style="display: flex; gap: 16px; margin-top: 6px;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px;">
              <input type="radio" name="patientMode" value="existing" checked id="modeExisting"> Existing Registered Patient
            </label>
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px;">
              <input type="radio" name="patientMode" value="walkin" id="modeWalkin"> New Walk-in Patient (Instant Reg)
            </label>
          </div>
        </div>

        <!-- Existing Patient Selector -->
        <div id="existingPatientSection" class="form-group">
          <label class="form-label">Select Registered Patient *</label>
          <select id="bookPatientSelect" class="form-control" required>
            <option value="">-- Choose Patient --</option>
          </select>
        </div>

        <!-- New Walk-in Fields -->
        <div id="walkinPatientSection" style="display: none;">
          <div class="grid grid-cols-2">
            <div class="form-group">
              <label class="form-label">Patient Full Name *</label>
              <input type="text" id="walkinName" class="form-control" placeholder="e.g. Rahim Ullah">
            </div>
            <div class="form-group">
              <label class="form-label">Mobile Phone *</label>
              <input type="text" id="walkinPhone" class="form-control" placeholder="017XXXXXXXX">
            </div>
          </div>
          <div class="grid grid-cols-2">
            <div class="form-group">
              <label class="form-label">Gender</label>
              <select id="walkinGender" class="form-control">
                <option value="MALE">Male</option>
                <option value="FEMALE">Female</option>
                <option value="OTHER">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Address / District</label>
              <input type="text" id="walkinAddress" class="form-control" placeholder="e.g. Dhanmondi, Dhaka">
            </div>
          </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        <!-- Doctor & Chamber Selection -->
        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Select Doctor *</label>
            <select id="bookDoctorSelect" class="form-control" required>
              <option value="">-- Select Verified Doctor --</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Doctor Chamber *</label>
            <select id="bookChamberSelect" class="form-control" required disabled>
              <option value="">-- Select Doctor First --</option>
            </select>
          </div>
        </div>

        <!-- Date & Consultation Details -->
        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Appointment Date *</label>
            <input type="date" id="bookAppointmentDate" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Patient Consultation Type</label>
            <select id="bookPatientType" class="form-control">
              <option value="NEW">New Consultation</option>
              <option value="FOLLOW_UP">Follow-up Visit</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Promo / Discount Code (Optional)</label>
            <input type="text" id="bookPromoCode" class="form-control" placeholder="e.g. MEDIQFREE, WELCOME50">
          </div>
          <div class="form-group">
            <label class="form-label">Estimated Fee</label>
            <div id="feeBreakdown" style="padding: 10px 14px; background: #f1f5f9; border-radius: 6px; font-size: 13px; font-weight: 600; color: #334155;">
              Doctor Fee: ৳<span id="displayDocFee">0</span> | Platform Fee: ৳<span id="displayPlatFee">50</span>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close="bookSerialModal">Cancel</button>
        <button type="submit" id="btnSubmitBooking" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
          Confirm Serial & Issue Token 🎟️
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BOOKING CONFIRMATION & PRINTABLE RECEIPT -->
<!-- ========================================================================= -->
<div id="bookingSuccessModal" class="modal-backdrop">
  <div class="modal-content" style="max-width: 540px;">
    <div class="modal-header" style="background: #ecfdf5; border-bottom-color: #a7f3d0;">
      <h3 style="color: #065f46;">🎉 Serial Confirmed Successfully!</h3>
      <button class="modal-close" data-close="bookingSuccessModal">&times;</button>
    </div>
    <div class="modal-body" id="printContainer">
      <div class="token-print-card">
        <div style="font-size: 13px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 1px;">
          MEDIQ Guaranteed Chamber Serial
        </div>
        <div class="token-number-hero" id="confirmTokenDisplay">--</div>
        <div style="font-size: 14px; font-weight: 600; color: #1e293b;" id="confirmAptNumber">APT-XXXXXXXX</div>
      </div>

      <div style="background: #f8fafc; border-radius: 8px; padding: 16px; font-size: 14px; line-height: 1.8; border: 1px solid #e2e8f0;">
        <div><strong>Patient:</strong> <span id="confirmPatientName">--</span> (<span id="confirmPatientPhone">--</span>)</div>
        <div><strong>Doctor:</strong> <span id="confirmDoctorName">--</span></div>
        <div><strong>Chamber:</strong> <span id="confirmChamberName">--</span></div>
        <div><strong>Address:</strong> <span id="confirmChamberAddress">--</span></div>
        <div><strong>Date:</strong> <span id="confirmDate">--</span></div>
        <div><strong>Total Paid:</strong> ৳<span id="confirmTotalPaid">--</span> (<span id="confirmPaymentStatus" class="status-pill status-verified" style="padding: 2px 8px; font-size: 11px;">PAID</span>)</div>
      </div>

      <div style="text-align: center; margin-top: 16px; font-size: 12px; color: #64748b;">
        Please arrive 15 minutes before the session starts. Present this serial token at the chamber desk.
      </div>
    </div>
    <div class="modal-footer" style="justify-content: space-between;">
      <button type="button" class="btn btn-outline" onclick="window.print()">
        🖨️ Print Token Receipt
      </button>
      <button type="button" class="btn btn-primary" data-close="bookingSuccessModal">
        Done / Back to Patients
      </button>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: QUICK REGISTER PATIENT -->
<!-- ========================================================================= -->
<div id="quickRegisterModal" class="modal-backdrop">
  <div class="modal-content">
    <div class="modal-header">
      <h3>➕ Quick Register Patient (Reception Desk)</h3>
      <button class="modal-close" data-close="quickRegisterModal">&times;</button>
    </div>
    <form id="quickRegisterForm">
      <div class="modal-body">
        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" id="regName" class="form-control" placeholder="Patient's Full Name" required>
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Phone *</label>
            <input type="text" id="regPhone" class="form-control" placeholder="017XXXXXXXX" required>
          </div>
        </div>

        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Email (Optional)</label>
            <input type="email" id="regEmail" class="form-control" placeholder="patient@example.com">
          </div>
          <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" id="regDob" class="form-control">
          </div>
        </div>

        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select id="regGender" class="form-control">
              <option value="MALE">Male</option>
              <option value="FEMALE">Female</option>
              <option value="OTHER">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Blood Group</label>
            <select id="regBloodGroup" class="form-control">
              <option value="">Unknown</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
              <option value="O+">O+</option>
              <option value="O-">O-</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Present Address</label>
          <input type="text" id="regAddress" class="form-control" placeholder="House, Road, Area, City">
        </div>

        <div class="grid grid-cols-2">
          <div class="form-group">
            <label class="form-label">Emergency Contact Name</label>
            <input type="text" id="regEmergName" class="form-control" placeholder="Guardian / Relative Name">
          </div>
          <div class="form-group">
            <label class="form-label">Emergency Contact Phone</label>
            <input type="text" id="regEmergPhone" class="form-control" placeholder="01XXXXXXXXX">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close="quickRegisterModal">Cancel</button>
        <button type="submit" id="btnSubmitQuickReg" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
          Register Patient
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PATIENT DETAILS & APPOINTMENT HISTORY -->
<!-- ========================================================================= -->
<div id="patientDetailsModal" class="modal-backdrop">
  <div class="modal-content" style="max-width: 850px;">
    <div class="modal-header">
      <h3>👤 Patient Profile & History</h3>
      <button class="modal-close" data-close="patientDetailsModal">&times;</button>
    </div>
    <div class="modal-body">
      <!-- Profile Top Header -->
      <div style="display: flex; gap: 16px; align-items: center; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px;">
        <div class="avatar-circle" id="detailAvatar" style="width: 56px; height: 56px; font-size: 22px;">--</div>
        <div>
          <h4 id="detailName" style="font-size: 20px; font-weight: 800; color: #0f172a;">--</h4>
          <div style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
            <span id="detailPhone">--</span> • <span id="detailEmail">--</span>
          </div>
        </div>
        <div style="margin-left: auto; text-align: right;">
          <span id="detailStatusPill" class="status-pill status-active">ACTIVE</span>
          <div style="margin-top: 6px;">
            <button id="btnDetailBookSerial" class="btn btn-primary btn-sm" style="font-size: 12px; font-weight: 700;">
              🎫 Book Serial Now
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-4" style="margin-bottom: 20px;">
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
          <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Blood Group</div>
          <div id="detailBloodGroup" style="font-weight: 700; font-size: 16px; color: #dc2626; margin-top: 2px;">--</div>
        </div>
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
          <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Gender / DOB</div>
          <div id="detailGenderAge" style="font-weight: 600; font-size: 14px; margin-top: 2px;">--</div>
        </div>
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
          <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Emergency Contact</div>
          <div id="detailEmergency" style="font-weight: 600; font-size: 13px; margin-top: 2px;">--</div>
        </div>
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
          <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Total Visits</div>
          <div id="detailTotalVisits" style="font-weight: 700; font-size: 16px; color: #4f46e5; margin-top: 2px;">--</div>
        </div>
      </div>

      <!-- Address & Family Members -->
      <div style="font-size: 13px; margin-bottom: 20px; color: #334155; line-height: 1.6;">
        <div><strong>Residential Address:</strong> <span id="detailAddress">Not provided</span></div>
        <div id="familySection" style="margin-top: 6px;"></div>
      </div>

      <!-- Complete Appointment History -->
      <h5 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
        <span>Appointment & Chamber History</span>
        <span id="historyBadge" style="font-size: 12px; font-weight: 600; color: var(--text-muted);">0 Records</span>
      </h5>

      <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <table class="data-table" style="font-size: 13px;">
          <thead>
            <tr>
              <th>Date & Appt No</th>
              <th>Doctor & Chamber</th>
              <th>Token</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="patientHistoryTbody">
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No appointments found.</td></tr>
          </tbody>
        </table>
      </div>

    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close="patientDetailsModal">Close</button>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT MODULE -->
<!-- ========================================================================= -->
<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  let allPatients = [];
  let allDoctors = [];
  let doctorChambersCache = {};
  let currentDetailPatient = null;

  // Set default appointment date to today
  const todayStr = new Date().toISOString().split('T')[0];
  document.getElementById('bookAppointmentDate').value = todayStr;
  document.getElementById('bookAppointmentDate').min = todayStr;

  // --------------------------------------------------------------------------
  // Initialization & Loading
  // --------------------------------------------------------------------------
  async function init() {
    await Promise.all([loadPatients(), loadDoctorsList()]);
    bindEvents();
  }

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.style.border = type === 'success' ? '1px solid #86efac' : '1px solid #fca5a5';
    box.innerHTML = (type === 'success' ? '✅ ' : '⚠️ ') + msg;
    setTimeout(() => { box.style.display = 'none'; }, 5000);
  }

  async function loadPatients() {
    try {
      const q = document.getElementById('searchInput').value.trim();
      const status = document.getElementById('statusFilter').value;
      const gender = document.getElementById('genderFilter').value;
      const blood = document.getElementById('bloodFilter').value;

      let url = '/admin/patients?';
      const params = new URLSearchParams();
      if (q) params.append('q', q);
      if (status) params.append('status', status);
      if (gender) params.append('gender', gender);
      if (blood) params.append('blood_group', blood);
      url += params.toString();

      const res = await AdminApi.get(url);
      if (res && res.success) {
        allPatients = res.data;
        renderPatientsTable(allPatients);
        updatePatientSelectDropdown(allPatients);
        calculateStats(allPatients);
      }
    } catch (err) {
      console.error('Failed to load patients:', err);
      showNotice(err.message || 'Error fetching patients', 'error');
    }
  }

  async function loadDoctorsList() {
    try {
      const res = await AdminApi.get('/admin/doctors?status=VERIFIED');
      if (res && res.success) {
        allDoctors = res.data;
        const sel = document.getElementById('bookDoctorSelect');
        sel.innerHTML = '<option value="">-- Select Verified Doctor --</option>' +
          allDoctors.map(d => `<option value="${d.id}" data-fee="${d.consultation_fee}" data-followup="${d.follow_up_fee || (d.consultation_fee * 0.6)}">Dr. ${d.doctor_name} (${d.specialties || d.designation}) - ৳${parseFloat(d.consultation_fee).toFixed(0)}</option>`).join('');
      }
    } catch (err) {
      console.error('Failed to load doctors list:', err);
    }
  }

  function calculateStats(patients) {
    document.getElementById('statTotalPatients').innerText = patients.length;
    const active = patients.filter(p => p.user_status === 'ACTIVE').length;
    document.getElementById('statActivePatients').innerText = active;
    const totalApts = patients.reduce((acc, p) => acc + parseInt(p.total_appointments || 0), 0);
    document.getElementById('statTotalBookings').innerText = totalApts;
  }

  function updatePatientSelectDropdown(patients) {
    const sel = document.getElementById('bookPatientSelect');
    sel.innerHTML = '<option value="">-- Choose Patient --</option>' +
      patients.map(p => `<option value="${p.id}" data-name="${p.full_name}" data-phone="${p.phone}">${p.full_name} (${p.phone})</option>`).join('');
  }

  // --------------------------------------------------------------------------
  // Render Patients Table
  // --------------------------------------------------------------------------
  function renderPatientsTable(patients) {
    const tbody = document.getElementById('patientsTableBody');
    if (!patients || patients.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">No patient records found matching the filters.</td></tr>';
      return;
    }

    tbody.innerHTML = patients.map(p => {
      const initials = (p.full_name || 'P').split(' ').map(n => n[0]).join('').substring(0, 2);
      const bloodPill = p.blood_group ? `<span class="badge-blood">${p.blood_group}</span>` : '<span style="color: #94a3b8; font-size: 12px;">--</span>';
      const lastVisit = p.last_visit_date ? new Date(p.last_visit_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '<span style="color: #94a3b8; font-size: 12px;">Never</span>';
      const statusPill = p.user_status === 'ACTIVE'
        ? '<span class="status-pill status-active">ACTIVE</span>'
        : '<span class="status-pill status-suspended">SUSPENDED</span>';

      return `
        <tr>
          <td>
            <div style="display: flex; align-items: center; gap: 12px;">
              <div class="avatar-circle">${initials}</div>
              <div>
                <strong style="color: #0f172a; font-size: 14px;">${p.full_name}</strong>
                <div style="font-size: 11px; color: var(--text-muted);">ID: #PT-${p.id}</div>
              </div>
            </div>
          </td>
          <td>
            <div style="font-weight: 600; font-size: 13px;">${p.phone}</div>
            <div style="font-size: 11px; color: var(--text-muted);">${p.email || 'No email registered'}</div>
          </td>
          <td>
            <div style="font-size: 13px; font-weight: 500;">${p.gender || 'OTHER'}</div>
            <div style="margin-top: 2px;">${bloodPill}</div>
          </td>
          <td>
            <span style="font-weight: 700; color: #4f46e5; font-size: 14px;">${p.total_appointments || 0}</span>
            <span style="font-size: 11px; color: var(--text-muted);"> sessions</span>
          </td>
          <td>${lastVisit}</td>
          <td>${statusPill}</td>
          <td style="text-align: right;">
            <div style="display: inline-flex; gap: 6px;">
              <button class="btn btn-outline btn-sm btn-action-book" data-id="${p.id}" data-name="${p.full_name}" data-phone="${p.phone}" title="Book Doctor Serial for Patient" style="background: #eef2ff; color: #4338ca; border-color: #c7d2fe; font-size: 12px;">
                🎫 Book
              </button>
              <button class="btn btn-outline btn-sm btn-action-view" data-id="${p.id}" title="View Details & Medical History" style="font-size: 12px;">
                👁️ View
              </button>
              ${p.user_status === 'ACTIVE' ? `
                <button class="btn btn-danger btn-sm btn-action-status" data-id="${p.id}" data-status="SUSPENDED" style="padding: 4px 8px; font-size: 11px;">
                  Suspend
                </button>
              ` : `
                <button class="btn btn-success btn-sm btn-action-status" data-id="${p.id}" data-status="ACTIVE" style="padding: 4px 8px; font-size: 11px;">
                  Activate
                </button>
              `}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // --------------------------------------------------------------------------
  // Event Listeners & Modals
  // --------------------------------------------------------------------------
  function bindEvents() {
    // Search input debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(loadPatients, 300);
    });

    document.getElementById('statusFilter').addEventListener('change', loadPatients);
    document.getElementById('genderFilter').addEventListener('change', loadPatients);
    document.getElementById('bloodFilter').addEventListener('change', loadPatients);

    // Close modal handlers
    document.querySelectorAll('[data-close]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const modalId = e.currentTarget.dataset.close;
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
      });
    });

    // Open Quick Register Modal
    document.getElementById('btnOpenQuickRegister').addEventListener('click', () => {
      document.getElementById('quickRegisterForm').reset();
      openModal('quickRegisterModal');
    });

    // Open Book Serial Modal
    document.getElementById('btnOpenBookSerial').addEventListener('click', () => {
      openBookingModal();
    });

    // Radio toggle patientMode
    document.getElementsByName('patientMode').forEach(radio => {
      radio.addEventListener('change', (e) => {
        const isExisting = e.target.value === 'existing';
        document.getElementById('existingPatientSection').style.display = isExisting ? 'block' : 'none';
        document.getElementById('walkinPatientSection').style.display = isExisting ? 'none' : 'block';
        document.getElementById('bookPatientSelect').required = isExisting;
        document.getElementById('walkinName').required = !isExisting;
        document.getElementById('walkinPhone').required = !isExisting;
      });
    });

    // Doctor selection change -> load chambers
    document.getElementById('bookDoctorSelect').addEventListener('change', async (e) => {
      const docId = e.target.value;
      const chamberSelect = document.getElementById('bookChamberSelect');
      chamberSelect.innerHTML = '<option value="">-- Loading chambers... --</option>';
      chamberSelect.disabled = true;

      updateFeeDisplay();

      if (!docId) {
        chamberSelect.innerHTML = '<option value="">-- Select Doctor First --</option>';
        return;
      }

      try {
        if (!doctorChambersCache[docId]) {
          const res = await AdminApi.get(`/admin/doctors/${docId}/details`);
          if (res && res.success) {
            doctorChambersCache[docId] = res.data.chambers || [];
          }
        }

        const chambers = doctorChambersCache[docId] || [];
        if (chambers.length === 0) {
          chamberSelect.innerHTML = '<option value="">No active chambers found for doctor</option>';
          chamberSelect.disabled = true;
          return;
        }

        chamberSelect.innerHTML = '<option value="">-- Choose Chamber --</option>' +
          chambers.map(c => `<option value="${c.id}">${c.name} - ${c.address} (${c.room_number ? 'Room ' + c.room_number : 'Main'})</option>`).join('');
        chamberSelect.disabled = false;
      } catch (err) {
        console.error('Error fetching doctor details:', err);
        chamberSelect.innerHTML = '<option value="">Error loading chambers</option>';
      }
    });

    // Patient consultation type change -> update fee
    document.getElementById('bookPatientType').addEventListener('change', updateFeeDisplay);

    // Table delegated actions
    document.getElementById('patientsTableBody').addEventListener('click', async (e) => {
      const target = e.target.closest('button');
      if (!target) return;

      const patientId = target.dataset.id;
      if (!patientId) return;

      if (target.classList.contains('btn-action-book')) {
        openBookingModal(patientId);
      } else if (target.classList.contains('btn-action-view')) {
        openPatientDetails(patientId);
      } else if (target.classList.contains('btn-action-status')) {
        const newStatus = target.dataset.status;
        const confirmMsg = newStatus === 'SUSPENDED'
          ? 'Are you sure you want to suspend this patient account? They will not be able to book serials.'
          : 'Activate this patient account?';
        if (confirm(confirmMsg)) {
          try {
            await AdminApi.put(`/admin/patients/${patientId}/status`, { status: newStatus });
            showNotice(`Patient account marked as ${newStatus}`);
            loadPatients();
          } catch (err) {
            alert(err.message || 'Failed to update patient status');
          }
        }
      }
    });

    // Book Serial Form Submit
    document.getElementById('bookSerialForm').addEventListener('submit', handleBookSerialSubmit);

    // Quick Register Form Submit
    document.getElementById('quickRegisterForm').addEventListener('submit', handleQuickRegisterSubmit);
  }

  function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'flex';
  }

  function updateFeeDisplay() {
    const docSelect = document.getElementById('bookDoctorSelect');
    const selectedOption = docSelect.selectedOptions[0];
    const pType = document.getElementById('bookPatientType').value;

    let docFee = 0;
    if (selectedOption && selectedOption.dataset.fee) {
      docFee = pType === 'FOLLOW_UP' ? parseFloat(selectedOption.dataset.followup) : parseFloat(selectedOption.dataset.fee);
    }
    document.getElementById('displayDocFee').innerText = docFee.toFixed(0);
  }

  function openBookingModal(preselectedPatientId = null) {
    document.getElementById('bookSerialForm').reset();
    document.getElementById('bookAppointmentDate').value = todayStr;
    document.getElementById('modeExisting').checked = true;
    document.getElementById('existingPatientSection').style.display = 'block';
    document.getElementById('walkinPatientSection').style.display = 'none';
    document.getElementById('bookPatientSelect').required = true;
    document.getElementById('walkinName').required = false;
    document.getElementById('walkinPhone').required = false;

    if (preselectedPatientId) {
      document.getElementById('bookPatientSelect').value = preselectedPatientId;
    }

    updateFeeDisplay();
    openModal('bookSerialModal');
  }

  // --------------------------------------------------------------------------
  // Handle Book Serial Submission
  // --------------------------------------------------------------------------
  async function handleBookSerialSubmit(e) {
    e.preventDefault();
    const btnSubmit = document.getElementById('btnSubmitBooking');
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Verifying Slot & Booking...';

    try {
      const mode = document.querySelector('input[name="patientMode"]:checked').value;
      const docId = parseInt(document.getElementById('bookDoctorSelect').value);
      const chamberId = parseInt(document.getElementById('bookChamberSelect').value);
      const aptDate = document.getElementById('bookAppointmentDate').value;
      const pType = document.getElementById('bookPatientType').value;
      const promo = document.getElementById('bookPromoCode').value.trim() || null;

      const payload = {
        doctor_id: docId,
        chamber_id: chamberId,
        appointment_date: aptDate,
        patient_type: pType,
        promo_code: promo
      };

      if (mode === 'existing') {
        payload.patient_id = parseInt(document.getElementById('bookPatientSelect').value);
      } else {
        payload.full_name = document.getElementById('walkinName').value.trim();
        payload.phone = document.getElementById('walkinPhone').value.trim();
        payload.gender = document.getElementById('walkinGender').value;
        payload.address = document.getElementById('walkinAddress').value.trim() || null;
      }

      const res = await AdminApi.post('/admin/appointments/book-for-patient', payload);
      if (!res.success) {
        throw new Error(res.error?.message || 'Booking failed');
      }

      // Close booking modal
      document.getElementById('bookSerialModal').style.display = 'none';

      // Populate Success Modal
      const bookingData = res.data;
      document.getElementById('confirmTokenDisplay').innerText = bookingData.token_number || bookingData.token_display || 'A001';
      document.getElementById('confirmAptNumber').innerText = bookingData.appointment_number || ('#APT-' + bookingData.appointment_id);

      const docOption = document.getElementById('bookDoctorSelect').selectedOptions[0];
      const chamberOption = document.getElementById('bookChamberSelect').selectedOptions[0];
      document.getElementById('confirmDoctorName').innerText = docOption ? docOption.text.split(' - ৳')[0] : 'Doctor';
      document.getElementById('confirmChamberName').innerText = chamberOption ? chamberOption.text : 'Main Chamber';
      document.getElementById('confirmChamberAddress').innerText = 'Chamber verified & slot secured';
      document.getElementById('confirmDate').innerText = aptDate;
      document.getElementById('confirmTotalPaid').innerText = parseFloat(bookingData.total_paid || 0).toFixed(0);

      if (mode === 'existing') {
        const patientOpt = document.getElementById('bookPatientSelect').selectedOptions[0];
        document.getElementById('confirmPatientName').innerText = patientOpt ? patientOpt.dataset.name : 'Patient';
        document.getElementById('confirmPatientPhone').innerText = patientOpt ? patientOpt.dataset.phone : '';
      } else {
        document.getElementById('confirmPatientName').innerText = payload.full_name;
        document.getElementById('confirmPatientPhone').innerText = payload.phone;
      }

      openModal('bookingSuccessModal');
      showNotice('Guaranteed Serial successfully generated and booked for patient!');
      loadPatients(); // refresh stats & table
    } catch (err) {
      console.error(err);
      alert('Booking Error: ' + (err.message || 'Unable to book appointment'));
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerText = 'Confirm Serial & Issue Token 🎟️';
    }
  }

  // --------------------------------------------------------------------------
  // Handle Quick Register
  // --------------------------------------------------------------------------
  async function handleQuickRegisterSubmit(e) {
    e.preventDefault();
    const btnSubmit = document.getElementById('btnSubmitQuickReg');
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Registering Patient...';

    try {
      const payload = {
        full_name: document.getElementById('regName').value.trim(),
        phone: document.getElementById('regPhone').value.trim(),
        email: document.getElementById('regEmail').value.trim() || null,
        date_of_birth: document.getElementById('regDob').value || null,
        gender: document.getElementById('regGender').value,
        blood_group: document.getElementById('regBloodGroup').value || null,
        address: document.getElementById('regAddress').value.trim() || null,
        emergency_contact_name: document.getElementById('regEmergName').value.trim() || null,
        emergency_contact_phone: document.getElementById('regEmergPhone').value.trim() || null
      };

      const res = await AdminApi.post('/admin/patients/quick-register', payload);
      if (!res.success) {
        throw new Error(res.error?.message || 'Registration failed');
      }

      document.getElementById('quickRegisterModal').style.display = 'none';
      showNotice('Patient account registered successfully!');
      await loadPatients();

      // Offer immediate serial booking
      if (confirm(`Patient registered successfully! Would you like to book a chamber serial for ${payload.full_name} right now?`)) {
        openBookingModal(res.data.patient_id);
      }
    } catch (err) {
      console.error(err);
      alert('Registration Error: ' + (err.message || 'Could not register patient'));
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerText = 'Register Patient';
    }
  }

  // --------------------------------------------------------------------------
  // View Patient Details Modal & Slip Printing
  // --------------------------------------------------------------------------
  let currentPatientAppointments = [];

  window.showAppointmentSlip = function(index) {
    const a = currentPatientAppointments[index];
    if (!a || !currentDetailPatient) return;

    document.getElementById('confirmTokenDisplay').innerText = a.token_display || ('#' + a.token_number);
    document.getElementById('confirmAptNumber').innerText = a.appointment_number || ('APT-' + a.id);
    document.getElementById('confirmPatientName').innerText = currentDetailPatient.full_name || '--';
    document.getElementById('confirmPatientPhone').innerText = currentDetailPatient.phone || '--';
    document.getElementById('confirmDoctorName').innerText = 'Dr. ' + (a.doctor_name || '--');
    document.getElementById('confirmChamberName').innerText = a.chamber_name || '--';
    document.getElementById('confirmChamberAddress').innerText = a.chamber_address || 'Main Desk';
    document.getElementById('confirmDate').innerText = a.appointment_date || '--';
    document.getElementById('confirmTotalPaid').innerText = parseFloat(a.total_paid || a.consultation_fee || 0).toFixed(0);
    
    const payStatusEl = document.getElementById('confirmPaymentStatus');
    if (payStatusEl) {
      payStatusEl.innerText = a.payment_status || 'PAID';
    }

    openModal('bookingSuccessModal');
  };

  async function openPatientDetails(patientId) {
    try {
      const res = await AdminApi.get(`/admin/patients/${patientId}`);
      if (!res.success) {
        throw new Error(res.error?.message || 'Failed to fetch details');
      }

      const p = res.data;
      currentDetailPatient = p;

      const initials = (p.full_name || 'P').split(' ').map(n => n[0]).join('').substring(0, 2);
      document.getElementById('detailAvatar').innerText = initials;
      document.getElementById('detailName').innerText = p.full_name || 'Patient';
      document.getElementById('detailPhone').innerText = p.phone || '--';
      document.getElementById('detailEmail').innerText = p.email || 'No email';
      
      const userStatus = p.user_status || p.status || 'ACTIVE';
      const statusPill = document.getElementById('detailStatusPill');
      statusPill.className = `status-pill status-${userStatus.toLowerCase()}`;
      statusPill.innerText = userStatus;

      document.getElementById('detailBloodGroup').innerText = p.blood_group || 'Not Specified';
      const dobDisplay = p.date_of_birth || p.dob ? (p.date_of_birth || p.dob) : 'DOB N/A';
      document.getElementById('detailGenderAge').innerText = `${p.gender || 'OTHER'} • ${dobDisplay}`;
      document.getElementById('detailEmergency').innerText = p.emergency_contact_name ? `${p.emergency_contact_name} (${p.emergency_contact_phone || '--'})` : 'None specified';
      document.getElementById('detailAddress').innerText = p.address || 'None specified';

      const familySec = document.getElementById('familySection');
      if (p.family_members && p.family_members.length > 0) {
        familySec.innerHTML = '<strong>Family Profiles:</strong> ' + p.family_members.map(f => `<span style="display: inline-block; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-right: 6px;">${f.full_name} (${f.relation})</span>`).join('');
      } else {
        familySec.innerHTML = '<strong>Family Profiles:</strong> <span style="color: var(--text-muted); font-size: 12px;">No linked family members</span>';
      }

      const apts = p.appointments || [];
      currentPatientAppointments = apts;
      document.getElementById('detailTotalVisits').innerText = apts.length;
      document.getElementById('historyBadge').innerText = `${apts.length} Record${apts.length === 1 ? '' : 's'}`;

      const historyTbody = document.getElementById('patientHistoryTbody');
      if (apts.length === 0) {
        historyTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No previous appointments recorded.</td></tr>';
      } else {
        historyTbody.innerHTML = apts.map((a, idx) => `
          <tr>
            <td>
              <strong>${a.appointment_date}</strong>
              <div style="font-size: 11px; color: var(--text-muted);">${a.appointment_number || ('APT-' + a.id)}</div>
            </td>
            <td>
              <div>Dr. ${a.doctor_name}</div>
              <div style="font-size: 11px; color: var(--text-muted);">${a.chamber_name}</div>
            </td>
            <td>
              <span style="font-weight: 800; color: #4f46e5; background: #e0e7ff; padding: 3px 8px; border-radius: 6px; font-size: 13px;">
                ${a.token_display || ('#' + a.token_number)}
              </span>
            </td>
            <td>
              <span class="status-pill status-${(a.status || 'booked').toLowerCase()}">${a.status || 'BOOKED'}</span>
            </td>
            <td>
              ৳${parseFloat(a.total_paid || a.consultation_fee || 0).toFixed(0)}
              <div style="font-size: 10px; color: var(--text-muted);">${a.payment_status || 'PAID'}</div>
            </td>
            <td>
              <button class="btn btn-outline btn-sm" onclick="showAppointmentSlip(${idx})" style="font-size: 11px; padding: 3px 8px;">
                Slip 📄
              </button>
            </td>
          </tr>
        `).join('');
      }

      // Hook detail modal "Book Serial Now" button
      document.getElementById('btnDetailBookSerial').onclick = () => {
        document.getElementById('patientDetailsModal').style.display = 'none';
        openBookingModal(p.id);
      };

      openModal('patientDetailsModal');
    } catch (err) {
      console.error('Error opening patient modal:', err);
      alert('Error fetching patient profile: ' + (err.message || 'Unknown error'));
    }
  }

  // Start app
  init();
</script>

</body>
</html>
