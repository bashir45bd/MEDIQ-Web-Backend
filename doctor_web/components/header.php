<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<header class="top-bar">
  <div style="display: flex; align-items: center; gap: 12px;">
    <button id="btnMobileNavToggle" class="mobile-toggle-btn" aria-label="Toggle Navigation">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>
    <h2 style="font-size: 20px; font-weight: 700;"><?= $pageTitle ?? 'Doctor Portal' ?></h2>
  </div>
  <div style="display: flex; align-items: center; gap: 20px;">
    <div id="doctorProfileHeader" style="text-align: right;">
      <div id="headerDocName" style="font-weight: 600;">Dr. Ahmed Rahman</div>
      <div id="headerDocDesig" style="font-size: 12px; color: var(--text-muted);">BMDC-A12345</div>
    </div>
    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 700;">
      DR
    </div>
  </div>
</header>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const btnToggle = document.getElementById('btnMobileNavToggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (btnToggle && sidebar && backdrop) {
      btnToggle.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
        backdrop.classList.toggle('active');
      });
      backdrop.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('active');
      });
    }
  });
</script>
