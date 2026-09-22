<?php
$activePage = 'appointments';
$pageTitle = 'Chamber Appointments Roster';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointments Roster — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <!-- Date & Filter Controls -->
      <div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
        <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
          <div>
            <label class="form-label" for="filterDate">Filter Date</label>
            <input type="date" id="filterDate" class="form-control" style="width: 180px;">
          </div>
          <button id="btnApplyFilter" class="btn btn-primary" style="height: 40px;">Filter Roster</button>
        </div>
      </div>

      <!-- Appointments Table -->
      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Serial / Token</th>
                <th>Appointment Number</th>
                <th>Patient Details</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="appointmentsTableBody">
              <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading appointments...</td>
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

  const dateInput = document.getElementById('filterDate');
  dateInput.value = new Date().toISOString().split('T')[0];

  async function loadAppointments() {
    const dt = dateInput.value;
    try {
      const res = await Api.get(`/appointments?date=${dt}`);
      if (!res.success) return;

      const apts = res.data;
      const tbody = document.getElementById('appointmentsTableBody');
      if (apts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No appointments found for this date.</td></tr>';
        return;
      }

      tbody.innerHTML = apts.map(a => `
        <tr>
          <td><strong style="font-size: 16px; color: var(--primary);">${a.token_display}</strong></td>
          <td><code>${a.appointment_number}</code></td>
          <td><strong>${a.patient_name}</strong><br><small style="color: var(--text-muted);">${a.patient_phone || ''}</small></td>
          <td><span class="status-pill status-${a.patient_type === 'NEW' ? 'on' : 'paused'}">${a.patient_type}</span></td>
          <td><span class="status-pill status-${a.status === 'COMPLETED' ? 'on' : (a.status === 'CANCELLED' ? 'off' : 'paused')}">${a.status}</span></td>
          <td>
            ${a.status === 'COMPLETED' ? 
              `<a href="consultation.php?appointment_id=${a.id}" class="btn btn-outline btn-sm">View Rx</a>` : 
              `<a href="queue.php" class="btn btn-primary btn-sm">Live Queue</a>`}
          </td>
        </tr>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  document.getElementById('btnApplyFilter').addEventListener('click', loadAppointments);
  document.getElementById('btnLogout').addEventListener('click', async () => {
    await Api.post('/auth/logout', {});
    Api.clearToken();
    window.location.href = 'login.php';
  });

  loadAppointments();
</script>

</body>
</html>
