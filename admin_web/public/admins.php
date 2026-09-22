<?php
$activePage = 'admins';
$pageTitle = 'Platform Administrators & Role Governance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Team & Roles — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .role-super {
      background: #f3e8ff;
      color: #7e22ce;
      border: 1px solid #d8b4fe;
      font-weight: 700;
    }
    .role-platform {
      background: #e0f2fe;
      color: #0369a1;
      border: 1px solid #bae6fd;
      font-weight: 600;
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

      <!-- Top Action Bar -->
      <div class="card" style="margin-bottom: 24px; padding: 18px 24px; border-left: 5px solid #8b5cf6;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div>
            <h3 style="font-size: 19px; font-weight: 800; color: #1e1b4b;">Administrative Governance & Access Control</h3>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">
              Super Administrators have full privilege to appoint new Super Admins and Platform Admins.
            </p>
          </div>
          <button id="btnOpenAddAdminModal" class="btn btn-primary" style="background: #7c3aed; border-color: #6d28d9; padding: 10px 20px; font-weight: 700;">
            + Add New Administrator
          </button>
        </div>
      </div>

      <!-- Administrators Table -->
      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Administrator Name</th>
                <th>Access Role</th>
                <th>Contact Phone</th>
                <th>Email Address</th>
                <th>Status</th>
                <th>Joined Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="adminsTableBody">
              <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading administrators...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add Admin Modal -->
      <div id="addAdminModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 500px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 18px; font-weight: 800;">Appoint New Administrator</h3>
            <button id="btnCloseAdminModal" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
          </div>

          <form id="newAdminForm">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" id="adminName" class="form-control" placeholder="e.g. Tanzir Ahmed" required>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">Contact Mobile</label>
                <input type="text" id="adminPhone" class="form-control" placeholder="+88017..." required>
              </div>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" id="adminEmail" class="form-control" placeholder="admin@mediq.com.bd">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Account Password</label>
              <input type="password" id="adminPassword" class="form-control" placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group">
              <label class="form-label">Administrative Role</label>
              <select id="adminRole" class="form-control" required style="font-weight: 600;">
                <option value="PLATFORM_ADMIN">PLATFORM_ADMIN (Doctor, Queue, Revenue Management)</option>
                <option value="SUPER_ADMIN">SUPER_ADMIN (Full Platform & Admin Appointing Authority)</option>
              </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
              <button type="button" id="btnCancelAddAdmin" class="btn btn-outline">Cancel</button>
              <button type="submit" id="btnSubmitAdmin" class="btn btn-primary" style="background: #7c3aed; border-color: #6d28d9; padding: 10px 24px;">
                Create Administrator
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  async function loadAdministrators() {
    try {
      const res = await AdminApi.get('/admin/administrators');
      if (!res.success) return;

      const admins = res.data;
      const tbody = document.getElementById('adminsTableBody');

      if (admins.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No administrators found.</td></tr>';
        return;
      }

      tbody.innerHTML = admins.map(a => `
        <tr>
          <td><strong>${a.full_name}</strong></td>
          <td>
            <span class="status-pill ${a.role === 'SUPER_ADMIN' ? 'role-super' : 'role-platform'}">
              ${a.role === 'SUPER_ADMIN' ? '👑 SUPER_ADMIN' : '🛡️ PLATFORM_ADMIN'}
            </span>
          </td>
          <td><code>${a.phone}</code></td>
          <td>${a.email || '<span style="color: var(--text-muted);">N/A</span>'}</td>
          <td>
            <span class="status-pill status-${a.status.toLowerCase()}">${a.status}</span>
          </td>
          <td>${new Date(a.created_at).toLocaleDateString()}</td>
          <td>
            ${a.status === 'ACTIVE' ? `
              <button class="btn btn-warning btn-sm btn-status" data-id="${a.id}" data-status="SUSPENDED">Suspend</button>
            ` : `
              <button class="btn btn-success btn-sm btn-status" data-id="${a.id}" data-status="ACTIVE">Activate</button>
            `}
          </td>
        </tr>
      `).join('');
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  // Modal handlers
  const modal = document.getElementById('addAdminModal');
  document.getElementById('btnOpenAddAdminModal').addEventListener('click', () => { modal.style.display = 'flex'; });
  const closeModal = () => { modal.style.display = 'none'; };
  document.getElementById('btnCloseAdminModal').addEventListener('click', closeModal);
  document.getElementById('btnCancelAddAdmin').addEventListener('click', closeModal);

  // Form submit
  document.getElementById('newAdminForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitAdmin');
    btn.disabled = true;
    btn.innerText = 'Creating...';

    const payload = {
      full_name: document.getElementById('adminName').value.trim(),
      phone: document.getElementById('adminPhone').value.trim(),
      email: document.getElementById('adminEmail').value.trim(),
      password: document.getElementById('adminPassword').value,
      role: document.getElementById('adminRole').value
    };

    try {
      const res = await AdminApi.post('/admin/administrators', payload);
      if (res.success) {
        closeModal();
        document.getElementById('newAdminForm').reset();
        showNotice(`Administrator ${payload.full_name} created successfully!`);
        loadAdministrators();
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Create Administrator';
    }
  });

  // Table action delegation for status
  document.getElementById('adminsTableBody').addEventListener('click', async (e) => {
    if (e.target.classList.contains('btn-status')) {
      const id = e.target.dataset.id;
      const st = e.target.dataset.status;
      if (confirm(`Change administrator status to ${st}?`)) {
        try {
          await AdminApi.put(`/admin/administrators/${id}/status`, { status: st });
          showNotice(`Administrator status updated to ${st}.`);
          loadAdministrators();
        } catch (err) {
          showNotice(err.message, 'danger');
        }
      }
    }
  });

  document.getElementById('btnLogoutAdmin').addEventListener('click', async () => {
    await AdminApi.post('/auth/logout', {});
    AdminApi.clearToken();
    window.location.href = 'login.php';
  });

  loadAdministrators();
</script>

</body>
</html>
