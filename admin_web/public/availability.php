<?php
$activePage = 'availability';
$pageTitle = 'Emergency Availability & Capacity Override';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emergency Overrides — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <div class="card" style="max-width: 650px; margin-bottom: 30px; border-left: 5px solid var(--warning);">
        <h3 style="font-size: 18px; margin-bottom: 8px;">Administrative Emergency Session Override</h3>
        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
          Admins can immediately suspend booking, close an active chamber, or reopen slots. In accordance with platform governance, all overrides write an immutable entry to <code>audit_logs</code>.
        </p>

        <form id="overrideForm">
          <div class="form-group">
            <label class="form-label" for="doctorId">Doctor ID</label>
            <input type="number" id="doctorId" class="form-control" placeholder="1" required value="1">
          </div>

          <div class="form-group">
            <label class="form-label" for="chamberId">Chamber ID</label>
            <input type="number" id="chamberId" class="form-control" placeholder="1" required value="1">
          </div>

          <div class="form-group">
            <label class="form-label" for="appointmentDate">Session Date</label>
            <input type="date" id="appointmentDate" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="newStatus">New Session Status</label>
            <select id="newStatus" class="form-control">
              <option value="ON">🟢 ON (Allow Bookings)</option>
              <option value="PAUSED">🟡 PAUSED (Hold Intake)</option>
              <option value="OFF">🔴 OFF (Closed)</option>
              <option value="CLOSED">⬛ CLOSED (Manually Terminated)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="overrideReason">Mandatory Audit Reason</label>
            <textarea id="overrideReason" class="form-control" rows="3" placeholder="e.g. Doctor emergency surgery reported via helpline. Bookings suspended." required></textarea>
          </div>

          <button type="submit" id="btnSubmitOverride" class="btn btn-warning" style="width: 100%; padding: 12px; font-size: 16px;">
            Execute Emergency Override & Log Action
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  document.getElementById('appointmentDate').value = new Date().toISOString().split('T')[0];

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  document.getElementById('overrideForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitOverride');
    btn.disabled = true;
    btn.innerText = 'Applying Override...';

    const payload = {
      doctor_id: parseInt(document.getElementById('doctorId').value, 10),
      chamber_id: parseInt(document.getElementById('chamberId').value, 10),
      appointment_date: document.getElementById('appointmentDate').value,
      status: document.getElementById('newStatus').value,
      reason: document.getElementById('overrideReason').value.trim()
    };

    try {
      const res = await AdminApi.post('/admin/availability/override', payload);
      if (res.success) {
        showNotice(res.message);
        document.getElementById('overrideReason').value = '';
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Execute Emergency Override & Log Action';
    }
  });

  document.getElementById('btnLogoutAdmin').addEventListener('click', async () => {
    await AdminApi.post('/auth/logout', {});
    AdminApi.clearToken();
    window.location.href = 'login.php';
  });
</script>

</body>
</html>
