<?php
$activePage = 'dashboard';
$pageTitle = "Today's Chamber Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Dashboard — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <!-- Top Alert Notification -->
      <div id="noticeBox" style="display: none; padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 500;"></div>

      <!-- Active Break Banner (shown when PAUSED) -->
      <div id="breakBanner" style="display: none; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 10px; padding: 14px 20px; margin-bottom: 20px; color: #92400e; font-weight: 600; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 24px;" id="breakBannerIcon">⏸️</span>
          <div>
            <div style="font-size: 15px; font-weight: 700;" id="breakBannerTitle">Chamber is currently on Break</div>
            <div style="font-size: 13px; font-weight: 500; opacity: 0.9;" id="breakBannerNote">Waiting patients have been notified.</div>
          </div>
        </div>
        <button class="btn btn-success btn-sm" id="btnResumeBreak" style="padding: 8px 18px; font-size: 13px; font-weight: 700;">🟢 Resume Chamber Now</button>
      </div>

      <!-- Quick Session Control Bar with Multi-Chamber Switcher -->
      <div class="card" style="margin-bottom: 28px; background: #ffffff; border-left: 5px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div style="display: flex; align-items: center; gap: 16px;">
            <div>
              <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Active Chamber</div>
              <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px;">
                <select id="chamberSelect" class="form-control" style="font-size: 16px; font-weight: 700; min-width: 240px; padding: 8px 12px; border-color: var(--primary);">
                  <option value="">Loading chambers...</option>
                </select>
                <div id="sessionDate" style="font-size: 13px; color: var(--text-muted); white-space: nowrap;"></div>
              </div>
            </div>
          </div>

          <!-- Rapid Availability & Break Presets -->
          <div style="display: flex; flex-direction: column; gap: 8px; background: #f8fafc; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
              <span style="font-weight: 600; font-size: 13px;">Session Status:</span>
              <div id="availabilityPill" class="status-pill status-on">🟢 ON</div>
            </div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
              <button class="btn btn-success btn-sm" id="btnSetOn" title="Resume Consultation" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🟢 Online</button>
              <button class="btn btn-warning btn-sm" id="btnPrayerBreak" title="Prayer Break" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🕌 Prayer Break</button>
              <button class="btn btn-warning btn-sm" id="btnMealBreak" title="Meal Break" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🍽️ Meal Break</button>
              <button class="btn btn-outline btn-sm" id="btnCustomPause" title="Short Pause" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">⏸️ Pause</button>
              <button class="btn btn-danger btn-sm" id="btnSetOff" title="Close Session" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">🛑 Close</button>
            </div>
          </div>

          <div>
            <a href="queue.php" id="linkQueueConsole" class="btn btn-primary" style="padding: 12px 22px; font-size: 14px; font-weight: 600;">
              Live Queue Console &rarr;
            </a>
          </div>
        </div>
      </div>

      <!-- Live KPI Cards -->
      <div class="grid grid-cols-4" style="margin-bottom: 28px;">
        <div class="card">
          <div class="card-header">
            <span class="card-title">Current Token</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="card-value" id="cardCurrentToken" style="color: #0284c7;">--</div>
          <div style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">Currently inside chamber</div>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Waiting Patients</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div class="card-value" id="cardWaiting" style="color: #d97706;">0</div>
          <div style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">Checked-in & awaiting turn</div>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Completed Today</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <div class="card-value" id="cardCompleted" style="color: #16a34a;">0</div>
          <div style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">Prescriptions concluded</div>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Capacity & Slots</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/></svg>
          </div>
          <div class="card-value" id="cardCapacity">0 / 0</div>
          <div id="cardRemainingText" style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">0 slots remaining</div>
        </div>
      </div>

      <!-- Quick Delay Broadcast & Capacity Management -->
      <div class="grid grid-cols-2">
        <div class="card">
          <h3 style="font-size: 18px; margin-bottom: 16px;">Broadcast Chamber Delay</h3>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 18px;">
            In case of surgery, traffic, or emergency, inform waiting patients immediately. Patient apps will recalculate arrival times automatically.
          </p>
          <div style="display: flex; gap: 12px; align-items: flex-end;">
            <div style="flex-grow: 1;">
              <label class="form-label" for="delayMinutes">Delay (Minutes)</label>
              <select id="delayMinutes" class="form-control">
                <option value="0">On Time (0 min delay)</option>
                <option value="15">15 Minutes Delay</option>
                <option value="30">30 Minutes Delay</option>
                <option value="45">45 Minutes Delay</option>
                <option value="60">1 Hour Delay</option>
              </select>
            </div>
            <button id="btnBroadcastDelay" class="btn btn-warning" style="height: 42px;">
              Broadcast Alert
            </button>
          </div>
        </div>

        <div class="card">
          <h3 style="font-size: 18px; margin-bottom: 16px;">Adjust Session Capacity</h3>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 18px;">
            Increase today's maximum patient intake. Capacity cannot be reduced below confirmed bookings.
          </p>
          <div style="display: flex; gap: 12px; align-items: flex-end;">
            <div style="flex-grow: 1;">
              <label class="form-label" for="newCapacityInput">Total Daily Capacity</label>
              <input type="number" id="newCapacityInput" class="form-control" min="1" max="100" value="20">
            </div>
            <button id="btnUpdateCapacity" class="btn btn-outline" style="height: 42px;">
              Update Capacity
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="module">
  import { Api } from '../assets/js/api.js';

  let activeChamberId = localStorage.getItem('doc_selected_chamber_id') ? parseInt(localStorage.getItem('doc_selected_chamber_id'), 10) : null;
  let chambersList = [];

  async function loadDashboard() {
    try {
      const url = activeChamberId ? `/doctor/dashboard?chamber_id=${activeChamberId}` : '/doctor/dashboard';
      const res = await Api.get(url);
      if (!res.success) return;

      const data = res.data;
      const stats = data.stats;
      chambersList = data.chambers || [];

      // Populate Chamber Select
      const select = document.getElementById('chamberSelect');
      if (chambersList.length > 0) {
        if (!activeChamberId || !chambersList.some(c => c.id === activeChamberId)) {
          activeChamberId = chambersList[0].id;
          localStorage.setItem('doc_selected_chamber_id', activeChamberId);
        }
        select.innerHTML = chambersList.map(c => `
          <option value="${c.id}" ${c.id === activeChamberId ? 'selected' : ''}>
            ${c.name} (${c.city_name || c.area_name || 'Chamber'})
          </option>
        `).join('');
      } else {
        select.innerHTML = '<option value="">No Chambers Configured</option>';
      }

      // Update link to queue console
      if (activeChamberId) {
        document.getElementById('linkQueueConsole').href = `queue.php?chamber_id=${activeChamberId}`;
      }

      document.getElementById('sessionDate').innerText = '• ' + stats.today_date;
      document.getElementById('cardCurrentToken').innerText = stats.current_token || 'None';
      document.getElementById('cardWaiting').innerText = stats.waiting_count;
      document.getElementById('cardCompleted').innerText = stats.completed_count;
      document.getElementById('cardCapacity').innerText = stats.total_booked + ' / ' + stats.capacity;
      document.getElementById('cardRemainingText').innerText = stats.remaining + ' slots remaining';
      document.getElementById('newCapacityInput').value = stats.capacity;
      document.getElementById('delayMinutes').value = stats.delay_minutes || 0;

      updateAvailabilityUI(stats.availability, stats.status_note);

      // Header doctor details
      if (data.doctor) {
        document.getElementById('headerDocName').innerText = data.doctor.full_name;
        document.getElementById('headerDocDesig').innerText = data.doctor.doctor ? data.doctor.doctor.bmdc_number : '';
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  function updateAvailabilityUI(status, note) {
    const pill = document.getElementById('availabilityPill');
    const banner = document.getElementById('breakBanner');
    pill.className = 'status-pill status-' + status.toLowerCase();

    if (status === 'ON') {
      pill.innerText = '🟢 ONLINE (Accepting)';
      banner.style.display = 'none';
    } else if (status === 'PAUSED') {
      pill.innerText = '🟡 PAUSED';
      banner.style.display = 'flex';
      const icon = (note && note.includes('Prayer')) ? '🕌' : ((note && note.includes('Meal')) ? '🍽️' : '⏸️');
      document.getElementById('breakBannerIcon').innerText = icon;
      document.getElementById('breakBannerTitle').innerText = note || 'Chamber Session Paused';
      document.getElementById('breakBannerNote').innerText = 'New bookings paused. Waiting patients can see this status in real-time.';
    } else if (status === 'OFF' || status === 'CLOSED') {
      pill.innerText = '🔴 ' + status;
      banner.style.display = 'none';
    } else {
      pill.innerText = status;
      banner.style.display = 'none';
    }
  }

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    if (type === 'success') {
      box.style.background = '#dcfce7';
      box.style.color = '#15803d';
    } else {
      box.style.background = '#fee2e2';
      box.style.color = '#dc2626';
    }
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
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
        showNotice(`Chamber status updated: ${status} ${reason ? '— ' + reason : ''}`);
        loadDashboard();
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  // Chamber selection change
  document.getElementById('chamberSelect').addEventListener('change', (e) => {
    activeChamberId = parseInt(e.target.value, 10);
    localStorage.setItem('doc_selected_chamber_id', activeChamberId);
    document.getElementById('linkQueueConsole').href = `queue.php?chamber_id=${activeChamberId}`;
    loadDashboard();
  });

  // Break / Status Actions
  document.getElementById('btnSetOn').addEventListener('click', () => setAvailability('ON', '', 'AVAILABLE'));
  document.getElementById('btnResumeBreak').addEventListener('click', () => setAvailability('ON', '', 'AVAILABLE'));
  document.getElementById('btnPrayerBreak').addEventListener('click', () => setAvailability('PAUSED', '🕌 Prayer Break (নামাজের বিরতি)', 'SHORT_BREAK'));
  document.getElementById('btnMealBreak').addEventListener('click', () => setAvailability('PAUSED', '🍽️ Meal Break (খাওয়ার বিরতি)', 'SHORT_BREAK'));
  document.getElementById('btnCustomPause').addEventListener('click', () => {
    const custom = prompt('Enter break reason (e.g., Round check, Emergency, Tea break):', '⏸️ Quick Break (সাময়িক বিরতি)');
    if (custom !== null) {
      setAvailability('PAUSED', custom, 'SHORT_BREAK');
    }
  });
  document.getElementById('btnSetOff').addEventListener('click', () => {
    if (confirm('Are you sure you want to close today’s session for this chamber?')) {
      setAvailability('OFF', 'Chamber session closed for today', 'OFF');
    }
  });

  document.getElementById('btnBroadcastDelay').addEventListener('click', async () => {
    const mins = parseInt(document.getElementById('delayMinutes').value, 10);
    try {
      await Api.post('/queue/doctor/delay', {
        chamber_id: activeChamberId,
        delay_minutes: mins
      });
      showNotice(`Broadcasted ${mins} minutes delay to waiting patients.`);
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnUpdateCapacity').addEventListener('click', async () => {
    const cap = parseInt(document.getElementById('newCapacityInput').value, 10);
    try {
      await Api.post('/doctor/capacity/update', {
        chamber_id: activeChamberId,
        total_capacity: cap
      });
      showNotice('Capacity updated to ' + cap);
      loadDashboard();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('btnLogout').addEventListener('click', async () => {
    await Api.post('/auth/logout', {});
    Api.clearToken();
    window.location.href = 'login.php';
  });

  loadDashboard();
  // Live refresh every 15 seconds
  setInterval(loadDashboard, 15000);
</script>

</body>
</html>
