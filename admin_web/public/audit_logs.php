<?php
$activePage = 'audit_logs';
$pageTitle = 'Immutable Platform Audit Trail';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Audit Logs — MEDIQ Admin</title>
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
                <th>ID</th>
                <th>Actor</th>
                <th>Role</th>
                <th>Action Code</th>
                <th>Entity Target</th>
                <th>Diff / Metadata</th>
                <th>IP Address</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody id="auditTableBody">
              <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading audit events...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  async function loadAuditLogs() {
    try {
      const res = await AdminApi.get('/admin/audit-logs');
      if (!res.success) return;

      const logs = res.data;
      const tbody = document.getElementById('auditTableBody');
      if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">No audit events recorded yet.</td></tr>';
        return;
      }

      tbody.innerHTML = logs.map(l => `
        <tr>
          <td><code>#${l.id}</code></td>
          <td><strong>${l.actor_name || 'System'}</strong><br><small style="color: var(--text-muted);">${l.actor_email || ''}</small></td>
          <td><span class="status-pill status-waiting">${l.actor_role}</span></td>
          <td><code style="font-weight: 600; color: #4f46e5;">${l.action}</code></td>
          <td>${l.entity} (#${l.entity_id})</td>
          <td>
            <div style="font-size: 11px; max-width: 250px; overflow-x: auto; white-space: pre-wrap; font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;">
              ${l.new_value_json || l.old_value_json || 'N/A'}
            </div>
          </td>
          <td><code>${l.ip_address}</code></td>
          <td>${new Date(l.created_at).toLocaleString()}</td>
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

  loadAuditLogs();
</script>

</body>
</html>
