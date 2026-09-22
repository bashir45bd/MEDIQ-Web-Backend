<?php
$activePage = 'profile';
$pageTitle = 'Doctor Profile & Clinical Credentials';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — MEDIQ Doctor</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 500;"></div>

      <div class="card" style="max-width: 800px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 18px;">
          <div>
            <h3 style="font-size: 20px; font-weight: 700;">Professional Profile</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">
              Manage your personal details, consultation charges, and clinical biography.
            </p>
          </div>
          <div style="text-align: right;">
            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">BMDC Registration</span>
            <div id="badgeBmdc" style="font-size: 16px; font-weight: 800; color: var(--primary); font-family: monospace;">BMDC-Loading</div>
            <div id="badgeStatus" class="status-pill status-verified" style="margin-top: 4px;">VERIFIED</div>
          </div>
        </div>

        <!-- Doctor Profile Photo Upload Card -->
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px; padding: 18px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--border);">
          <div style="position: relative; width: 88px; height: 88px; border-radius: 50%; overflow: hidden; background: #e0f2fe; border: 3px solid var(--primary); flex-shrink: 0; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <img id="avatarPreview" src="" alt="Doctor Photo" style="width: 100%; height: 100%; object-fit: cover; display: none;">
            <div id="avatarPlaceholder" style="font-size: 28px; font-weight: 800; color: var(--primary);">DR</div>
          </div>
          <div style="flex: 1;">
            <label style="font-size: 15px; font-weight: 700; display: block; margin-bottom: 4px;">Doctor Profile Photo (প্রোফাইল ছবি)</label>
            <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 12px;">
              Upload a clear professional photo (JPG, PNG or WEBP, max 5MB). Patients will see this photo on the mobile app and portal.
            </p>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
              <button type="button" id="btnChoosePhoto" class="btn btn-primary btn-sm" style="padding: 8px 18px; font-size: 13px; font-weight: 600;">
                📷 Upload New Photo
              </button>
              <button type="button" id="btnRemovePhoto" class="btn btn-outline btn-sm" style="padding: 8px 14px; font-size: 13px; display: none; color: #ef4444; border-color: #ef4444;">
                Remove Photo
              </button>
              <span id="uploadStatusText" style="font-size: 13px; color: var(--text-muted); font-weight: 600;"></span>
            </div>
          </div>
        </div>

        <form id="profileForm">
          <div class="grid grid-cols-2">
            <div class="form-group">
              <label class="form-label" for="fullName">Full Name</label>
              <input type="text" id="fullName" class="form-control" required placeholder="Dr. Firstname Lastname">
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">Contact Mobile</label>
              <input type="text" id="phone" class="form-control" required placeholder="+88017...">
            </div>
          </div>

          <div class="grid grid-cols-2">
            <div class="form-group">
              <label class="form-label" for="email">Email Address</label>
              <input type="email" id="email" class="form-control" placeholder="doctor@example.com">
            </div>
            <div class="form-group">
              <label class="form-label" for="experienceYears">Experience (Years)</label>
              <input type="number" id="experienceYears" class="form-control" min="0" max="60" value="5">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="designation">Medical Designation & Degrees</label>
            <input type="text" id="designation" class="form-control" required placeholder="MBBS, FCPS, MD (Cardiology) • Senior Consultant">
          </div>

          <div class="grid grid-cols-2">
            <div class="form-group">
              <label class="form-label" for="consultationFee">New Patient Fee (BDT)</label>
              <input type="number" step="10" id="consultationFee" class="form-control" required value="1000">
            </div>
            <div class="form-group">
              <label class="form-label" for="followUpFee">Follow-up Fee (BDT)</label>
              <input type="number" step="10" id="followUpFee" class="form-control" required value="600">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Medical Specialties</label>
            <div id="specialtiesContainer" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
              <span style="color: var(--text-muted); font-size: 13px;">Loading specialties...</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="bio">Professional Bio & Chamber Instructions</label>
            <textarea id="bio" class="form-control" rows="4" placeholder="Brief summary of clinical background, areas of expertise, and visiting guidelines..."></textarea>
          </div>

          <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="submit" id="btnSaveProfile" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px;">
              Save Profile Changes
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { Api } from '../assets/js/api.js';

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  async function loadProfile() {
    try {
      const res = await Api.get('/doctor/profile');
      if (!res.success) return;

      const d = res.data;
      document.getElementById('fullName').value = d.full_name || '';
      document.getElementById('phone').value = d.phone || '';
      document.getElementById('email').value = d.email || '';
      document.getElementById('designation').value = d.designation || '';
      document.getElementById('experienceYears').value = d.experience_years || 0;
      document.getElementById('consultationFee').value = parseFloat(d.consultation_fee || 0).toFixed(0);
      document.getElementById('followUpFee').value = parseFloat(d.follow_up_fee || 0).toFixed(0);
      document.getElementById('bio').value = d.bio || '';

      document.getElementById('badgeBmdc').innerText = d.bmdc_number || 'N/A';
      const statusPill = document.getElementById('badgeStatus');
      statusPill.innerText = d.verification_status;
      statusPill.className = 'status-pill status-' + d.verification_status.toLowerCase();

      if (d.profile_photo) {
        const imgUrl = d.profile_photo.startsWith('http') ? d.profile_photo : ('/mediq/backend/public/' + d.profile_photo);
        document.getElementById('avatarPreview').src = imgUrl;
        document.getElementById('avatarPreview').style.display = 'block';
        document.getElementById('avatarPlaceholder').style.display = 'none';
        document.getElementById('btnRemovePhoto').style.display = 'inline-block';

        const headerAvatar = document.querySelector('header.top-bar div[style*="border-radius: 50%"]');
        if (headerAvatar) {
          headerAvatar.innerHTML = `<img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        }
      }

      // Render specialties checkboxes
      const container = document.getElementById('specialtiesContainer');
      const assignedIds = (d.specialty_ids || []).map(Number);
      const allSpecs = d.all_specialties || [];

      if (allSpecs.length === 0) {
        container.innerHTML = '<span style="color: var(--text-muted);">No specialties found</span>';
      } else {
        container.innerHTML = allSpecs.map(s => `
          <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 14px; background: #f8fafc; padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;">
            <input type="checkbox" name="specialty_ids" value="${s.id}" ${assignedIds.includes(Number(s.id)) ? 'checked' : ''}>
            <span>${s.name}</span>
          </label>
        `).join('');
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  }

  // Photo Upload Handlers
  document.getElementById('btnChoosePhoto').addEventListener('click', () => {
    document.getElementById('avatarFileInput').click();
  });

  document.getElementById('avatarFileInput').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      showNotice('File size exceeds 5MB limit.', 'danger');
      return;
    }

    const statusSpan = document.getElementById('uploadStatusText');
    statusSpan.innerText = 'Uploading photo...';

    const reader = new FileReader();
    reader.onload = async (event) => {
      const base64 = event.target.result;
      document.getElementById('avatarPreview').src = base64;
      document.getElementById('avatarPreview').style.display = 'block';
      document.getElementById('avatarPlaceholder').style.display = 'none';

      try {
        const res = await Api.post('/doctor/avatar', { avatar: base64 });
        if (res.success) {
          showNotice('Profile photo updated successfully!');
          statusSpan.innerText = '✅ Saved';
          document.getElementById('btnRemovePhoto').style.display = 'inline-block';
          const headerAvatar = document.querySelector('header.top-bar div[style*="border-radius: 50%"]');
          if (headerAvatar) {
            headerAvatar.innerHTML = `<img src="${base64}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
          }
          setTimeout(() => { statusSpan.innerText = ''; }, 3000);
        }
      } catch (err) {
        showNotice('Failed to upload photo: ' + err.message, 'danger');
        statusSpan.innerText = '❌ Failed';
      }
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('btnRemovePhoto').addEventListener('click', async () => {
    if (confirm('Are you sure you want to remove your profile photo?')) {
      try {
        await Api.put('/doctor/profile', {
          full_name: document.getElementById('fullName').value.trim(),
          phone: document.getElementById('phone').value.trim(),
          designation: document.getElementById('designation').value.trim(),
          consultation_fee: parseFloat(document.getElementById('consultationFee').value),
          profile_photo: ''
        });
        document.getElementById('avatarPreview').style.display = 'none';
        document.getElementById('avatarPlaceholder').style.display = 'block';
        document.getElementById('btnRemovePhoto').style.display = 'none';
        showNotice('Profile photo removed.');
      } catch (err) {
        showNotice(err.message, 'danger');
      }
    }
  });

  document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    const selectedSpecs = Array.from(document.querySelectorAll('input[name="specialty_ids"]:checked'))
      .map(cb => parseInt(cb.value, 10));

    const payload = {
      full_name: document.getElementById('fullName').value.trim(),
      phone: document.getElementById('phone').value.trim(),
      email: document.getElementById('email').value.trim(),
      designation: document.getElementById('designation').value.trim(),
      experience_years: parseInt(document.getElementById('experienceYears').value, 10),
      consultation_fee: parseFloat(document.getElementById('consultationFee').value),
      follow_up_fee: parseFloat(document.getElementById('followUpFee').value),
      bio: document.getElementById('bio').value.trim(),
      specialty_ids: selectedSpecs
    };

    try {
      const res = await Api.put('/doctor/profile', payload);
      if (res.success) {
        showNotice('Profile changes saved successfully!');
        // Update header name
        document.getElementById('headerDocName').innerText = payload.full_name;
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Save Profile Changes';
    }
  });

  document.getElementById('btnLogout').addEventListener('click', async () => {
    await Api.post('/auth/logout', {});
    Api.clearToken();
    window.location.href = 'login.php';
  });

  loadProfile();
</script>

</body>
</html>
