<?php
$activePage = 'promotions';
$pageTitle = 'Promotions, Free Appointments & Coupons';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Promotions — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 20px; font-weight: 700;">Active Platform Campaigns</h3>
        <button id="btnOpenPromoModal" class="btn btn-primary">+ Create Promotion</button>
      </div>

      <!-- Promotions Table -->
      <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 30px;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Campaign Name</th>
                <th>Code</th>
                <th>Type & Discount</th>
                <th>Validity Window</th>
                <th>Usage Cap</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="promoTableBody">
              <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading campaigns...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create Promo Modal -->
      <div id="promoModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 500px;">
          <h3 style="font-size: 18px; margin-bottom: 16px;">Create Promotion Campaign</h3>
          <form id="promoForm">
            <div class="form-group">
              <label class="form-label" for="promoName">Campaign Name</label>
              <input type="text" id="promoName" class="form-control" placeholder="e.g. Ramadan Health Campaign" required>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="promoCode">Promo Code</label>
                <input type="text" id="promoCode" class="form-control" placeholder="HEALTH50" required style="text-transform: uppercase;">
              </div>
              <div class="form-group">
                <label class="form-label" for="promoType">Discount Type</label>
                <select id="promoType" class="form-control">
                  <option value="FREE">FREE (100% platform fee waived)</option>
                  <option value="PERCENT">PERCENT (%)</option>
                  <option value="FIXED">FIXED (BDT Amount)</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="discountVal">Discount Value</label>
                <input type="number" step="0.01" id="discountVal" class="form-control" value="50.00" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="maxDiscount">Max Discount (BDT)</label>
                <input type="number" step="0.01" id="maxDiscount" class="form-control" placeholder="Optional cap">
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="startDate">Start Date</label>
                <input type="date" id="startDate" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="endDate">End Date</label>
                <input type="date" id="endDate" class="form-control" required>
              </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
              <button type="button" id="btnClosePromoModal" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary">Create Campaign</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  const today = new Date().toISOString().split('T')[0];
  document.getElementById('startDate').value = today;
  const nextYear = new Date();
  nextYear.setFullYear(nextYear.getFullYear() + 1);
  document.getElementById('endDate').value = nextYear.toISOString().split('T')[0];

  async function loadPromotions() {
    try {
      const res = await AdminApi.get('/admin/promotions');
      if (!res.success) return;

      const promos = res.data;
      const tbody = document.getElementById('promoTableBody');
      if (promos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No active promotions.</td></tr>';
        return;
      }

      tbody.innerHTML = promos.map(p => `
        <tr>
          <td><strong>${p.name}</strong></td>
          <td><code style="font-size: 15px; font-weight: 700; color: #4f46e5;">${p.code}</code></td>
          <td>
            ${p.type === 'FREE' ? '<span class="status-pill status-verified">FREE (100% OFF)</span>' : ''}
            ${p.type === 'PERCENT' ? `<span class="status-pill status-waiting">${p.discount_value}% OFF</span>` : ''}
            ${p.type === 'FIXED' ? `<span class="status-pill status-waiting">৳${p.discount_value} OFF</span>` : ''}
          </td>
          <td>${p.start_date} to ${p.end_date}</td>
          <td>${p.usage_limit || 'Unlimited'} (${p.per_user_limit} per user)</td>
          <td><span class="status-pill status-verified">${p.status}</span></td>
        </tr>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  const modal = document.getElementById('promoModal');
  document.getElementById('btnOpenPromoModal').addEventListener('click', () => { modal.style.display = 'flex'; });
  document.getElementById('btnClosePromoModal').addEventListener('click', () => { modal.style.display = 'none'; });

  document.getElementById('promoForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      name: document.getElementById('promoName').value.trim(),
      code: document.getElementById('promoCode').value.trim().toUpperCase(),
      type: document.getElementById('promoType').value,
      discount_value: parseFloat(document.getElementById('discountVal').value),
      max_discount: document.getElementById('maxDiscount').value ? parseFloat(document.getElementById('maxDiscount').value) : null,
      start_date: document.getElementById('startDate').value,
      end_date: document.getElementById('endDate').value
    };

    try {
      const res = await AdminApi.post('/admin/promotions', payload);
      if (res.success) {
        modal.style.display = 'none';
        loadPromotions();
      }
    } catch (err) {
      alert(err.message || 'Failed to create promotion');
    }
  });

  document.getElementById('btnLogoutAdmin').addEventListener('click', async () => {
    await AdminApi.post('/auth/logout', {});
    AdminApi.clearToken();
    window.location.href = 'login.php';
  });

  loadPromotions();
</script>

</body>
</html>
