<?php
$activePage = 'posts';
$pageTitle = 'Posts, Offers & Blogs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Posts & Offers — MEDIQ Admin</title>
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
          <h3 style="font-size: 20px; font-weight: 700;">Patient App Posts, Offers & Health Blogs</h3>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Publish doctor announcements, special discount offers, and health blogs with instant push notifications to all patients.</p>
        </div>
        <button id="btnOpenPostModal" class="btn btn-primary">+ Create New Post</button>
      </div>

      <!-- Quick Metrics Cards -->
      <div class="grid grid-cols-4" style="margin-bottom: 24px;">
        <div class="card" style="padding: 18px; border-left: 4px solid var(--primary);">
          <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Posts</span>
          <h3 id="statTotalPosts" style="font-size: 24px; margin-top: 6px;">0</h3>
        </div>
        <div class="card" style="padding: 18px; border-left: 4px solid #8b5cf6;">
          <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Doctor Announcements</span>
          <h3 id="statDocAnnounce" style="font-size: 24px; margin-top: 6px;">0</h3>
        </div>
        <div class="card" style="padding: 18px; border-left: 4px solid #f59e0b;">
          <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Active Offers</span>
          <h3 id="statOffers" style="font-size: 24px; margin-top: 6px;">0</h3>
        </div>
        <div class="card" style="padding: 18px; border-left: 4px solid #10b981;">
          <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Patient Reads</span>
          <h3 id="statTotalViews" style="font-size: 24px; margin-top: 6px;">0</h3>
        </div>
      </div>

      <!-- Posts Table Card -->
      <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 30px;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Cover</th>
                <th>Category & Badge</th>
                <th>Title & Summary</th>
                <th>Linked Doctor</th>
                <th>Views</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="postsTableBody">
              <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Loading posts...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Post Create / Edit Modal -->
      <div id="postModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 18px;" id="modalPostTitle">Create New Post</h3>
            <button id="btnClosePostModal" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted);">&times;</button>
          </div>

          <form id="postForm">
            <input type="hidden" id="editPostId" value="">

            <div class="form-group">
              <label class="form-label" for="postTitle">Post Title *</label>
              <input type="text" id="postTitle" class="form-control" placeholder="e.g. নতুন বিশেষজ্ঞ চিকিৎসক ডা. ফারহান তানভীর যুক্ত হয়েছেন" required>
            </div>

            <div class="grid grid-cols-2">
              <div class="form-group">
                <label class="form-label" for="postCategory">Category *</label>
                <select id="postCategory" class="form-control" required>
                  <option value="DOCTOR_ANNOUNCEMENT">Doctor Announcement (নতুন ডাক্তার)</option>
                  <option value="SPECIAL_OFFER">Special Offer & Discount (অফার ও ছাড়)</option>
                  <option value="HEALTH_TIPS">Health Blog / Tips (স্বাস্থ্য পরামর্শ)</option>
                  <option value="GENERAL_NOTICE">General Notice (সাধারণ নোটিশ)</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="postBadge">Badge Text</label>
                <input type="text" id="postBadge" class="form-control" placeholder="e.g. 🎉 নতুন ডাক্তার / 🎁 অফার">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="postDoctorId">Link to Doctor (Optional)</label>
              <select id="postDoctorId" class="form-control">
                <option value="">-- No specific doctor --</option>
              </select>
              <small style="font-size: 11.5px; color: var(--text-muted);">If linked, a "Book Serial" card will be displayed inside the post in the patient app.</small>
            </div>

            <div class="form-group">
              <label class="form-label" for="postCoverImage">Cover Image URL</label>
              <input type="url" id="postCoverImage" class="form-control" placeholder="https://images.unsplash.com/... or image link">
            </div>

            <div class="form-group">
              <label class="form-label" for="postSummary">Short Summary * (Notification & Preview)</label>
              <textarea id="postSummary" class="form-control" rows="2" placeholder="Brief 1-2 sentences summary shown in the home feed and phone push notification." required></textarea>
            </div>

            <div class="form-group">
              <label class="form-label" for="postContent">Full Post Article / Details *</label>
              <textarea id="postContent" class="form-control" rows="6" placeholder="Write the complete article, details, terms of offer, or doctor details..." required></textarea>
            </div>

            <div class="form-group" style="background: #eff6ff; padding: 12px; border-radius: 6px; border: 1px dashed #93c5fd;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; font-size: 13px; color: #1e3a8a;">
                <input type="checkbox" id="postNotifyPatients" value="1" checked>
                <span>📢 Send instant push notification to all patient mobile apps</span>
              </label>
              <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; margin-left: 24px;">Patients will immediately receive a notification on their smartphones with this post title and summary.</p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
              <button type="button" id="btnCancelPostModal" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary" id="btnSavePost">Publish Post</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="module">
  import { AdminApi as Api, AdminApi } from '../assets/js/api.js';

  let postsList = [];
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
        const select = document.getElementById('postDoctorId');
        select.innerHTML = '<option value="">-- No specific doctor --</option>' +
          doctorsList.map(d => `<option value="${d.id}">${d.full_name || d.name} (${d.designation || 'Doctor'})</option>`).join('');
      }
    } catch (e) {
      console.error('Failed to load doctors:', e);
    }
  }

  function getCategoryBadge(cat, badge) {
    const b = badge || 'Post';
    if (cat === 'DOCTOR_ANNOUNCEMENT') return `<span class="status-pill" style="background:#ede9fe; color:#6d28d9;">${b}</span>`;
    if (cat === 'SPECIAL_OFFER') return `<span class="status-pill" style="background:#fef3c7; color:#b45309;">${b}</span>`;
    if (cat === 'HEALTH_TIPS') return `<span class="status-pill" style="background:#dcfce7; color:#15803d;">${b}</span>`;
    return `<span class="status-pill status-paused">${b}</span>`;
  }

  async function loadPosts() {
    const tbody = document.getElementById('postsTableBody');
    try {
      const res = await Api.get('/admin/posts');
      if (!res.success) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 20px;">Failed to load posts.</td></tr>';
        return;
      }

      postsList = res.data;

      // Update statistics
      document.getElementById('statTotalPosts').innerText = postsList.length;
      document.getElementById('statDocAnnounce').innerText = postsList.filter(p => p.category === 'DOCTOR_ANNOUNCEMENT').length;
      document.getElementById('statOffers').innerText = postsList.filter(p => p.category === 'SPECIAL_OFFER').length;
      const totalViews = postsList.reduce((acc, p) => acc + (parseInt(p.views_count, 10) || 0), 0);
      document.getElementById('statTotalViews').innerText = totalViews;

      if (postsList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No posts created yet. Click "+ Create New Post" to publish an offer or announcement.</td></tr>';
        return;
      }

      tbody.innerHTML = postsList.map(p => `
        <tr>
          <td>
            <img src="${p.cover_image || 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=120'}" 
                 style="width: 54px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
          </td>
          <td>${getCategoryBadge(p.category, p.badge_text)}</td>
          <td>
            <strong style="color: #1e293b; font-size: 14px;">${p.title}</strong>
            <br><small style="color: var(--text-muted); display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;">${p.summary}</small>
          </td>
          <td><strong>${p.doctor_name || '—'}</strong></td>
          <td><span style="font-weight: 600; color: var(--primary);">👁️ ${p.views_count || 0}</span></td>
          <td>
            <span class="status-pill status-${p.is_published == 1 ? 'on' : 'off'}">
              ${p.is_published == 1 ? 'Published' : 'Draft'}
            </span>
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <button class="btn btn-outline btn-sm btn-edit-post" data-id="${p.id}">Edit</button>
              ${p.is_published == 1 ? `
                <button class="btn btn-danger btn-sm btn-unpublish" data-id="${p.id}">Draft</button>
              ` : `
                <button class="btn btn-success btn-sm btn-publish" data-id="${p.id}">Publish</button>
              `}
              <button class="btn btn-outline btn-sm btn-delete-post" data-id="${p.id}" style="color: #dc2626;">&times;</button>
            </div>
          </td>
        </tr>
      `).join('');

    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 20px;">Error: ${err.message}</td></tr>`;
    }
  }

  const modal = document.getElementById('postModal');
  document.getElementById('btnOpenPostModal').addEventListener('click', () => {
    document.getElementById('modalPostTitle').innerText = 'Create New Post';
    document.getElementById('editPostId').value = '';
    document.getElementById('postForm').reset();
    document.getElementById('postBadge').value = '🎉 নতুন ডাক্তার';
    document.getElementById('postNotifyPatients').checked = true;
    modal.style.display = 'flex';
  });

  document.getElementById('postCategory').addEventListener('change', (e) => {
    const cat = e.target.value;
    const badgeInput = document.getElementById('postBadge');
    if (cat === 'DOCTOR_ANNOUNCEMENT') badgeInput.value = '🎉 নতুন ডাক্তার';
    else if (cat === 'SPECIAL_OFFER') badgeInput.value = '🎁 স্পেশাল অফার';
    else if (cat === 'HEALTH_TIPS') badgeInput.value = '💡 স্বাস্থ্য পরামর্শ';
    else badgeInput.value = '📢 নোটিশ';
  });

  document.getElementById('btnClosePostModal').addEventListener('click', () => { modal.style.display = 'none'; });
  document.getElementById('btnCancelPostModal').addEventListener('click', () => { modal.style.display = 'none'; });

  document.getElementById('postForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editPostId').value;
    const payload = {
      title: document.getElementById('postTitle').value,
      category: document.getElementById('postCategory').value,
      badge_text: document.getElementById('postBadge').value,
      doctor_id: document.getElementById('postDoctorId').value || null,
      cover_image: document.getElementById('postCoverImage').value,
      summary: document.getElementById('postSummary').value,
      content: document.getElementById('postContent').value,
      notify_patients: document.getElementById('postNotifyPatients').checked ? 1 : 0
    };

    try {
      if (id) {
        await Api.put(`/admin/posts/${id}`, payload);
        showNotice('Post updated successfully.');
      } else {
        await Api.post('/admin/posts', payload);
        showNotice('Post published and broadcasted to all patient apps.');
      }
      modal.style.display = 'none';
      loadPosts();
    } catch (err) {
      showNotice(err.message, 'danger');
    }
  });

  document.getElementById('postsTableBody').addEventListener('click', async (e) => {
    const editBtn = e.target.closest('.btn-edit-post');
    if (editBtn) {
      const id = parseInt(editBtn.dataset.id, 10);
      const p = postsList.find(item => item.id === id);
      if (p) {
        document.getElementById('modalPostTitle').innerText = 'Edit Post';
        document.getElementById('editPostId').value = p.id;
        document.getElementById('postTitle').value = p.title;
        document.getElementById('postCategory').value = p.category;
        document.getElementById('postBadge').value = p.badge_text || '';
        document.getElementById('postDoctorId').value = p.doctor_id || '';
        document.getElementById('postCoverImage').value = p.cover_image || '';
        document.getElementById('postSummary').value = p.summary;
        document.getElementById('postContent').value = p.content;
        document.getElementById('postNotifyPatients').checked = false;
        modal.style.display = 'flex';
      }
      return;
    }

    const unpubBtn = e.target.closest('.btn-unpublish');
    if (unpubBtn) {
      const id = unpubBtn.dataset.id;
      try {
        await Api.put(`/admin/posts/${id}`, { is_published: 0 });
        showNotice('Post moved to drafts.');
        loadPosts();
      } catch (err) {
        showNotice(err.message, 'danger');
      }
      return;
    }

    const pubBtn = e.target.closest('.btn-publish');
    if (pubBtn) {
      const id = pubBtn.dataset.id;
      try {
        await Api.put(`/admin/posts/${id}`, { is_published: 1 });
        showNotice('Post published.');
        loadPosts();
      } catch (err) {
        showNotice(err.message, 'danger');
      }
      return;
    }

    const delBtn = e.target.closest('.btn-delete-post');
    if (delBtn) {
      const id = delBtn.dataset.id;
      if (confirm('Are you sure you want to delete this post?')) {
        try {
          await Api.delete(`/admin/posts/${id}`);
          showNotice('Post deleted.');
          loadPosts();
        } catch (err) {
          showNotice(err.message, 'danger');
        }
      }
      return;
    }
  });

  await loadDoctorsDropdown();
  loadPosts();
</script>

</body>
</html>