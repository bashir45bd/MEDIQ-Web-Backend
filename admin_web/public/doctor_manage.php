<?php
$activePage = 'doctors';
$pageTitle = 'Comprehensive Doctor Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Doctor — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .tab-btn {
      padding: 10px 20px;
      font-weight: 600;
      border: none;
      background: none;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      color: var(--text-muted);
      font-size: 15px;
    }
    .tab-btn.active {
      color: var(--primary);
      border-bottom-color: var(--primary);
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
  </style>
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <!-- Top Header Card -->
      <div class="card" style="margin-bottom: 24px; border-left: 5px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div>
            <div style="display: flex; align-items: center; gap: 10px;">
              <a href="doctors.php" class="btn btn-outline btn-sm">&larr; Back to Doctors</a>
              <span id="docStatusBadge" class="status-pill status-verified">VERIFIED</span>
            </div>
            <h2 id="docHeaderName" style="font-size: 24px; font-weight: 800; margin-top: 10px;">Loading Doctor...</h2>
            <div id="docHeaderMeta" style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">BMDC: -- • Phone: --</div>
          </div>
          <div>
            <div style="font-size: 12px; color: var(--text-muted); text-align: right;">PLATFORM GOVERNANCE</div>
            <div style="font-size: 13px; font-weight: 600; color: #10b981; margin-top: 2px;">Administrator Super-Control Active</div>
          </div>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div style="display: flex; border-bottom: 1px solid var(--border); margin-bottom: 24px;">
        <button class="tab-btn active" data-tab="tabProfile">1. Edit Doctor Profile</button>
        <button class="tab-btn" data-tab="tabCapacity">2. Session Capacity & Delay</button>
        <button class="tab-btn" data-tab="tabChambers">3. Chambers & Schedules</button>
      </div>

      <!-- TAB 1: Edit Doctor Profile -->
      <div id="tabProfile" class="tab-content active">
        <div class="card" style="max-width: 850px;">
          <h3 style="font-size: 18px; margin-bottom: 18px;">Doctor Profile & Credential Details</h3>
          <form id="adminProfileForm">
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="docName">Doctor Name</label>
                <input type="text" id="docName" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="docPhone">Contact Mobile</label>
                <input type="text" id="docPhone" class="form-control" required>
              </div>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="docEmail">Email Address</label>
                <input type="email" id="docEmail" class="form-control">
              </div>
              <div class="form-group">
                <label class="form-label" for="docBmdc">BMDC Number</label>
                <input type="text" id="docBmdc" class="form-control" required>
              </div>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="docDesignation">Designation & Degrees</label>
                <input type="text" id="docDesignation" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="docExperience">Experience (Years)</label>
                <input type="number" id="docExperience" class="form-control" min="0" max="60">
              </div>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="docConsultationFee">Consultation Fee (BDT)</label>
                <input type="number" step="10" id="docConsultationFee" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="docFollowUpFee">Follow-up Fee (BDT)</label>
                <input type="number" step="10" id="docFollowUpFee" class="form-control" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="docVerificationStatus">Verification Status</label>
              <select id="docVerificationStatus" class="form-control">
                <option value="VERIFIED">VERIFIED (Active on Platform)</option>
                <option value="PENDING">PENDING (Awaiting Review)</option>
                <option value="SUSPENDED">SUSPENDED (Temporarily Blocked)</option>
                <option value="REJECTED">REJECTED (Application Declined)</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Specialties</label>
              <div id="adminSpecialtiesContainer" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px;"></div>
            </div>

            <div class="form-group">
              <label class="form-label" for="docBio">Bio & Qualifications</label>
              <textarea id="docBio" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" id="btnSaveAdminProfile" class="btn btn-primary" style="padding: 10px 24px;">
              Save Doctor Profile
            </button>
          </form>
        </div>
      </div>

      <!-- TAB 2: Capacity & Session Controls -->
      <div id="tabCapacity" class="tab-content">
        <div class="grid grid-cols-2">
          <!-- Adjust Capacity Card -->
          <div class="card">
            <h3 style="font-size: 18px; margin-bottom: 12px;">Adjust Session Capacity</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 18px;">
              Directly set today's maximum patient intake on behalf of this doctor.
            </p>
            <form id="adminCapacityForm">
              <div class="form-group">
                <label class="form-label" for="capChamberSelect">Select Chamber</label>
                <select id="capChamberSelect" class="form-control" required></select>
              </div>
              <div class="form-group">
                <label class="form-label" for="capDate">Session Date</label>
                <input type="date" id="capDate" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="capTotalInput">Total Daily Capacity</label>
                <input type="number" id="capTotalInput" class="form-control" min="1" max="100" value="20" required>
              </div>
              <button type="submit" class="btn btn-primary">Update Capacity</button>
            </form>
          </div>

          <!-- Broadcast Delay Card -->
          <div class="card">
            <h3 style="font-size: 18px; margin-bottom: 12px;">Broadcast Chamber Delay</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 18px;">
              Alert waiting patients regarding doctor traffic or emergency delays.
            </p>
            <form id="adminDelayForm">
              <div class="form-group">
                <label class="form-label" for="delayChamberSelect">Select Chamber</label>
                <select id="delayChamberSelect" class="form-control" required></select>
              </div>
              <div class="form-group">
                <label class="form-label" for="adminDelayMinutes">Delay Minutes</label>
                <select id="adminDelayMinutes" class="form-control">
                  <option value="0">0 Minutes (On Time)</option>
                  <option value="15">15 Minutes Delay</option>
                  <option value="30">30 Minutes Delay</option>
                  <option value="45">45 Minutes Delay</option>
                  <option value="60">1 Hour Delay</option>
                </select>
              </div>
              <button type="submit" class="btn btn-warning">Broadcast Delay to Patients</button>
            </form>
          </div>
        </div>

        <!-- Rapid Availability Status Card -->
        <div class="card" style="margin-top: 24px;">
          <h3 style="font-size: 18px; margin-bottom: 12px;">Emergency Availability Override</h3>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 16px;">
            Open or close booking sessions instantly for this doctor.
          </p>
          <div style="display: flex; gap: 12px;">
            <button class="btn btn-success btn-sm btn-quick-avail" data-status="ON">🟢 Set ON (Open Booking)</button>
            <button class="btn btn-warning btn-sm btn-quick-avail" data-status="PAUSED">🟡 Set PAUSED (Hold Intake)</button>
            <button class="btn btn-danger btn-sm btn-quick-avail" data-status="OFF">🔴 Set OFF (Closed)</button>
          </div>
        </div>
      </div>

      <!-- TAB 3: Chambers & Weekly Schedules -->
      <div id="tabChambers" class="tab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h3 style="font-size: 18px; font-weight: 700;">Registered Chambers</h3>
          <button id="btnOpenAddChamberModal" class="btn btn-primary">+ Add Chamber for Doctor</button>
        </div>

        <!-- Chambers Table -->
        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 32px;">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Chamber Name</th>
                  <th>Location</th>
                  <th>Phone</th>
                  <th>Room</th>
                  <th>Prefix</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="adminChambersTbody">
                <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">Loading chambers...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Schedules Table & Creator -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h3 style="font-size: 18px; font-weight: 700;">Weekly Consultation Hours</h3>
          <button id="btnOpenAddScheduleModal" class="btn btn-primary">+ Configure Hours</button>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Chamber</th>
                  <th>Day</th>
                  <th>Hours</th>
                  <th>Slot Duration</th>
                  <th>Default Capacity</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="adminSchedulesTbody">
                <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">Loading schedules...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Add Chamber Modal -->
      <div id="addChamberModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 500px;">
          <h3 style="font-size: 18px; margin-bottom: 16px;">Add Chamber for Doctor</h3>
          <form id="adminNewChamberForm">
            <div class="form-group">
              <label class="form-label">Chamber Name</label>
              <input type="text" id="chName" class="form-control" required placeholder="e.g. Popular Diagnostic Centre">
            </div>
            <div class="form-group">
              <label class="form-label">Street Address</label>
              <input type="text" id="chAddress" class="form-control" required placeholder="Full location address">
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">City</label>
                <select id="chCitySelect" class="form-control"></select>
              </div>
              <div class="form-group">
                <label class="form-label">Area</label>
                <select id="chAreaSelect" class="form-control"></select>
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">Contact Phone</label>
                <input type="text" id="chPhone" class="form-control" required placeholder="+88017...">
              </div>
              <div class="form-group">
                <label class="form-label">Token Prefix</label>
                <input type="text" id="chPrefix" class="form-control" value="A" maxlength="2">
              </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
              <button type="button" id="btnCloseChamberModal" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary">Create Chamber</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Schedule Modal -->
      <div id="addScheduleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 500px;">
          <h3 style="font-size: 18px; margin-bottom: 16px;">Configure Weekly Hours</h3>
          <form id="adminNewScheduleForm">
            <div class="form-group">
              <label class="form-label">Select Chamber</label>
              <select id="schChamberSelect" class="form-control" required></select>
            </div>
            <div class="form-group">
              <label class="form-label">Day of Week</label>
              <select id="schDaySelect" class="form-control">
                <option value="0">Sunday</option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thursday</option>
                <option value="5">Friday</option>
                <option value="6">Saturday</option>
              </select>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">Start Time</label>
                <input type="time" id="schStart" class="form-control" value="17:00" required>
              </div>
              <div class="form-group">
                <label class="form-label">End Time</label>
                <input type="time" id="schEnd" class="form-control" value="21:00" required>
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">Slot Duration (Min)</label>
                <input type="number" id="schDuration" class="form-control" value="10" required>
              </div>
              <div class="form-group">
                <label class="form-label">Default Capacity</label>
                <input type="number" id="schCapacity" class="form-control" value="25" required>
              </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
              <button type="button" id="btnCloseScheduleModal" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Schedule</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  const urlParams = new URLSearchParams(window.location.search);
  const doctorId = parseInt(urlParams.get('id'), 10);
  if (!doctorId) {
    alert('No doctor ID specified');
    window.location.href = 'doctors.php';
  }

  const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  let currentChambers = [];

  document.getElementById('capDate').value = new Date().toISOString().split('T')[0];

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  // Tab Switching
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });

  async function loadDoctorData() {
    try {
      const res = await AdminApi.get(`/admin/doctors/${doctorId}/details`);
      if (!res.success) return;

      const d = res.data;
      const doc = d.doctor;
      currentChambers = d.chambers || [];

      // Header info
      document.getElementById('docHeaderName').innerText = doc.doctor_name;
      document.getElementById('docHeaderMeta').innerText = `BMDC: ${doc.bmdc_number} • Phone: ${doc.phone} • Email: ${doc.email || 'N/A'}`;
      const statusPill = document.getElementById('docStatusBadge');
      statusPill.innerText = doc.verification_status;
      statusPill.className = 'status-pill status-' + doc.verification_status.toLowerCase();

      // Populate Form 1: Profile
      document.getElementById('docName').value = doc.doctor_name || '';
      document.getElementById('docPhone').value = doc.phone || '';
      document.getElementById('docEmail').value = doc.email || '';
      document.getElementById('docBmdc').value = doc.bmdc_number || '';
      document.getElementById('docDesignation').value = doc.designation || '';
      document.getElementById('docExperience').value = doc.experience_years || 0;
      document.getElementById('docConsultationFee').value = parseFloat(doc.consultation_fee || 0).toFixed(0);
      document.getElementById('docFollowUpFee').value = parseFloat(doc.follow_up_fee || 0).toFixed(0);
      document.getElementById('docVerificationStatus').value = doc.verification_status;
      document.getElementById('docBio').value = doc.bio || '';

      // Specialties checkboxes
      const specContainer = document.getElementById('adminSpecialtiesContainer');
      const assignedIds = (doc.specialty_ids || []).map(Number);
      specContainer.innerHTML = (d.all_specialties || []).map(s => `
        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; background: #f8fafc; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;">
          <input type="checkbox" name="admin_specialty_ids" value="${s.id}" ${assignedIds.includes(Number(s.id)) ? 'checked' : ''}>
          <span>${s.name}</span>
        </label>
      `).join('');

      // Populate Chamber Selects in Tab 2 & 3
      const capChamberSelect = document.getElementById('capChamberSelect');
      const delayChamberSelect = document.getElementById('delayChamberSelect');
      const schChamberSelect = document.getElementById('schChamberSelect');

      const chamberOptions = currentChambers.map(c => `<option value="${c.id}">${c.name} (${c.city_name})</option>`).join('');
      capChamberSelect.innerHTML = chamberOptions || '<option value="">No chambers</option>';
      delayChamberSelect.innerHTML = chamberOptions || '<option value="">No chambers</option>';
      schChamberSelect.innerHTML = chamberOptions || '<option value="">No chambers</option>';

      // Populate Chambers Table in Tab 3
      const cTbody = document.getElementById('adminChambersTbody');
      if (currentChambers.length === 0) {
        cTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No chambers registered for this doctor.</td></tr>';
      } else {
        cTbody.innerHTML = currentChambers.map(c => `
          <tr>
            <td><strong>${c.name}</strong></td>
            <td>${c.address}, ${c.city_name}</td>
            <td>${c.contact_phone}</td>
            <td>${c.room_number || 'Main Desk'}</td>
            <td><code>${c.token_prefix}</code></td>
            <td><span class="status-pill status-${c.is_active ? 'verified' : 'rejected'}">${c.is_active ? 'ACTIVE' : 'OFF'}</span></td>
          </tr>
        `).join('');
      }

      // Populate Schedules Table in Tab 3
      const sTbody = document.getElementById('adminSchedulesTbody');
      const schedules = d.schedules || [];
      if (schedules.length === 0) {
        sTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No weekly consultation hours configured.</td></tr>';
      } else {
        sTbody.innerHTML = schedules.map(s => `
          <tr>
            <td><strong>${s.chamber_name}</strong></td>
            <td><span style="font-weight: 600;">${days[s.day_of_week]}</span></td>
            <td>${s.start_time} – ${s.end_time}</td>
            <td>${s.slot_duration_minutes} min/patient</td>
            <td>${s.default_capacity} patients</td>
            <td><span class="status-pill status-${s.is_active ? 'verified' : 'rejected'}">${s.is_active ? 'ACTIVE' : 'OFF'}</span></td>
          </tr>
        `).join('');
      }

      // Populate City & Area Selects in Modal
      const citySelect = document.getElementById('chCitySelect');
      citySelect.innerHTML = (d.all_cities || []).map(ci => `<option value="${ci.id}">${ci.name}</option>`).join('');
      const areaSelect = document.getElementById('chAreaSelect');
      areaSelect.innerHTML = (d.all_areas || []).map(a => `<option value="${a.id}">${a.name}</option>`).join('');

    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  // 1. Save Profile Form Handler
  document.getElementById('adminProfileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSaveAdminProfile');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    const selectedSpecs = Array.from(document.querySelectorAll('input[name="admin_specialty_ids"]:checked'))
      .map(cb => parseInt(cb.value, 10));

    const payload = {
      doctor_name: document.getElementById('docName').value.trim(),
      phone: document.getElementById('docPhone').value.trim(),
      email: document.getElementById('docEmail').value.trim(),
      bmdc_number: document.getElementById('docBmdc').value.trim(),
      designation: document.getElementById('docDesignation').value.trim(),
      experience_years: parseInt(document.getElementById('docExperience').value, 10),
      consultation_fee: parseFloat(document.getElementById('docConsultationFee').value),
      follow_up_fee: parseFloat(document.getElementById('docFollowUpFee').value),
      verification_status: document.getElementById('docVerificationStatus').value,
      bio: document.getElementById('docBio').value.trim(),
      specialty_ids: selectedSpecs
    };

    try {
      await AdminApi.put(`/admin/doctors/${doctorId}`, payload);
      showNotice('Doctor profile and credentials updated successfully!');
      loadDoctorData();
    } catch (err) {
      showNotice(err.message, 'danger');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Save Doctor Profile';
    }
  });

  // 2. Adjust Capacity Form Handler
  document.getElementById('adminCapacityForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const cid = parseInt(document.getElementById('capChamberSelect').value, 10);
    const dt = document.getElementById('capDate').value;
    const cap = parseInt(document.getElementById('capTotalInput').value, 10);
    if (!cid) return showNotice('Please select a chamber', 'danger');

    try {
      await AdminApi.post(`/admin/doctors/${doctorId}/capacity`, {
        chamber_id: cid,
        date: dt,
        total_capacity: cap
      });
      showNotice(`Capacity updated to ${cap} patients for ${dt}.`);
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  // 3. Broadcast Delay Form Handler
  document.getElementById('adminDelayForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const cid = parseInt(document.getElementById('delayChamberSelect').value, 10);
    const mins = parseInt(document.getElementById('adminDelayMinutes').value, 10);
    if (!cid) return showNotice('Please select a chamber', 'danger');

    try {
      await AdminApi.post(`/admin/doctors/${doctorId}/delay`, {
        chamber_id: cid,
        delay_minutes: mins
      });
      showNotice(`Broadcasted ${mins} min delay alert to patients.`);
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  // Quick Availability Toggle
  document.querySelectorAll('.btn-quick-avail').forEach(btn => {
    btn.addEventListener('click', async () => {
      const cid = parseInt(document.getElementById('capChamberSelect').value, 10);
      const dt = document.getElementById('capDate').value;
      const status = btn.dataset.status;
      if (!cid) return showNotice('Please select a chamber', 'danger');

      try {
        await AdminApi.post('/admin/availability/override', {
          doctor_id: doctorId,
          chamber_id: cid,
          appointment_date: dt,
          status: status,
          reason: 'Administrator quick toggle in Doctor Management Console'
        });
        showNotice(`Session status overridden to ${status}.`);
      } catch (err) {
        showNotice(err.message, 'danger');
      }
    });
  });

  // 4. Chamber Modal & Creator
  const cModal = document.getElementById('addChamberModal');
  document.getElementById('btnOpenAddChamberModal').addEventListener('click', () => { cModal.style.display = 'flex'; });
  document.getElementById('btnCloseChamberModal').addEventListener('click', () => { cModal.style.display = 'none'; });

  document.getElementById('adminNewChamberForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      name: document.getElementById('chName').value.trim(),
      address: document.getElementById('chAddress').value.trim(),
      city_id: parseInt(document.getElementById('chCitySelect').value, 10),
      area_id: parseInt(document.getElementById('chAreaSelect').value, 10),
      contact_phone: document.getElementById('chPhone').value.trim(),
      token_prefix: document.getElementById('chPrefix').value.trim() || 'A'
    };

    try {
      await AdminApi.post(`/admin/doctors/${doctorId}/chambers`, payload);
      cModal.style.display = 'none';
      showNotice('New practice chamber created successfully!');
      loadDoctorData();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  // 5. Schedule Modal & Creator
  const sModal = document.getElementById('addScheduleModal');
  document.getElementById('btnOpenAddScheduleModal').addEventListener('click', () => { sModal.style.display = 'flex'; });
  document.getElementById('btnCloseScheduleModal').addEventListener('click', () => { sModal.style.display = 'none'; });

  document.getElementById('adminNewScheduleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      chamber_id: parseInt(document.getElementById('schChamberSelect').value, 10),
      day_of_week: parseInt(document.getElementById('schDaySelect').value, 10),
      start_time: document.getElementById('schStart').value,
      end_time: document.getElementById('schEnd').value,
      slot_duration_minutes: parseInt(document.getElementById('schDuration').value, 10),
      default_capacity: parseInt(document.getElementById('schCapacity').value, 10)
    };

    try {
      await AdminApi.post(`/admin/doctors/${doctorId}/schedules`, payload);
      sModal.style.display = 'none';
      showNotice('Weekly consultation hours saved successfully!');
      loadDoctorData();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  loadDoctorData();
</script>

</body>
</html>
