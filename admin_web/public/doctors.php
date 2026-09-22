<?php
$activePage = 'doctors';
$pageTitle = 'Doctor Verification & Credentialing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Verification — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <!-- Filter Controls & Add Doctor -->
      <div class="card" style="margin-bottom: 24px; padding: 16px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="btn btn-outline btn-sm filter-btn active" data-status="">All Doctors</button>
            <button class="btn btn-outline btn-sm filter-btn" data-status="PENDING">Pending Review</button>
            <button class="btn btn-outline btn-sm filter-btn" data-status="VERIFIED">Verified</button>
            <button class="btn btn-outline btn-sm filter-btn" data-status="SUSPENDED">Suspended</button>
          </div>
          <div style="display: flex; align-items: center; gap: 14px;">
            <button id="btnOpenAddDocModal" class="btn btn-primary btn-sm" style="padding: 8px 18px; font-weight: 700;">+ Register New Doctor</button>
          </div>
        </div>
      </div>

      <!-- Doctors Table -->
      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Doctor Name</th>
                <th>BMDC Reg No</th>
                <th>Specialties & Degrees</th>
                <th>Fee (BDT)</th>
                <th>Verification Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="doctorsTableBody">
              <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading doctors...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reject Modal -->
      <div id="rejectModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 450px;">
          <h3 style="font-size: 18px; margin-bottom: 12px; color: #dc2626;">Reject Doctor Credentials</h3>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 16px;">
            Provide the specific BMDC rejection reason to be sent to the applicant.
          </p>
          <div class="form-group">
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="e.g. BMDC certificate photocopy is blurry; registration number not found in council database."></textarea>
          </div>
          <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button id="btnCancelReject" class="btn btn-outline">Cancel</button>
            <button id="btnConfirmReject" class="btn btn-danger">Confirm Rejection</button>
          </div>
        </div>
      </div>

      <!-- Add Doctor Modal -->
      <div id="addDoctorModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px; overflow-y: auto;">
        <div class="card" style="width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 19px; font-weight: 800;">Register New Doctor Account</h3>
            <button id="btnCloseAddDocModal" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
          </div>

          <form id="newDoctorForm">
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" id="newDocName" class="form-control" placeholder="Dr. Firstname Lastname" required>
              </div>
              <div class="form-group">
                <label class="form-label">Contact Mobile</label>
                <input type="text" id="newDocPhone" class="form-control" placeholder="+88017..." required>
              </div>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">Account Password</label>
                <input type="password" id="newDocPassword" class="form-control" placeholder="Min 6 characters" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email (Optional)</label>
                <input type="email" id="newDocEmail" class="form-control" placeholder="doctor@example.com">
              </div>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">BMDC Reg Number</label>
                <input type="text" id="newDocBmdc" class="form-control" placeholder="e.g. BMDC-A12345" required>
              </div>
              <div class="form-group">
                <label class="form-label">Experience (Years)</label>
                <input type="number" id="newDocExp" class="form-control" value="5" min="0" max="60">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Designation & Degrees</label>
              <input type="text" id="newDocDesig" class="form-control" placeholder="MBBS, FCPS, MD • Senior Consultant" required>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label">New Patient Fee (BDT)</label>
                <input type="number" step="10" id="newDocFee" class="form-control" value="1000" required>
              </div>
              <div class="form-group">
                <label class="form-label">Follow-up Fee (BDT)</label>
                <input type="number" step="10" id="newDocFuFee" class="form-control" value="600" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Initial Status</label>
              <select id="newDocStatus" class="form-control">
                <option value="VERIFIED">VERIFIED (Immediately Active & Discoverable)</option>
                <option value="PENDING">PENDING (Require Verification Later)</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Specialties</label>
              <div id="newDocSpecsContainer" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px;"></div>
            </div>

            <div class="form-group">
              <label class="form-label">Bio & Clinical Notes (Optional)</label>
              <textarea id="newDocBio" class="form-control" rows="2" placeholder="Brief clinical background..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
              <button type="button" id="btnCancelAddDoc" class="btn btn-outline">Cancel</button>
              <button type="submit" id="btnSubmitAddDoc" class="btn btn-primary" style="padding: 10px 24px;">Register Doctor</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi } from '../assets/js/api.js';

  let currentFilter = '';
  let selectedDocId = null;

  async function loadDoctors() {
    try {
      const endpoint = currentFilter ? `/admin/doctors?status=${currentFilter}` : '/admin/doctors';
      const res = await AdminApi.get(endpoint);
      if (!res.success) return;

      const docs = res.data;
      const tbody = document.getElementById('doctorsTableBody');
      if (docs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No doctor applications found for this filter.</td></tr>';
        return;
      }

      tbody.innerHTML = docs.map(d => `
        <tr>
          <td>
            <strong>${d.doctor_name}</strong>
            <div style="font-size: 12px; color: var(--text-muted);">${d.phone} • ${d.email || ''}</div>
          </td>
          <td><code>${d.bmdc_number}</code></td>
          <td>
            <div>${d.designation}</div>
            <div style="font-size: 12px; color: var(--text-muted);">${d.specialties || 'General'}</div>
          </td>
          <td>৳${parseFloat(d.consultation_fee).toFixed(0)}</td>
          <td>
            <span class="status-pill status-${d.verification_status.toLowerCase()}">${d.verification_status}</span>
          </td>
          <td>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
              <a href="doctor_manage.php?id=${d.id}" class="btn btn-outline btn-sm" style="background: #e0e7ff; color: #4338ca; border-color: #c7d2fe;">Manage ⚙️</a>
              ${d.verification_status !== 'VERIFIED' ? `
                <button class="btn btn-success btn-sm btn-verify" data-id="${d.id}">Approve</button>
              ` : `
                <button class="btn btn-warning btn-sm btn-suspend" data-id="${d.id}">Suspend</button>
              `}
              ${d.verification_status === 'PENDING' ? `
                <button class="btn btn-danger btn-sm btn-reject" data-id="${d.id}">Reject</button>
              ` : ''}
            </div>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  // Filter Buttons
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      e.target.classList.add('active');
      currentFilter = e.target.dataset.status;
      loadDoctors();
    });
  });

  // Table Action Delegation
  document.getElementById('doctorsTableBody').addEventListener('click', async (e) => {
    const target = e.target;
    const docId = target.dataset.id;
    if (!docId) return;

    if (target.classList.contains('btn-verify')) {
      if (confirm('Verify BMDC credentials and publish this doctor publicly on MEDIQ?')) {
        try {
          await AdminApi.post(`/admin/doctors/${docId}/verify`, {});
          showNotice('Doctor BMDC verified and profile published!');
          loadDoctors();
        } catch (err) {
          showNotice(err.message, 'danger');
        }
      }
    } else if (target.classList.contains('btn-suspend')) {
      if (confirm('Suspend this doctor account? Booking will be blocked immediately.')) {
        try {
          await AdminApi.post(`/admin/doctors/${docId}/suspend`, {});
          showNotice('Doctor account suspended.');
          loadDoctors();
        } catch (err) {
          showNotice(err.message, 'danger');
        }
      }
    } else if (target.classList.contains('btn-reject')) {
      selectedDocId = docId;
      document.getElementById('rejectModal').style.display = 'flex';
    }
  });

  // Reject Modal
  document.getElementById('btnCancelReject').addEventListener('click', () => {
    document.getElementById('rejectModal').style.display = 'none';
    selectedDocId = null;
  });

  document.getElementById('btnConfirmReject').addEventListener('click', async () => {
    if (!selectedDocId) return;
    const reason = document.getElementById('rejectReason').value.trim();
    try {
      await AdminApi.post(`/admin/doctors/${selectedDocId}/reject`, { reason });
      document.getElementById('rejectModal').style.display = 'none';
      showNotice('Doctor application rejected.');
      loadDoctors();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  // Add Doctor Modal Handlers
  const addDocModal = document.getElementById('addDoctorModal');
  const btnOpenAddDoc = document.getElementById('btnOpenAddDocModal');
  const btnCloseAddDoc = document.getElementById('btnCloseAddDocModal');
  const btnCancelAddDoc = document.getElementById('btnCancelAddDoc');

  async function loadSpecialtiesForModal() {
    try {
      const res = await AdminApi.get('/specialties');
      if (res.success) {
        const container = document.getElementById('newDocSpecsContainer');
        container.innerHTML = res.data.map(s => `
          <label style="display: inline-flex; align-items: center; gap: 5px; font-size: 13px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
            <input type="checkbox" name="modal_specialty_ids" value="${s.id}">
            <span>${s.name}</span>
          </label>
        `).join('');
      }
    } catch (err) {
      console.warn('Specialties load note:', err);
    }
  }

  btnOpenAddDoc.addEventListener('click', () => {
    addDocModal.style.display = 'flex';
    loadSpecialtiesForModal();
  });

  const closeAddDocModal = () => { addDocModal.style.display = 'none'; };
  btnCloseAddDoc.addEventListener('click', closeAddDocModal);
  btnCancelAddDoc.addEventListener('click', closeAddDocModal);

  document.getElementById('newDoctorForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitAddDoc');
    btn.disabled = true;
    btn.innerText = 'Registering...';

    const selectedSpecs = Array.from(document.querySelectorAll('input[name="modal_specialty_ids"]:checked'))
      .map(cb => parseInt(cb.value, 10));

    const payload = {
      full_name: document.getElementById('newDocName').value.trim(),
      phone: document.getElementById('newDocPhone').value.trim(),
      password: document.getElementById('newDocPassword').value,
      email: document.getElementById('newDocEmail').value.trim(),
      bmdc_number: document.getElementById('newDocBmdc').value.trim(),
      experience_years: parseInt(document.getElementById('newDocExp').value, 10),
      designation: document.getElementById('newDocDesig').value.trim(),
      consultation_fee: parseFloat(document.getElementById('newDocFee').value),
      follow_up_fee: parseFloat(document.getElementById('newDocFuFee').value),
      verification_status: document.getElementById('newDocStatus').value,
      bio: document.getElementById('newDocBio').value.trim(),
      specialty_ids: selectedSpecs
    };

    try {
      const res = await AdminApi.post('/admin/doctors', payload);
      if (res.success) {
        closeAddDocModal();
        document.getElementById('newDoctorForm').reset();
        showNotice(`Doctor ${payload.full_name} successfully registered!`);
        loadDoctors();
      }
    } catch (err) {
      showNotice(err.message, 'danger');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Register Doctor';
    }
  });

  loadDoctors();
</script>

</body>
</html>
