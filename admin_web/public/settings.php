<?php
$activePage = 'settings';
$pageTitle = 'Platform Configuration & System Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Settings — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <div class="card" style="max-width: 700px;">
        <h3 style="font-size: 18px; margin-bottom: 8px;">Configurable Platform Business Parameters</h3>
        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px;">
          Fees, grace periods, and timeout limits are stored in the database and never hardcoded. Changes apply immediately to new transactions.
        </p>

        <div id="settingsContainer" style="display: flex; flex-direction: column; gap: 20px;">
          <div style="color: var(--text-muted);">Loading settings...</div>
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

  async function loadSettings() {
    try {
      const res = await AdminApi.get('/admin/settings');
      if (!res.success) return;

      const settings = res.data;
      const container = document.getElementById('settingsContainer');
      container.innerHTML = settings.map(s => `
        <div style="padding-bottom: 18px; border-bottom: 1px solid var(--border);">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
            <div>
              <strong style="font-size: 15px; color: #1e293b;"><code>${s.key_name}</code></strong>
              <div style="font-size: 13px; color: var(--text-muted);">${s.description || ''}</div>
            </div>
          </div>
          <div style="display: flex; gap: 10px; margin-top: 8px;">
            <input type="text" id="input_${s.key_name}" class="form-control" value="${s.setting_value}" style="max-width: 320px;">
            <button class="btn btn-primary btn-sm btn-save-setting" data-key="${s.key_name}">Save</button>
          </div>
        </div>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  document.getElementById('settingsContainer').addEventListener('click', async (e) => {
    if (e.target.classList.contains('btn-save-setting')) {
      const key = e.target.dataset.key;
      const val = document.getElementById(`input_${key}`).value.trim();
      try {
        await AdminApi.put('/admin/settings', { key_name: key, setting_value: val });
        showNotice(`Setting ${key} updated successfully.`);
      } catch (err) {
        showNotice(err.message, 'danger');
      }
    }
  });

  document.getElementById('btnLogoutAdmin').addEventListener('click', async () => {
    await AdminApi.post('/auth/logout', {});
    AdminApi.clearToken();
    window.location.href = 'login.php';
  });

  loadSettings();
</script>

</body>
</html>
