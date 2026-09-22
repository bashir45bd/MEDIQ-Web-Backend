<?php
$activePage = 'marketing';
$pageTitle = 'Marketing & Featured Doctor Banners';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketing Banners — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .slot-card {
      background: #ffffff;
      border: 1.5px dashed #cbd5e1;
      border-radius: 12px;
      padding: 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      transition: all 0.2s ease;
      min-height: 220px;
      justify-content: space-between;
    }
    .slot-card.active {
      border: 1.5px solid #8b5cf6;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(139, 92, 246, 0.08);
    }
    .slot-num {
      font-size: 11px;
      font-weight: 700;
      color: #6b21a8;
      background: #f3e8ff;
      padding: 3px 8px;
      border-radius: 12px;
      margin-bottom: 8px;
    }
    .slot-avatar {
      width: 54px;
      height: 54px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #e2e8f0;
      margin-bottom: 8px;
    }
    .slot-doctor-name {
      font-size: 13px;
      font-weight: 700;
      color: #1e293b;
      max-width: 100%;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .slot-promo-msg {
      font-size: 11.5px;
      color: #047857;
      background: #ecfdf5;
      padding: 4px 8px;
      border-radius: 6px;
      margin: 6px 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.3;
    }
  </style>
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 20px; font-weight: 700;">Home Carousel & Featured Marketing</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Control the 5 featured doctors shown in the Patient App's top slider. Add custom messages (e.g. <em>"ডা. রহিম স্যারের সিরিয়াল নিন এখন MEDIQ-তে"</em>) and send instant push notifications.</p>
        </div>
        <button id="btnOpenBannerModal" class="btn btn-primary">+ Create Custom Banner</button>
      </div>

      <!-- ⭐ 5 Featured Doctors Quick Manager Card -->
      <div class="card" style="margin-bottom: 24px; padding: 20px; background: #faf5ff; border: 1.5px solid #d8b4fe;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
          <div>
            <h4 style="font-size: 16px; font-weight: 700; color: #581c87;">⭐ 5 Featured Doctors (App Home Carousel Slider)</h4>
            <p style="font-size: 12.5px; color: #7e22ce; margin-top: 2px;">These 5 doctors appear prominently in the patient mobile app home slider. Click "Configure" on any slot to select a registered doctor and set your custom promotional tagline.</p>
          </div>
        </div>

        <div id="featuredDoctorsSlots" class="grid grid-cols-5" style="gap: 12px;">
          <!-- 5 slots generated via JavaScript -->
        </div>
      </div>

      <!-- All Banners Table Card -->
      <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 30px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
          <h4 style="font-size: 15px; font-weight: 700; margin: 0;">All Marketing Banners & Spotlight Ads</h4>
          <span style="font-size: 12px; color: var(--text-muted);">Active banners appear in carousel order</span>
        </div>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Image</th>
                <th>Title / Custom Message</th>
                <th>Badge</th>
                <th>Target Doctor</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="bannersTableBody">
              <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading marketing banners...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Banner Modal (Create / Edit) -->
      <div id="bannerModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 18px;" id="modalTitle">Configure Featured Doctor / Banner</h3>
            <button id="btnCloseModal" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted);">&times;</button>
          </div>

          <form id="bannerForm">
            <input type="hidden" id="editBannerId" value="">

            <div class="form-group">
              <label class="form-label" for="targetDoctorId">Select Registered Doctor *</label>
              <select id="targetDoctorId" class="form-control">
                <option value="">-- Select a registered doctor --</option>
              </select>
              <small style="font-size: 11.5px; color: var(--text-muted);">Selecting a doctor auto-populates their name, photo, and generates a recommended promotional message.</small>
            </div>

            <div class="form-group">
              <label class="form-label" for="bannerTitle">Custom Promotional Message / Title *</label>
              <input type="text" id="bannerTitle" class="form-control" placeholder="e.g. ডা. রহিম স্যারের সিরিয়াল নিন এখন MEDIQ-তে" required>
              <small style="font-size: 11.5px; color: var(--text-muted);">Shown prominently as the headline in the app carousel card and push alert.</small>
            </div>

            <div class="form-group">
              <label class="form-label" for="bannerSubtitle">Subtitle / Offer Description</label>
              <input type="text" id="bannerSubtitle" class="form-control" placeholder="e.g. কার্ডিওলজি বিশেষজ্ঞ • আজই চেম্বারে সিরিয়াল নিন">
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="badgeText">Badge Text</label>
                <input type="text" id="badgeText" class="form-control" placeholder="Featured / Top Doctor / বিশেষজ্ঞ" value="Featured Doctor">
              </div>
              <div class="form-group">
                <label class="form-label" for="displayOrder">Carousel Order (1 to 5)</label>
                <input type="number" id="displayOrder" class="form-control" value="1" min="1" max="99">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="imageUrl">Custom Image URL (Optional)</label>
              <input type="url" id="imageUrl" class="form-control" placeholder="Leave empty to use doctor's profile picture automatically">
            </div>

            <div class="form-group" style="background: #f0fdf4; padding: 12px; border-radius: 6px; border: 1px dashed #86efac;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; font-size: 13px; color: #166534;">
                <input type="checkbox" id="notifyPatients" value="1" checked>
                <span>📢 Send instant push notification to all patient mobile apps</span>
              </label>
              <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; margin-left: 24px;">Patients will immediately receive a notification on their phones with this custom message and doctor name.</p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
              <button type="button" id="btnCancelModal" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary" id="btnSaveBanner">Save & Publish</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi as Api, AdminApi } from '../assets/js/api.js';

  let bannersList = [];
  let doctorsList = [];

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  async function loadDoctorsDropdown() {
    try {
      const res = await Api.get('/admin/doctors');
      if (res.success && res.data) {
        doctorsList = res.data.doctors || res.data || [];
        const select = document.getElementById('targetDoctorId');
        select.innerHTML = '<option value="">-- Select a registered doctor --</option>' +
          doctorsList.map(d => `<option value="${d.id}">${d.full_name || d.name} (${d.designation || 'Doctor'})</option>`).join('');
      }
    } catch (e) {
      console.error('Failed to load doctors dropdown:', e);
    }
  }

  function renderFeaturedSlots() {
    const container = document.getElementById('featuredDoctorsSlots');
    let html = '';

    for (let slot = 1; slot <= 5; slot++) {
      const banner = bannersList.find(b => parseInt(b.display_order, 10) === slot && b.is_active == 1);
      if (banner) {
        const docAvatar = banner.image_url || 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=120';
        html += `
          <div class="slot-card active">
            <div style="width: 100%;">
              <span class="slot-num">Slot ${slot} • Featured</span>
              <img src="${docAvatar}" class="slot-avatar" alt="Avatar" onerror="this.src='https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=120'">
              <div class="slot-doctor-name" title="${banner.doctor_name || banner.title}">${banner.doctor_name || banner.title}</div>
              <div class="slot-promo-msg" title="${banner.title}">${banner.title}</div>
            </div>
            <button class="btn btn-outline btn-sm btn-edit-slot" data-slot="${slot}" data-banner-id="${banner.id}" style="width: 100%; margin-top: 8px; font-size: 11.5px;">Edit Slot ${slot}</button>
          </div>
        `;
      } else {
        html += `
          <div class="slot-card">
            <div style="width: 100%; padding-top: 10px;">
              <span class="slot-num" style="background:#f1f5f9; color:#64748b;">Slot ${slot} • Empty</span>
              <div style="width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 10px auto; color: #94a3b8; font-size: 20px;">+</div>
              <div style="font-size: 12px; color: #94a3b8; font-weight: 500;">No Doctor Assigned</div>
            </div>
            <button class="btn btn-primary btn-sm btn-edit-slot" data-slot="${slot}" data-banner-id="" style="width: 100%; margin-top: 8px; font-size: 11.5px;">+ Set Doctor</button>
          </div>
        `;
      }
    }
    container.innerHTML = html;
  }

  async function loadBanners() {
    const tbody = document.getElementById('bannersTableBody');
    try {
      const res = await Api.get('/admin/marketing/banners');
      if (!res.success) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 20px;">Failed to load banners.</td></tr>';
        return;
      }

      bannersList = res.data;
      renderFeaturedSlots();

      if (bannersList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No banners created yet. Use the slots above or click "+ Create Custom Banner".</td></tr>';
        return;
      }

      tbody.innerHTML = bannersList.map(b => `
        <tr>
          <td><strong style="color: var(--primary); font-size: 15px;">#${b.display_order}</strong></td>
          <td>
            <img src="${b.image_url || 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=120'}" 
                 style="width: 52px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
          </td>
          <td>
            <strong style="color: #1e293b;">${b.title}</strong>
            <br><small style="color: var(--text-muted);">${b.subtitle || '—'}</small>
          </td>
          <td><span class="status-pill status-paused">${b.badge_text || 'Featured'}</span></td>
          <td><strong>${b.doctor_name || '—'}</strong></td>
          <td>
            <span class="status-pill status-${b.is_active == 1 ? 'on' : 'off'}">
              ${b.is_active == 1 ? 'Active' : 'Inactive'}
            </span>
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <button class="btn btn-outline btn-sm btn-edit" data-id="${b.id}">Edit</button>
              ${b.is_active == 1 ? `
                <button class="btn btn-danger btn-sm btn-delete" data-id="${b.id}">Disable</button>
              ` : `
                <button class="btn btn-success btn-sm btn-activate" data-id="${b.id}">Enable</button>
              `}
            </div>
          </td>
        </tr>
      `).join('');

    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 20px;">Error: ${err.message}</td></tr>`;
    }
  }

  const modal = document.getElementById('bannerModal');

  function openModalForSlot(slotNum, existingBanner = null) {
    document.getElementById('modalTitle').innerText = existingBanner ? `Edit Featured Doctor (Slot ${slotNum})` : `Set Featured Doctor (Slot ${slotNum})`;
    document.getElementById('editBannerId').value = existingBanner ? existingBanner.id : '';
    document.getElementById('bannerForm').reset();
    document.getElementById('displayOrder').value = slotNum;
    document.getElementById('badgeText').value = 'Featured Doctor';
    document.getElementById('notifyPatients').checked = true;

    if (existingBanner) {
      document.getElementById('targetDoctorId').value = existingBanner.doctor_id || '';
      document.getElementById('bannerTitle').value = existingBanner.title;
      document.getElementById('bannerSubtitle').value = existingBanner.subtitle || '';
      document.getElementById('badgeText').value = existingBanner.badge_text || 'Featured Doctor';
      document.getElementById('imageUrl').value = existingBanner.image_url || '';
    }
    modal.style.display = 'flex';
  }

  document.getElementById('btnOpenBannerModal').addEventListener('click', () => {
    document.getElementById('modalTitle').innerText = 'Create Marketing Banner';
    document.getElementById('editBannerId').value = '';
    document.getElementById('bannerForm').reset();
    document.getElementById('badgeText').value = 'Featured Doctor';
    document.getElementById('displayOrder').value = '1';
    document.getElementById('notifyPatients').checked = true;
    modal.style.display = 'flex';
  });

  // Doctor select change: auto-fill title & subtitle
  document.getElementById('targetDoctorId').addEventListener('change', (e) => {
    const did = parseInt(e.target.value, 10);
    const doc = doctorsList.find(d => d.id === did);
    if (doc) {
      const docName = doc.full_name || doc.name;
      const titleInput = document.getElementById('bannerTitle');
      if (!titleInput.value || titleInput.value.includes('সিরিয়াল নিন') || titleInput.value.includes('Dr.')) {
        titleInput.value = `ডা. ${docName} স্যারের সিরিয়াল নিন এখন MEDIQ-তে`;
      }
      const subInput = document.getElementById('bannerSubtitle');
      if (!subInput.value || subInput.value.includes('বিশেষজ্ঞ')) {
        subInput.value = `${doc.designation || 'বিশেষজ্ঞ চিকিৎসক'} • আজই সিরিয়াল বুক করুন`;
      }
      const imgInput = document.getElementById('imageUrl');
      if (doc.profile_photo && !imgInput.value) {
        imgInput.value = doc.profile_photo.startsWith('http') ? doc.profile_photo : `/${doc.profile_photo}`;
      }
    }
  });

  // Click on slot edit button
  document.getElementById('featuredDoctorsSlots').addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-edit-slot');
    if (!btn) return;
    const slot = parseInt(btn.dataset.slot, 10);
    const bId = parseInt(btn.dataset.bannerId, 10);
    const existing = bId ? bannersList.find(b => b.id === bId) : null;
    openModalForSlot(slot, existing);
  });

  document.getElementById('btnCloseModal').addEventListener('click', () => { modal.style.display = 'none'; });
  document.getElementById('btnCancelModal').addEventListener('click', () => { modal.style.display = 'none'; });

  document.getElementById('bannerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editBannerId').value;
    const payload = {
      title: document.getElementById('bannerTitle').value,
      subtitle: document.getElementById('bannerSubtitle').value,
      badge_text: document.getElementById('badgeText').value,
      banner_type: 'DOCTOR',
      doctor_id: document.getElementById('targetDoctorId').value || null,
      image_url: document.getElementById('imageUrl').value,
      display_order: parseInt(document.getElementById('displayOrder').value, 10),
      notify_patients: document.getElementById('notifyPatients').checked ? 1 : 0
    };

    try {
      if (id) {
        await Api.put(`/admin/marketing/banners/${id}`, payload);
        showNotice('Featured Doctor banner updated successfully.');
      } else {
        await Api.post('/admin/marketing/banners', payload);
        showNotice('Featured Doctor published and push alert broadcasted to patients.');
      }
      modal.style.display = 'none';
      loadBanners();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('bannersTableBody').addEventListener('click', async (e) => {
    const editBtn = e.target.closest('.btn-edit');
    if (editBtn) {
      const id = parseInt(editBtn.dataset.id, 10);
      const b = bannersList.find(item => item.id === id);
      if (b) {
        document.getElementById('modalTitle').innerText = 'Edit Banner';
        document.getElementById('editBannerId').value = b.id;
        document.getElementById('bannerTitle').value = b.title;
        document.getElementById('bannerSubtitle').value = b.subtitle || '';
        document.getElementById('badgeText').value = b.badge_text || 'Featured Doctor';
        document.getElementById('targetDoctorId').value = b.doctor_id || '';
        document.getElementById('imageUrl').value = b.image_url || '';
        document.getElementById('displayOrder').value = b.display_order || 1;
        document.getElementById('notifyPatients').checked = false;
        modal.style.display = 'flex';
      }
      return;
    }

    const delBtn = e.target.closest('.btn-delete');
    if (delBtn) {
      const id = delBtn.dataset.id;
      if (confirm('Are you sure you want to disable this featured banner?')) {
        try {
          await Api.delete(`/admin/marketing/banners/${id}`);
          showNotice('Banner deactivated.');
          loadBanners();
        } catch (err) {
          showNotice(err.message, 'danger');
        }
      }
      return;
    }

    const actBtn = e.target.closest('.btn-activate');
    if (actBtn) {
      const id = actBtn.dataset.id;
      try {
        await Api.put(`/admin/marketing/banners/${id}`, { is_active: 1 });
        showNotice('Banner activated.');
        loadBanners();
      } catch (err) {
        showNotice(err.message, 'danger');
      }
      return;
    }
  });

  await loadDoctorsDropdown();
  loadBanners();
</script>

</body>
</html>