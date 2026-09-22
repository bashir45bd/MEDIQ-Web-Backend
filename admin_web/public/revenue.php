<?php
$activePage = 'revenue';
$pageTitle = 'Platform Revenue & Financial Ledger';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Revenue Ledger — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Txn ID</th>
                <th>Appointment Code</th>
                <th>Payer Name</th>
                <th>Type</th>
                <th>Amount (BDT)</th>
                <th>Gateway</th>
                <th>Platform Balance</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody id="revenueTableBody">
              <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading financial ledger...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  async function loadRevenue() {
    try {
      const res = await AdminApi.get('/admin/revenue');
      if (!res.success) return;

      const rows = res.data;
      const tbody = document.getElementById('revenueTableBody');
      if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">No platform fee collections recorded yet.</td></tr>';
        return;
      }

      tbody.innerHTML = rows.map(r => `
        <tr>
          <td><code>#${r.id}</code></td>
          <td><strong>${r.appointment_number}</strong></td>
          <td>${r.payer_name}</td>
          <td><span class="status-pill status-verified">${r.transaction_type}</span></td>
          <td><strong style="color: #059669;">+৳${parseFloat(r.amount).toFixed(2)}</strong></td>
          <td>${r.gateway_name} (<code>${r.transaction_ref}</code>)</td>
          <td>৳${parseFloat(r.balance_after).toFixed(2)}</td>
          <td>${new Date(r.created_at).toLocaleString()}</td>
        </tr>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  document.getElementById('btnLogoutAdmin').addEventListener('click', async () => {
    await AdminApi.post('/auth/logout', {});
    AdminApi.clearToken();
    window.location.href = 'login.php';
  });

  loadRevenue();
</script>

</body>
</html>
