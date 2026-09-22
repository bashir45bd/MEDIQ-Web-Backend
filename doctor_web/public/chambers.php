<?php
$activePage = 'chambers';
$pageTitle = 'Chambers & Weekly Schedules';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chambers & Schedules — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
      z-index: 999;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow-y: auto;
    }
    .modal-content {
      background: #ffffff;
      border-radius: 14px;
      width: 100%;
      max-width: 540px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
      border: 1px solid #e2e8f0;
      padding: 24px;
    }
    .schedule-badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      background: #e0e7ff;
      color: #3730a3;
    }
  </style>
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"></div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 22px; font-weight: 800; color: #0f172a;">Chamber Practice Locations</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">Configure all hospital, clinic, or private chambers where you consult patients.</p>
        </div>
        <button id="btnOpenAddChamberModal" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
          <span>+ Add New Chamber</span>
        </button>
      </div>

      <!-- Chambers Grid -->
      <div id="chambersGrid" class="grid grid-cols-2" style="margin-bottom: 36px;">
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 30px;">Loading chambers...</div>
      </div>

      <!-- Weekly Schedules Table -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 style="font-size: 20px; font-weight: 800; color: #0f172a;">Weekly Consultation Hours & Patient Intake</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">Set your days, consultation hours, and maximum patient capacity (e.g. 100 serials).</p>
        </div>
        <button id="btnOpenAddScheduleModal" class="btn btn-primary" style="background: #059669; border-color: #059669; display: flex; align-items: center; gap: 6px;">
          <span>+ Configure Weekly Hours</span>
        </button>
      </div>

      <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Chamber Name</th>
                <th>Day of Week</th>
                <th>Consultation Hours</th>
                <th>Avg Duration</th>
                <th>Max Capacity</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="schedulesTableBody">
              <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading schedules...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- =================================================================== -->
      <!-- MODAL: ADD / EDIT CHAMBER -->
      <!-- =================================================================== -->
      <div id="chamberModal" class="modal-backdrop">
        <div class="modal-content">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h3 id="chamberModalTitle" style="font-size: 18px; font-weight: 800;">Add Practice Chamber</h3>
            <button type="button" id="btnCloseChamberModal" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">&times;</button>
          </div>
          <form id="chamberForm">
            <input type="hidden" id="editChamberId" value="">
            <div class="form-group">
              <label class="form-label" for="chName">Chamber / Hospital / Clinic Name *</label>
              <input type="text" id="chName" class="form-control" placeholder="e.g. Popular Diagnostic Centre, Dhanmondi" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="chAddress">Full Street Address *</label>
              <input type="text" id="chAddress" class="form-control" placeholder="e.g. House 16, Road 2, Dhanmondi, Dhaka" required>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="chCity">City *</label>
                <select id="chCity" class="form-control" required></select>
              </div>
              <div class="form-group">
                <label class="form-label" for="chArea">Area / Thana *</label>
                <select id="chArea" class="form-control" required>
                  <option value="">Select city first</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="chPhone">Chamber Contact Phone *</label>
                <input type="text" id="chPhone" class="form-control" placeholder="+88017XXXXXXXX" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="chRoom">Room / Floor Number</label>
                <input type="text" id="chRoom" class="form-control" placeholder="Room 402, 4th Floor">
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="chPrefix">Token Prefix</label>
                <input type="text" id="chPrefix" class="form-control" value="A" maxlength="3" placeholder="A, B, or C">
              </div>
              <div class="form-group">
                <label class="form-label" for="chIsActive">Chamber Status</label>
                <select id="chIsActive" class="form-control">
                  <option value="1">Active & Operating</option>
                  <option value="0">Deactivated / Inactive</option>
                </select>
              </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
              <button type="button" class="btn btn-outline" id="btnCancelChamber">Cancel</button>
              <button type="submit" id="btnSaveChamber" class="btn btn-primary">Save Chamber</button>
            </div>
          </form>
        </div>
      </div>

      <!-- =================================================================== -->
      <!-- MODAL: ADD / EDIT SCHEDULE -->
      <!-- =================================================================== -->
      <div id="scheduleModal" class="modal-backdrop">
        <div class="modal-content">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h3 id="scheduleModalTitle" style="font-size: 18px; font-weight: 800;">Configure Consultation Hours</h3>
            <button type="button" id="btnCloseScheduleModal" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">&times;</button>
          </div>
          <form id="scheduleForm">
            <input type="hidden" id="editScheduleId" value="">
            <div class="form-group">
              <label class="form-label" for="schChamberSelect">Select Chamber *</label>
              <select id="schChamberSelect" class="form-control" required></select>
            </div>
            <div class="form-group">
              <label class="form-label" for="schDayOfWeek">Day of Week *</label>
              <select id="schDayOfWeek" class="form-control" required>
                <option value="0">Sunday (রবিবার)</option>
                <option value="1">Monday (সোমবার)</option>
                <option value="2">Tuesday (মঙ্গলবার)</option>
                <option value="3">Wednesday (বুধবার)</option>
                <option value="4">Thursday (বৃহস্পতিবার)</option>
                <option value="5">Friday (শুক্রবার)</option>
                <option value="6">Saturday (শনিবার)</option>
              </select>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="schStartTime">Start Time *</label>
                <input type="time" id="schStartTime" class="form-control" value="16:00" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="schEndTime">End Time *</label>
                <input type="time" id="schEndTime" class="form-control" value="20:00" required>
              </div>
            </div>
            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="schCapacity">Max Patient Capacity (Serials) *</label>
                <input type="number" id="schCapacity" class="form-control" min="1" max="300" value="100" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="schSlotDuration">Avg Consultation Time</label>
                <select id="schSlotDuration" class="form-control">
                  <option value="5">5 minutes / patient</option>
                  <option value="10" selected>10 minutes / patient</option>
                  <option value="15">15 minutes / patient</option>
                  <option value="20">20 minutes / patient</option>
                </select>
              </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
              <button type="button" class="btn btn-outline" id="btnCancelSchedule">Cancel</button>
              <button type="submit" id="btnSaveSchedule" class="btn btn-primary" style="background: #059669; border-color: #059669;">Save Weekly Hours</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { Api } from '../assets/js/api.js';

  const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  let loadedChambers = [];
  let loadedSchedules = [];

  function showNotice(msg, isError = false) {
    const box = document.getElementById('noticeBox');
    box.style.display = 'block';
    box.style.background = isError ? '#fef2f2' : '#ecfdf5';
    box.style.color = isError ? '#b91c1c' : '#065f46';
    box.style.border = isError ? '1px solid #fca5a5' : '1px solid #6ee7b7';
    box.innerText = msg;
    setTimeout(() => { box.style.display = 'none'; }, 4000);
  }

  function formatTime(timeStr) {
    if (!timeStr) return '--';
    const parts = timeStr.split(':');
    let hour = parseInt(parts[0], 10);
    const min = parts[1];
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    hour = hour ? hour : 12;
    return `${hour}:${min} ${ampm}`;
  }

  let citiesList = [];

  async function loadLocations() {
    try {
      const res = await Api.get('/locations/cities');
      if (res.success && res.data.length > 0) {
        citiesList = res.data;
        const cSelect = document.getElementById('chCity');
        cSelect.innerHTML = citiesList.map(c => `<option value="${c.id}">${c.name} (${c.division})</option>`).join('');
        loadAreasForCity(citiesList[0].id);
      }
    } catch (e) {
      console.error('Failed to load cities', e);
    }
  }

  async function loadAreasForCity(cityId, selectedAreaId = null) {
    try {
      const res = await Api.get(`/locations/cities/${cityId}/areas`);
      const aSelect = document.getElementById('chArea');
      if (res.success && res.data.length > 0) {
        aSelect.innerHTML = res.data.map(a => `<option value="${a.id}">${a.name} (Postal: ${a.postal_code || '--'})</option>`).join('');
        if (selectedAreaId) {
          aSelect.value = selectedAreaId;
        }
      } else {
        aSelect.innerHTML = '<option value="">No areas available</option>';
      }
    } catch (e) {
      console.error('Failed to load areas', e);
    }
  }

  document.getElementById('chCity').addEventListener('change', (e) => {
    loadAreasForCity(e.target.value);
  });

  window.editChamber = function(id) {
    const c = loadedChambers.find(item => item.id == id);
    if (!c) return;
    document.getElementById('editChamberId').value = c.id;
    document.getElementById('chamberModalTitle').innerText = 'Edit Practice Chamber';
    document.getElementById('chName').value = c.name;
    document.getElementById('chAddress').value = c.address;
    document.getElementById('chCity').value = c.city_id || (citiesList[0]?.id || 1);
    loadAreasForCity(document.getElementById('chCity').value, c.area_id);
    document.getElementById('chPhone').value = c.contact_phone;
    document.getElementById('chRoom').value = c.room_number || '';
    document.getElementById('chPrefix').value = c.token_prefix || 'A';
    document.getElementById('chIsActive').value = c.is_active ? '1' : '0';
    document.getElementById('chamberModal').style.display = 'flex';
  };

  window.editSchedule = function(id) {
    const s = loadedSchedules.find(item => item.id == id);
    if (!s) return;
    document.getElementById('editScheduleId').value = s.id;
    document.getElementById('schChamberSelect').value = s.chamber_id;
    document.getElementById('schDayOfWeek').value = s.day_of_week;
    document.getElementById('schStartTime').value = s.start_time.substring(0, 5);
    document.getElementById('schEndTime').value = s.end_time.substring(0, 5);
    document.getElementById('schCapacity').value = s.default_capacity;
    document.getElementById('schSlotDuration').value = s.slot_duration_minutes;
    document.getElementById('scheduleModalTitle').innerText = 'Edit Weekly Consultation Hours';
    document.getElementById('scheduleModal').style.display = 'flex';
  };

  window.deleteScheduleItem = async function(id) {
    if (!confirm('Are you sure you want to delete this weekly schedule?')) return;
    try {
      const res = await Api.delete(`/doctor/schedules/${id}`);
      if (res.success) {
        showNotice('Weekly schedule removed successfully.');
        loadData();
      }
    } catch (err) {
      alert(err.message || 'Failed to delete schedule');
    }
  };

  async function loadData() {
    try {
      // 1. Load Chambers
      const cRes = await Api.get('/doctor/chambers');
      if (cRes.success) {
        loadedChambers = cRes.data;
        const grid = document.getElementById('chambersGrid');
        const schSelect = document.getElementById('schChamberSelect');

        if (loadedChambers.length === 0) {
          grid.innerHTML = '<div class="card" style="grid-column: span 2; text-align: center; color: var(--text-muted); padding: 32px;">No practice chambers registered yet. Click "+ Add New Chamber" above to set up your practice location.</div>';
          schSelect.innerHTML = '<option value="">-- No chambers created yet --</option>';
        } else {
          schSelect.innerHTML = loadedChambers.map(c => `<option value="${c.id}">${c.name} (${c.room_number ? 'Room ' + c.room_number : 'Main Desk'})</option>`).join('');

          grid.innerHTML = loadedChambers.map(c => `
            <div class="card" style="border-left: 5px solid var(--primary); display: flex; flex-direction: column; justify-content: space-between;">
              <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                  <h4 style="font-size: 18px; font-weight: 800; color: #0f172a;">${c.name}</h4>
                  <span class="status-pill status-${c.is_active ? 'on' : 'off'}">${c.is_active ? 'ACTIVE' : 'INACTIVE'}</span>
                </div>
                <div style="font-size: 13px; color: #475569; margin-top: 6px;">📍 ${c.address}${c.city_name ? ', ' + c.city_name : ''}</div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                  📞 ${c.contact_phone} • 🚪 ${c.room_number || 'Main Desk'} • Prefix: <strong>${c.token_prefix}</strong>
                </div>
              </div>
              <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                <button class="btn btn-outline btn-sm" onclick="editChamber(${c.id})" style="font-size: 12px;">✏️ Edit Chamber</button>
              </div>
            </div>
          `).join('');
        }
      }

      // 2. Load Schedules
      const sRes = await Api.get('/doctor/schedules');
      if (sRes.success) {
        loadedSchedules = sRes.data;
        const tbody = document.getElementById('schedulesTableBody');
        if (loadedSchedules.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">No weekly schedules configured yet. Click "+ Configure Weekly Hours" to set your consultation days.</td></tr>';
        } else {
          tbody.innerHTML = loadedSchedules.map(s => `
            <tr>
              <td><strong>${s.chamber_name}</strong></td>
              <td><span class="schedule-badge">${days[s.day_of_week]}</span></td>
              <td><strong style="color: #0f172a;">${formatTime(s.start_time)} – ${formatTime(s.end_time)}</strong></td>
              <td>${s.slot_duration_minutes} min/patient</td>
              <td><strong style="color: #059669;">${s.default_capacity} Patients</strong></td>
              <td><span class="status-pill status-${s.is_active ? 'on' : 'off'}">${s.is_active ? 'ACTIVE' : 'OFF'}</span></td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <button class="btn btn-outline btn-sm" onclick="editSchedule(${s.id})" style="font-size: 11px; padding: 4px 8px;">Edit</button>
                  <button class="btn btn-danger btn-sm" onclick="deleteScheduleItem(${s.id})" style="font-size: 11px; padding: 4px 8px;">Delete</button>
                </div>
              </td>
            </tr>
          `).join('');
        }
      }
    } catch (err) {
      console.error(err);
      showNotice('Error loading chamber data: ' + err.message, true);
    }
  }

  // Chamber Modal Handling
  const chModal = document.getElementById('chamberModal');
  document.getElementById('btnOpenAddChamberModal').addEventListener('click', () => {
    document.getElementById('editChamberId').value = '';
    document.getElementById('chamberModalTitle').innerText = 'Add Practice Chamber';
    document.getElementById('chamberForm').reset();
    document.getElementById('chPrefix').value = 'A';
    chModal.style.display = 'flex';
  });

  document.getElementById('btnCloseChamberModal').addEventListener('click', () => { chModal.style.display = 'none'; });
  document.getElementById('btnCancelChamber').addEventListener('click', () => { chModal.style.display = 'none'; });

  document.getElementById('chamberForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const editId = document.getElementById('editChamberId').value;
    const payload = {
      name: document.getElementById('chName').value.trim(),
      address: document.getElementById('chAddress').value.trim(),
      city_id: parseInt(document.getElementById('chCity').value, 10),
      area_id: parseInt(document.getElementById('chArea').value, 10),
      contact_phone: document.getElementById('chPhone').value.trim(),
      room_number: document.getElementById('chRoom').value.trim() || null,
      token_prefix: document.getElementById('chPrefix').value.trim() || 'A',
      is_active: parseInt(document.getElementById('chIsActive').value, 10)
    };

    try {
      if (editId) {
        await Api.put(`/doctor/chambers/${editId}`, payload);
        showNotice('Chamber details updated successfully!');
      } else {
        await Api.post('/doctor/chambers', payload);
        showNotice('New chamber registered successfully!');
      }
      chModal.style.display = 'none';
      loadData();
    } catch (err) {
      alert(err.message || 'Failed to save chamber');
    }
  });

  // Schedule Modal Handling
  const schModal = document.getElementById('scheduleModal');
  document.getElementById('btnOpenAddScheduleModal').addEventListener('click', () => {
    if (loadedChambers.length === 0) {
      alert('Please register at least one chamber before configuring consultation hours.');
      return;
    }
    document.getElementById('editScheduleId').value = '';
    document.getElementById('scheduleModalTitle').innerText = 'Configure Weekly Consultation Hours';
    document.getElementById('scheduleForm').reset();
    document.getElementById('schCapacity').value = '100';
    document.getElementById('schSlotDuration').value = '10';
    schModal.style.display = 'flex';
  });

  document.getElementById('btnCloseScheduleModal').addEventListener('click', () => { schModal.style.display = 'none'; });
  document.getElementById('btnCancelSchedule').addEventListener('click', () => { schModal.style.display = 'none'; });

  document.getElementById('scheduleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const editId = document.getElementById('editScheduleId').value;
    const payload = {
      chamber_id: parseInt(document.getElementById('schChamberSelect').value, 10),
      day_of_week: parseInt(document.getElementById('schDayOfWeek').value, 10),
      start_time: document.getElementById('schStartTime').value,
      end_time: document.getElementById('schEndTime').value,
      default_capacity: parseInt(document.getElementById('schCapacity').value, 10),
      slot_duration_minutes: parseInt(document.getElementById('schSlotDuration').value, 10)
    };

    try {
      if (editId) {
        await Api.put(`/doctor/schedules/${editId}`, payload);
        showNotice('Weekly consultation hours updated successfully!');
      } else {
        await Api.post('/doctor/schedules', payload);
        showNotice('Weekly consultation hours and capacity saved successfully!');
      }
      schModal.style.display = 'none';
      loadData();
    } catch (err) {
      alert(err.message || 'Failed to save schedule');
    }
  });

  loadLocations();
  loadData();
</script>

</body>
</html>
