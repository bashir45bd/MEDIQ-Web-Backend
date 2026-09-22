<?php
$activePage = 'dashboard';
$pageTitle = 'Platform Governance Overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <!-- KPI Cards Grid -->
      <div class="grid grid-cols-4" style="margin-bottom: 28px;">
        <div class="card">
          <div class="card-header">
            <span class="card-title">Verified Doctors</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
          </div>
          <div class="card-value" id="kpiVerifiedDocs" style="color: #16a34a;">0</div>
          <div id="kpiTotalDocs" style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">Total: 0 registered</div>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Pending BMDC Checks</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
          </div>
          <div class="card-value" id="kpiPendingDocs" style="color: #d97706;">0</div>
          <div style="font-size: 13px; margin-top: 6px;">
            <a href="doctors.php?status=PENDING" style="color: var(--primary); text-decoration: none; font-weight: 600;">Review applications &rarr;</a>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Registered Patients</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div class="card-value" id="kpiTotalPatients" style="color: #4f46e5;">0</div>
          <div style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">Active patient accounts</div>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Monthly Platform Revenue</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="card-value" id="kpiMonthlyRev" style="color: #059669;">৳0.00</div>
          <div id="kpiTodayRev" style="font-size: 13px; color: var(--text-muted); margin-top: 6px;">Today: ৳0.00</div>
        </div>
      </div>

      <!-- Today's Chamber Traffic & Health -->
      <div class="grid grid-cols-2">
        <div class="card">
          <h3 style="font-size: 18px; margin-bottom: 16px;">Today's Platform Appointments</h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 10px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
              <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">CONFIRMED APPOINTMENTS</div>
              <div id="trafficTotal" style="font-size: 28px; font-weight: 700; color: #4f46e5; margin-top: 4px;">0</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
              <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">COMPLETED CONSULTATIONS</div>
              <div id="trafficCompleted" style="font-size: 28px; font-weight: 700; color: #16a34a; margin-top: 4px;">0</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
              <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">NO-SHOW BYPASSED</div>
              <div id="trafficNoShow" style="font-size: 28px; font-weight: 700; color: #d97706; margin-top: 4px;">0</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
              <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">CANCELLED BOOKINGS</div>
              <div id="trafficCancelled" style="font-size: 28px; font-weight: 700; color: #dc2626; margin-top: 4px;">0</div>
            </div>
          </div>
        </div>

        <div class="card">
          <h3 style="font-size: 18px; margin-bottom: 16px;">System Health & Quick Links</h3>
          <ul style="list-style: none; display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
            <li style="display: flex; justify-content: space-between; padding: 12px; background: #f8fafc; border-radius: 6px;">
              <span>Database Engine (MySQL 8+ / MariaDB)</span>
              <span class="status-pill status-verified">ACID Active</span>
            </li>
            <li style="display: flex; justify-content: space-between; padding: 12px; background: #f8fafc; border-radius: 6px;">
              <span>Standard Timezone</span>
              <span class="status-pill status-verified">Asia/Dhaka (+06:00)</span>
            </li>
            <li style="display: flex; justify-content: space-between; padding: 12px; background: #f8fafc; border-radius: 6px;">
              <span>Active Promotion Campaigns</span>
              <span id="activePromoCount" style="font-weight: 700; color: #4f46e5;">0 Campaigns</span>
            </li>
          </ul>
          <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="availability.php" class="btn btn-warning" style="flex: 1;">Emergency Overrides</a>
            <a href="audit_logs.php" class="btn btn-outline" style="flex: 1;">View Audit Logs</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  async function loadDashboard() {
    try {
      const res = await AdminApi.get('/admin/dashboard');
      if (!res.success) return;

      const d = res.data;
      document.getElementById('kpiVerifiedDocs').innerText = d.doctors.verified_doctors || 0;
      document.getElementById('kpiTotalDocs').innerText = `Total: ${d.doctors.total_doctors || 0} registered`;
      document.getElementById('kpiPendingDocs').innerText = d.doctors.pending_doctors || 0;
      document.getElementById('kpiTotalPatients').innerText = d.total_patients || 0;
      document.getElementById('kpiMonthlyRev').innerText = '৳' + parseFloat(d.monthly_revenue || 0).toFixed(2);
      document.getElementById('kpiTodayRev').innerText = 'Today: ৳' + parseFloat(d.today.revenue_today || 0).toFixed(2);

      document.getElementById('trafficTotal').innerText = d.today.total_today || 0;
      document.getElementById('trafficCompleted').innerText = d.today.completed_today || 0;
      document.getElementById('trafficNoShow').innerText = d.today.no_show_today || 0;
      document.getElementById('trafficCancelled').innerText = d.today.cancelled_today || 0;
      document.getElementById('activePromoCount').innerText = `${d.active_promotions} Campaigns`;
    } catch (err) {
      console.error(err);
    }
  }

  document.getElementById('btnLogoutAdmin').addEventListener('click', async () => {
    await AdminApi.post('/auth/logout', {});
    AdminApi.clearToken();
    window.location.href = 'login.php';
  });

  loadDashboard();
</script>

</body>
</html>
