<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Portal Login — MEDIQ</title>
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
      box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 24px;
      font-weight: 800;
      color: #0284c7;
      margin-bottom: 8px;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="brand-logo">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
    </svg>
    <span>MEDIQ Doctor</span>
  </div>
  <p style="color: #64748b; font-size: 14px; margin-bottom: 28px;">
    Chamber serial, live queue & digital prescription console
  </p>

  <div id="alertBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;"></div>

  <form id="loginForm">
    <div class="form-group">
      <label class="form-label" for="phone">Mobile Number</label>
      <input type="text" id="phone" class="form-control" placeholder="+8801711111111" required value="+8801711111111">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input type="password" id="password" class="form-control" placeholder="••••••••" required value="Doctor@123">
    </div>

    <button type="submit" id="btnSubmit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px; margin-top: 10px;">
      Log In to Chamber Console
    </button>
  </form>

  <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #64748b; text-align: center;">
    Demo Credentials: <strong>+8801711111111</strong> / <strong>Doctor@123</strong>
  </div>
</div>

<script type="module">
  import { Api } from '../assets/js/api.js';

  const form = document.getElementById('loginForm');
  const alertBox = document.getElementById('alertBox');
  const btnSubmit = document.getElementById('btnSubmit');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.style.display = 'none';
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Authenticating...';

    const phone = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;

    try {
      const res = await Api.post('/auth/login', { phone, password });
      if (res.success) {
        if (res.data.user.role !== 'DOCTOR') {
          throw new Error('Access denied. This portal is strictly for registered Doctors.');
        }

        Api.setToken(res.data.token);
        localStorage.setItem('mediq_doctor_user', JSON.stringify(res.data.user));
        window.location.href = 'dashboard.php';
      }
    } catch (err) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#dc2626';
      alertBox.innerText = err.message || 'Login failed. Check credentials.';
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerText = 'Log In to Chamber Console';
    }
  });
</script>

</body>
</html>
