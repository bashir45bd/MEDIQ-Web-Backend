<?php
$activePage = 'categories';
$pageTitle = 'Medical Specialties & Categories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Specialties & Categories — MEDIQ Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
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
          <h3 style="font-size: 20px; font-weight: 700;">Department & Specialties Management</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Manage doctor specialties, category icons, and listings visible across the mobile app and web directory.</p>
        </div>
        <button id="btnOpenAddModal" class="btn btn-primary">+ Add New Specialty</button>
      </div>

      <!-- Categories Table Card -->
      <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 30px;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Icon</th>
                <th>Specialty / Category Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="specialtyTableBody">
              <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading specialties...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Specialty Modal (Add/Edit) -->
      <div id="specialtyModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 520px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 18px;" id="modalTitle">Add New Specialty</h3>
            <button id="btnCloseModal" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted);">&times;</button>
          </div>
          <form id="specialtyForm">
            <input type="hidden" id="editSpecialtyId" value="">
            
            <div class="form-group">
              <label class="form-label" for="specName">Specialty Name *</label>
              <input type="text" id="specName" class="form-control" placeholder="e.g. Cardiology (হৃদরোগ বিশেষজ্ঞ)" required>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="specSlug">Slug</label>
                <input type="text" id="specSlug" class="form-control" placeholder="cardiology">
              </div>
              <div class="form-group">
                <label class="form-label" for="specIcon">Icon Identifier</label>
                <input type="text" id="specIcon" class="form-control" placeholder="heart / stethoscope" value="stethoscope">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="specDesc">Description</label>
              <textarea id="specDesc" class="form-control" rows="2" placeholder="Brief details about this clinical specialty..."></textarea>
            </div>

            <div class="form-group">
              <label class="form-label" for="specStatus">Active Status</label>
              <select id="specStatus" class="form-control">
                <option value="1">Active (Visible in Patient App)</option>
                <option value="0">Inactive / Hidden</option>
              </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
              <button type="button" id="btnCancelModal" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary" id="btnSaveSpecialty">Save Specialty</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi as Api, AdminApi } from '../assets/js/api.js';

  let specialtiesList = [];

  function showNotice(msg, type = 'success') {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    box.style.color = type === 'success' ? '#15803d' : '#dc2626';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  async function loadSpecialties() {
    const tbody = document.getElementById('specialtyTableBody');
    try {
      const res = await Api.get('/admin/specialties');
      if (!res.success) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 20px;">Failed to load specialties.</td></tr>';
        return;
      }

      specialtiesList = res.data;
      if (specialtiesList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No specialties found.</td></tr>';
        return;
      }

      tbody.innerHTML = specialtiesList.map(s => `
        <tr>
          <td><span style="color: var(--text-muted); font-size: 13px;">#${s.id}</span></td>
          <td>
            <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700;">
              🏥
            </div>
          </td>
          <td><strong>${s.name}</strong></td>
          <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px;">${s.slug || '—'}</code></td>
          <td style="color: var(--text-muted); font-size: 13px; max-width: 250px;">${s.description || '—'}</td>
          <td>
            <span class="status-pill status-${s.is_active == 1 ? 'on' : 'off'}">
              ${s.is_active == 1 ? 'Active' : 'Inactive'}
            </span>
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <button class="btn btn-outline btn-sm btn-edit" data-id="${s.id}">Edit</button>
              ${s.is_active == 1 ? `
                <button class="btn btn-danger btn-sm btn-deactivate" data-id="${s.id}">Disable</button>
              ` : `
                <button class="btn btn-success btn-sm btn-activate" data-id="${s.id}">Activate</button>
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

  // Modal open / close
  const modal = document.getElementById('specialtyModal');
  document.getElementById('btnOpenAddModal').addEventListener('click', () => {
    document.getElementById('modalTitle').innerText = 'Add New Specialty';
    document.getElementById('editSpecialtyId').value = '';
    document.getElementById('specialtyForm').reset();
    document.getElementById('specStatus').value = '1';
    modal.style.display = 'flex';
  });

  document.getElementById('btnCloseModal').addEventListener('click', () => { modal.style.display = 'none'; });
  document.getElementById('btnCancelModal').addEventListener('click', () => { modal.style.display = 'none'; });

  // Form submit (Add or Edit)
  document.getElementById('specialtyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editSpecialtyId').value;
    const payload = {
      name: document.getElementById('specName').value,
      slug: document.getElementById('specSlug').value,
      icon: document.getElementById('specIcon').value,
      description: document.getElementById('specDesc').value,
      is_active: parseInt(document.getElementById('specStatus').value, 10)
    };

    try {
      if (id) {
        await Api.put(`/admin/specialties/${id}`, payload);
        showNotice('Specialty updated successfully.');
      } else {
        await Api.post('/admin/specialties', payload);
        showNotice('Specialty created successfully.');
      }
      modal.style.display = 'none';
      loadSpecialties();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  // Table action delegates
  document.getElementById('specialtyTableBody').addEventListener('click', async (e) => {
    const editBtn = e.target.closest('.btn-edit');
    if (editBtn) {
      const id = parseInt(editBtn.dataset.id, 10);
      const spec = specialtiesList.find(s => s.id === id);
      if (spec) {
        document.getElementById('modalTitle').innerText = 'Edit Specialty';
        document.getElementById('editSpecialtyId').value = spec.id;
        document.getElementById('specName').value = spec.name;
        document.getElementById('specSlug').value = spec.slug || '';
        document.getElementById('specIcon').value = spec.icon || 'stethoscope';
        document.getElementById('specDesc').value = spec.description || '';
        document.getElementById('specStatus').value = spec.is_active;
        modal.style.display = 'flex';
      }
      return;
    }

    const deactBtn = e.target.closest('.btn-deactivate');
    if (deactBtn) {
      const id = deactBtn.dataset.id;
      if (confirm('Are you sure you want to deactivate this specialty?')) {
        try {
          await Api.delete(`/admin/specialties/${id}`);
          showNotice('Specialty deactivated.');
          loadSpecialties();
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
        await Api.put(`/admin/specialties/${id}`, { is_active: 1 });
        showNotice('Specialty activated.');
        loadSpecialties();
      } catch (err) {
        showNotice(err.message, 'danger');
      }
      return;
    }
  });

  loadSpecialties();
</script>

</body>
</html>
