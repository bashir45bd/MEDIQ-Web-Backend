<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal Login — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      min-height: 100vh;
      padding: 20px;
    }
    .login-card {
      background: #ffffff;
      width: 100%;
      max-width: 420px;
      padding: 40px;
      border-radius: 12px;
      box-shadow: var(--shadow);
    }
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 24px;
      font-weight: 800;
      color: #4f46e5;
      margin-bottom: 8px;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="brand-logo">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
    </svg>
    <span>MEDIQ Admin</span>
  </div>
  <p style="color: #64748b; font-size: 14px; margin-bottom: 28px;">
    Platform Governance & Doctor Credentialing Console
  </p>

  <div id="alertBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;"></div>

  <form id="adminLoginForm">
    <div class="form-group">
      <label class="form-label" for="phone">Admin Phone or Email</label>
      <input type="text" id="phone" class="form-control" placeholder="+8801700000001" required value="+8801700000001">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Admin Password</label>
      <input type="password" id="password" class="form-control" placeholder="••••••••" required value="Admin@12345">
    </div>

    <button type="submit" id="btnSubmit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px; margin-top: 10px;">
      Authenticate Administrator
    </button>
  </form>

  <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #64748b; text-align: center;">
    Demo Admin: <strong>+8801700000001</strong> / <strong>Admin@12345</strong>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  const form = document.getElementById('adminLoginForm');
  const alertBox = document.getElementById('alertBox');
  const btnSubmit = document.getElementById('btnSubmit');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.style.display = 'none';
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Verifying...';

    const phone = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;

    try {
      const res = await AdminApi.post('/auth/login', { phone, password });
      if (res.success) {
        if (res.data.user.role !== 'PLATFORM_ADMIN' && res.data.user.role !== 'SUPER_ADMIN') {
          throw new Error('Access denied. Administrator privileges required.');
        }

        AdminApi.setToken(res.data.token);
        localStorage.setItem('mediq_admin_user', JSON.stringify(res.data.user));
        window.location.href = 'dashboard.php';
      }
    } catch (err) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#dc2626';
      alertBox.innerText = err.message || 'Authentication failed.';
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerText = 'Authenticate Administrator';
    }
  });
</script>

</body>
</html>
