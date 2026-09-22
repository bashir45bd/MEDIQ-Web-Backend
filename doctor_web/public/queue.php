<?php
$activePage = 'queue';
$pageTitle = 'Live Chamber Queue Console';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Queue Console — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .queue-active-card {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      padding: 28px;
      border-radius: 12px;
      margin-bottom: 28px;
      box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.3);
    }
    .badge-token {
      font-size: 38px;
      font-weight: 800;
      letter-spacing: 1px;
    }
  </style>
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <!-- Active Break Banner -->
      <div id="breakBanner" style="display: none; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 10px; padding: 14px 20px; margin-bottom: 20px; color: #92400e; font-weight: 600; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 24px;" id="breakBannerIcon">⏸️</span>
          <div>
            <div style="font-size: 15px; font-weight: 700;" id="breakBannerTitle">Chamber is currently on Break</div>
            <div style="font-size: 13px; font-weight: 500; opacity: 0.9;" id="breakBannerNote">Waiting patients have been notified.</div>
          </div>
        </div>
        <button class="btn btn-success btn-sm" id="btnResumeBreak" style="padding: 8px 18px; font-size: 13px; font-weight: 700;">🟢 Resume Consultation Now</button>
      </div>

      <!-- Chamber Switcher & Session Controls Bar -->
      <div class="card" style="margin-bottom: 24px; padding: 18px 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
          <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div>
              <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Active Chamber:</label>
              <select id="chamberSelect" class="form-control" style="font-size: 14px; font-weight: 700; min-width: 200px; padding: 6px 10px; border-color: var(--primary);">
                <option value="">Loading chambers...</option>
              </select>
            </div>
            <div>
              <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Presence Status:</label>
              <div id="presenceBadge" class="status-pill status-on" style="font-size: 13px; font-weight: 700; padding: 6px 14px;">👨‍⚕️ In Chamber (চেম্বারে উপস্থিত)</div>
            </div>
            <div>
              <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Serial Booking:</label>
              <button id="btnToggleBooking" class="btn btn-success btn-sm" style="font-weight: 700; padding: 6px 14px;">🟢 Booking ON (চালু)</button>
            </div>
          </div>

          <!-- Quick Session & Presence Buttons -->
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button class="btn btn-primary btn-sm" id="btnInChamber" title="In Chamber" style="padding: 6px 12px; font-size: 12px; font-weight: 700;">👨‍⚕️ In Chamber</button>
            <button class="btn btn-warning btn-sm" id="btnPrayerBreak" title="Prayer Break" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🕌 Prayer Break</button>
            <button class="btn btn-warning btn-sm" id="btnMealBreak" title="Meal Break" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🍽️ Meal Break</button>
            <button class="btn btn-outline btn-sm" id="btnCustomPause" title="Short Pause" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">⏸️ Pause</button>
            <button class="btn btn-danger btn-sm" id="btnSetOff" title="Close Session" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🛑 Close Session</button>
          </div>
        </div>
      </div>

      <!-- Active Consultation Hero Banner -->
      <div id="activeConsultationSection" class="queue-active-card" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
          <div>
            <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; text-transform: uppercase;">
              Currently Consulting
            </span>
            <div id="activeToken" class="badge-token" style="margin-top: 8px;">A001</div>
            <h3 id="activePatientName" style="font-size: 22px; font-weight: 700; margin-top: 4px;">Patient Name</h3>
            <div id="activePatientMeta" style="opacity: 0.9; font-size: 14px; margin-top: 4px;">Male, 34 Yrs • B+</div>
          </div>

          <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button id="btnCompletePaperActive" class="btn btn-outline" style="background: rgba(255,255,255,0.2); color: #fff; border: 1.5px solid rgba(255,255,255,0.7); font-weight: 700; padding: 14px 22px; font-size: 15px;">
              ✍️ Complete (Handwritten Rx / প্রেসক্রিপশন ছাড়া)
            </button>
            <a id="btnPrescribeActive" href="#" class="btn btn-warning" style="padding: 14px 24px; font-size: 15px; font-weight: 700;">
              💻 Digital Rx &rarr;
            </a>
          </div>
        </div>
      </div>

      <!-- Called Patient Section (Waiting to Enter) -->
      <div id="calledPatientSection" class="card" style="margin-bottom: 28px; border-left: 5px solid #d97706; display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div>
            <span class="status-pill status-paused">CALLED — Awaiting Entry</span>
            <div style="font-size: 28px; font-weight: 800; color: #d97706; margin-top: 6px;" id="calledToken">A002</div>
            <div style="font-weight: 600; font-size: 16px;" id="calledPatientName">Patient Name</div>
          </div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button id="btnStartConsultation" class="btn btn-success">Start Consultation</button>
            <button id="btnCalledCompletePaper" class="btn btn-outline" style="border-color: #059669; color: #059669; font-weight: 700;">✍️ Complete (Paper Rx)</button>
            <button id="btnSkipPatient" class="btn btn-outline">Skip</button>
            <button id="btnNoShowPatient" class="btn btn-danger">No-Show</button>
          </div>
        </div>
      </div>

      <!-- Main Queue Actions Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 20px; font-weight: 700;">Waiting Room Queue</h3>
        <div style="display: flex; gap: 12px;">
          <button id="btnCallNext" class="btn btn-primary" style="padding: 10px 22px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            <span>Call Next Patient</span>
          </button>
        </div>
      </div>

      <!-- Waiting Table -->
      <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 32px;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Serial / Token</th>
                <th>Patient Name</th>
                <th>Patient Type</th>
                <th>Status</th>
                <th>Check-in Time</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="waitingTableBody">
              <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading waiting patients...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Skipped Patients Roster -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 20px; font-weight: 700; color: #b45309;">⏭️ Skipped Patients (বাদ পড়া রোগী)</h3>
        <small style="color: var(--text-muted);">চেম্বার শেষ করার আগে বাদ পড়া রোগীদের পুনরায় সিরিয়ালে ডাকতে পারেন</small>
      </div>
      <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 32px; border-top: 3px solid #f59e0b;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Serial / Token</th>
                <th>Patient Name</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="skippedTableBody">
              <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">No skipped patients.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Completed Patients Roster -->
      <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 16px;">Completed Consultations Today</h3>
      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Token</th>
                <th>Patient Name</th>
                <th>Finished At</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="completedTableBody">
              <tr>
                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px;">No completed consultations yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { Api } from '../assets/js/api.js';

  const urlParams = new URLSearchParams(window.location.search);
  let activeChamberId = urlParams.get('chamber_id') 
    ? parseInt(urlParams.get('chamber_id'), 10) 
    : (localStorage.getItem('doc_selected_chamber_id') ? parseInt(localStorage.getItem('doc_selected_chamber_id'), 10) : null);

  let currentAptId = null;
  let calledAptId = null;

  async function initChambers() {
    try {
      const res = await Api.get('/doctor/dashboard' + (activeChamberId ? `?chamber_id=${activeChamberId}` : ''));
      if (res.success && res.data.chambers) {
        const chambers = res.data.chambers;
        const select = document.getElementById('chamberSelect');
        if (chambers.length > 0) {
          if (!activeChamberId || !chambers.some(c => c.id === activeChamberId)) {
            activeChamberId = chambers[0].id;
          }
          localStorage.setItem('doc_selected_chamber_id', activeChamberId);
          select.innerHTML = chambers.map(c => `
            <option value="${c.id}" ${c.id === activeChamberId ? 'selected' : ''}>
              ${c.name} (${c.city_name || c.area_name || 'Chamber'})
            </option>
          `).join('');
        }
        if (res.data.stats) {
          updateAvailabilityUI(res.data.stats.availability, res.data.stats.status_note);
        }
      }
    } catch (e) {
      console.error('Failed to init chambers:', e);
    }
  }

  let currentPresence = 'IN_CHAMBER';
  let isBookingOpen = true;

  function updateAvailabilityUI(status, note) {
    const pill = document.getElementById('availabilityPill');
    const banner = document.getElementById('breakBanner');
    if (pill) {
      pill.className = 'status-pill status-' + (status || 'on').toLowerCase();

      if (status === 'ON') {
        pill.innerText = '🟢 ON (Accepting)';
      } else if (status === 'PAUSED') {
        pill.innerText = '🟡 PAUSED';
      } else if (status === 'OFF' || status === 'CLOSED') {
        pill.innerText = '🔴 ' + status;
      } else {
        pill.innerText = status;
      }
    }

    if (banner) {
      if (status === 'PAUSED') {
        banner.style.display = 'flex';
        const icon = (note && note.includes('Prayer')) ? '🕌' : ((note && note.includes('Meal')) ? '🍽️' : '⏸️');
        document.getElementById('breakBannerIcon').innerText = icon;
        document.getElementById('breakBannerTitle').innerText = note || 'Chamber Consultation Paused';
        document.getElementById('breakBannerNote').innerText = 'Queue calls held. Patients are notified of the break.';
      } else {
        banner.style.display = 'none';
      }
    }
  }

  function updatePresenceUI(presence, bookingOpen) {
    currentPresence = presence || 'IN_CHAMBER';
    if (bookingOpen !== undefined && bookingOpen !== null) {
      isBookingOpen = Boolean(bookingOpen == 1 || bookingOpen === true || bookingOpen === '1');
    }

    const badge = document.getElementById('presenceBadge');
    if (badge) {
      if (currentPresence === 'IN_CHAMBER') {
        badge.className = 'status-pill status-on';
        badge.innerText = '👨‍⚕️ In Chamber (উপস্থিত)';
      } else if (currentPresence === 'PRAYER_BREAK') {
        badge.className = 'status-pill status-paused';
        badge.innerText = '🕌 Prayer Break (নামাজ)';
      } else if (currentPresence === 'MEAL_BREAK') {
        badge.className = 'status-pill status-paused';
        badge.innerText = '🍽️ Meal Break (খাবার)';
      } else if (currentPresence === 'SHORT_PAUSE') {
        badge.className = 'status-pill status-paused';
        badge.innerText = '⏸️ Paused (বিরতি)';
      } else {
        badge.className = 'status-pill status-off';
        badge.innerText = '🚪 Away (বাইরে আছেন)';
      }
    }

    const btnBooking = document.getElementById('btnToggleBooking');
    if (btnBooking) {
      if (isBookingOpen) {
        btnBooking.className = 'btn btn-success btn-sm';
        btnBooking.innerText = '🟢 Booking ON (সিরিয়াল চালু)';
        btnBooking.title = 'Click to pause/stop serial booking';
      } else {
        btnBooking.className = 'btn btn-danger btn-sm';
        btnBooking.innerText = '🔴 Booking CLOSED (সিরিয়াল বন্ধ)';
        btnBooking.title = 'Click to open serial booking';
      }
    }
  }

  async function setAvailability(status, reason = '', liveStatus = 'AVAILABLE') {
    if (!activeChamberId) {
      showNotice('Please select an active chamber first.', 'danger');
      return;
    }
    try {
      const res = await Api.post('/doctor/availability/toggle', {
        chamber_id: activeChamberId,
        status: status,
        reason: reason,
        live_status: liveStatus
      });
      if (res.success) {
        updateAvailabilityUI(status, reason);
        showNotice(`Session status updated: ${status} ${reason ? '— ' + reason : ''}`);
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  async function setDoctorPresence(presence, note = '') {
    if (!activeChamberId) return;
    try {
      const today = new Date().toISOString().split('T')[0];
      const res = await Api.post('/doctor/presence', {
        chamber_id: activeChamberId,
        date: today,
        presence_status: presence,
        note: note
      });
      if (res.success) {
        currentPresence = presence;
        updatePresenceUI(currentPresence, isBookingOpen);
        showNotice(`Doctor presence: ${presence.replace('_', ' ')}`);
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  async function loadQueue() {
    if (!activeChamberId) return;
    try {
      const res = await Api.get(`/queue/doctor/today?chamber_id=${activeChamberId}`);
      if (!res.success) return;

      const q = res.data.queue;
      const avail = res.data.availability;

      if (avail) {
        updateAvailabilityUI(avail.status, avail.status_note);
        updatePresenceUI(avail.presence_status, avail.is_booking_open);
      }

      // 1. Current Consulting Patient
      const activeSection = document.getElementById('activeConsultationSection');
      if (q.current) {
        currentAptId = q.current.id;
        document.getElementById('activeToken').innerText = q.current.token_display;
        document.getElementById('activePatientName').innerText = q.current.patient_name;
        document.getElementById('activePatientMeta').innerText = (q.current.gender || 'Patient') + ' • ' + (q.current.blood_group || 'Blood N/A');
        document.getElementById('btnPrescribeActive').href = `consultation.php?appointment_id=${q.current.id}`;
        activeSection.style.display = 'block';
      } else {
        currentAptId = null;
        activeSection.style.display = 'none';
      }

      // 2. Called Patient
      const calledSection = document.getElementById('calledPatientSection');
      if (q.called) {
        calledAptId = q.called.id;
        document.getElementById('calledToken').innerText = q.called.token_display;
        document.getElementById('calledPatientName').innerText = q.called.patient_name;
        calledSection.style.display = 'block';
      } else {
        calledAptId = null;
        calledSection.style.display = 'none';
      }

      // 3. Waiting Table with Swap & Call controls
      const waitingTbody = document.getElementById('waitingTableBody');
      if (!q.waiting || q.waiting.length === 0) {
        waitingTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No patients currently waiting in chamber queue.</td></tr>';
      } else {
        waitingTbody.innerHTML = q.waiting.map((p, index) => {
          const prevId = index > 0 ? q.waiting[index - 1].id : null;
          const nextId = index < q.waiting.length - 1 ? q.waiting[index + 1].id : null;

          return `
            <tr>
              <td><strong style="font-size: 16px; color: var(--primary);">${p.token_display}</strong></td>
              <td><strong>${p.patient_name}</strong><br><small style="color: var(--text-muted);">${p.patient_phone || ''}</small></td>
              <td><span class="status-pill status-${p.patient_type === 'NEW' ? 'on' : 'paused'}">${p.patient_type}</span></td>
              <td><span class="status-pill status-${p.status === 'CHECKED_IN' ? 'on' : 'paused'}">${p.status}</span></td>
              <td>${p.check_in_time ? new Date(p.check_in_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'Pending'}</td>
              <td style="white-space: nowrap;">
                <button class="btn btn-primary btn-sm btn-call-specific" data-id="${p.id}" data-token="${p.token_display}" style="margin-right: 6px; font-weight: 700;">Call</button>
                ${prevId ? `<button class="btn btn-outline btn-sm btn-swap" data-id="${p.id}" data-target-id="${prevId}" title="Move Up (সিরিয়াল এগিয়ে দিন)" style="padding: 4px 8px; margin-right: 2px;">▲</button>` : ''}
                ${nextId ? `<button class="btn btn-outline btn-sm btn-swap" data-id="${p.id}" data-target-id="${nextId}" title="Move Down (সিরিয়াল পিছিয়ে দিন)" style="padding: 4px 8px;">▼</button>` : ''}
              </td>
            </tr>
          `;
        }).join('');
      }

      // 4. Skipped Patients Roster with Recall
      const skippedTbody = document.getElementById('skippedTableBody');
      if (skippedTbody) {
        if (!q.skipped || q.skipped.length === 0) {
          skippedTbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">No skipped patients today.</td></tr>';
        } else {
          skippedTbody.innerHTML = q.skipped.map(s => `
            <tr>
              <td><strong style="font-size: 16px; color: #b45309;">${s.token_display}</strong></td>
              <td><strong>${s.patient_name}</strong></td>
              <td>${s.patient_phone || '—'}</td>
              <td><span class="status-pill status-paused">SKIPPED</span></td>
              <td>
                <button class="btn btn-warning btn-sm btn-recall" data-id="${s.id}" data-token="${s.token_display}" style="font-weight: 700;">🔄 Recall (সিরিয়ালে ফেরত নিন)</button>
              </td>
            </tr>
          `).join('');
        }
      }

      // 5. Completed Table
      const completedTbody = document.getElementById('completedTableBody');
      if (!q.completed || q.completed.length === 0) {
        completedTbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px;">No completed consultations yet today.</td></tr>';
      } else {
        completedTbody.innerHTML = q.completed.map(c => `
          <tr>
            <td><strong>${c.token_display}</strong></td>
            <td>${c.patient_name}</td>
            <td>${c.consultation_start_time ? new Date(c.consultation_start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'Completed'}</td>
            <td>
              <a href="consultation.php?appointment_id=${c.id}" class="btn btn-outline btn-sm">View / Edit Rx</a>
            </td>
          </tr>
        `).join('');
      }

    } catch (err) {
      console.error('Queue load failure:', err);
    }
  }

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    if (!box) return;
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  // Chamber selection change
  document.getElementById('chamberSelect').addEventListener('change', (e) => {
    activeChamberId = parseInt(e.target.value, 10);
    localStorage.setItem('doc_selected_chamber_id', activeChamberId);
    loadQueue();
    Api.get(`/doctor/dashboard?chamber_id=${activeChamberId}`).then(res => {
      if (res.success && res.data.stats) {
        updateAvailabilityUI(res.data.stats.availability, res.data.stats.status_note);
      }
    });
  });

  // Action Handlers
  document.getElementById('btnInChamber')?.addEventListener('click', async () => {
    await setDoctorPresence('IN_CHAMBER', 'Doctor is present in chamber');
    await setAvailability('ON', '', 'AVAILABLE');
  });
  document.getElementById('btnResumeBreak')?.addEventListener('click', async () => {
    await setDoctorPresence('IN_CHAMBER', '');
    await setAvailability('ON', '', 'AVAILABLE');
  });
  document.getElementById('btnPrayerBreak').addEventListener('click', async () => {
    await setDoctorPresence('PRAYER_BREAK', '🕌 Prayer Break (নামাজের বিরতি)');
    await setAvailability('PAUSED', '🕌 Prayer Break (নামাজের বিরতি)', 'SHORT_BREAK');
  });
  document.getElementById('btnMealBreak').addEventListener('click', async () => {
    await setDoctorPresence('MEAL_BREAK', '🍽️ Meal Break (খাওয়ার বিরতি)');
    await setAvailability('PAUSED', '🍽️ Meal Break (খাওয়ার বিরতি)', 'SHORT_BREAK');
  });
  document.getElementById('btnCustomPause').addEventListener('click', async () => {
    const custom = prompt('Enter break reason:', '⏸️ Quick Break (সাময়িক বিরতি)');
    if (custom !== null) {
      await setDoctorPresence('SHORT_PAUSE', custom);
      await setAvailability('PAUSED', custom, 'SHORT_BREAK');
    }
  });
  document.getElementById('btnSetOff').addEventListener('click', async () => {
    if (confirm('Are you sure you want to close today’s session?')) {
      await setDoctorPresence('AWAY', 'Session closed');
      await setAvailability('OFF', 'Session closed', 'OFF');
    }
  });

  // 1-Click Booking Toggle
  document.getElementById('btnToggleBooking').addEventListener('click', async () => {
    if (!activeChamberId) {
      showNotice('Please select an active chamber first.', 'danger');
      return;
    }
    const newState = !isBookingOpen;
    try {
      const today = new Date().toISOString().split('T')[0];
      const res = await Api.post('/doctor/booking/toggle', {
        chamber_id: activeChamberId,
        date: today,
        is_open: newState
      });
      if (res.success) {
        isBookingOpen = newState;
        updatePresenceUI(currentPresence, isBookingOpen);
        showNotice(newState ? '🟢 Chamber booking opened (নতুন সিরিয়াল নেওয়া চালু).' : '🔴 Chamber booking closed (সিরিয়াল সাময়িক বন্ধ).');
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnCallNext').addEventListener('click', async () => {
    if (!activeChamberId) {
      showNotice('Please select an active chamber.', 'danger');
      return;
    }
    try {
      const res = await Api.post('/queue/doctor/call-next', { chamber_id: activeChamberId });
      if (res.success) {
        showNotice(`Called token ${res.data.token_display}`);
        loadQueue();
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  // Delegated events for Waiting Table (Call & Swap)
  document.getElementById('waitingTableBody').addEventListener('click', async (e) => {
    const callBtn = e.target.closest('.btn-call-specific');
    if (callBtn) {
      const aptId = callBtn.dataset.id;
      try {
        const res = await Api.post('/queue/doctor/call-next', {
          chamber_id: activeChamberId,
          appointment_id: aptId
        });
        showNotice(`Called token ${callBtn.dataset.token}`);
        loadQueue();
      } catch (err) {
        showNotice(err.message, 'danger');
      }
      return;
    }

    const swapBtn = e.target.closest('.btn-swap');
    if (swapBtn) {
      const id1 = swapBtn.dataset.id;
      const id2 = swapBtn.dataset.targetId;
      try {
        const res = await Api.post('/queue/doctor/swap', {
          appointment_id_1: id1,
          appointment_id_2: id2
        });
        showNotice('Queue order swapped successfully.');
        loadQueue();
      } catch (err) {
        showNotice(err.message, 'danger');
      }
    }
  });

  // Delegated events for Skipped Table (Recall)
  const skippedTbody = document.getElementById('skippedTableBody');
  if (skippedTbody) {
    skippedTbody.addEventListener('click', async (e) => {
      const recallBtn = e.target.closest('.btn-recall');
      if (recallBtn) {
        const aptId = recallBtn.dataset.id;
        try {
          const res = await Api.post('/queue/doctor/recall-skipped', {
            appointment_id: aptId
          });
          showNotice(res.message || `Token ${recallBtn.dataset.token} recalled to active queue.`);
          loadQueue();
        } catch (err) {
          showNotice(err.message, 'danger');
        }
      }
    });
  }

  document.getElementById('btnStartConsultation').addEventListener('click', async () => {
    if (!calledAptId) return;
    try {
      await Api.post('/queue/doctor/start', { appointment_id: calledAptId });
      showNotice('Consultation started.');
      loadQueue();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnSkipPatient').addEventListener('click', async () => {
    if (!calledAptId) return;
    try {
      await Api.post('/queue/doctor/skip', { appointment_id: calledAptId });
      showNotice('Patient skipped and placed in Skipped roster.');
      loadQueue();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnNoShowPatient').addEventListener('click', async () => {
    if (!calledAptId) return;
    try {
      await Api.post('/queue/doctor/no-show', { appointment_id: calledAptId });
      showNotice('Patient marked as No-Show.');
      loadQueue();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnCompletePaperActive').addEventListener('click', async () => {
    if (!currentAptId) return;
    try {
      await Api.post('/queue/doctor/complete', { appointment_id: currentAptId });
      showNotice('Consultation completed (Handwritten Rx). Queue advanced.');
      loadQueue();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnCalledCompletePaper').addEventListener('click', async () => {
    if (!calledAptId) return;
    try {
      await Api.post('/queue/doctor/complete', { appointment_id: calledAptId });
      showNotice('Consultation completed (Handwritten Rx).');
      loadQueue();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnLogout').addEventListener('click', async () => {
    await Api.post('/auth/logout', {});
    Api.clearToken();
    window.location.href = 'login.php';
  });

  // Initial load
  await initChambers();
  loadQueue();
  setInterval(loadQueue, 8000); // Live refresh every 8 seconds
</script>

</body>
</html>
