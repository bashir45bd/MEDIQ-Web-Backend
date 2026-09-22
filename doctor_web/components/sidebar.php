<aside class="sidebar">
  <div class="sidebar-brand">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
    </svg>
    <span>MEDIQ Doctor</span>
  </div>
  <ul class="sidebar-nav">
    <li class="<?= ($activePage === 'dashboard') ? 'active' : '' ?>">
      <a href="dashboard.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
        <span>Dashboard</span>
      </a>
    </li>
    <li class="<?= ($activePage === 'queue') ? 'active' : '' ?>">
      <a href="queue.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Live Queue</span>
      </a>
    </li>
    <li class="<?= ($activePage === 'appointments') ? 'active' : '' ?>">
      <a href="appointments.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
        <span>Appointments</span>
      </a>
    </li>
    <li class="<?= ($activePage === 'chambers') ? 'active' : '' ?>">
      <a href="chambers.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Chambers & Schedules</span>
      </a>
    </li>
    <li class="<?= ($activePage === 'profile') ? 'active' : '' ?>">
      <a href="profile.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>My Profile</span>
      </a>
    </li>
  </ul>
  <div style="padding: 24px; border-top: 1px solid #1e293b;">
    <button id="btnLogout" class="btn btn-outline" style="width: 100%; color: #ef4444; border-color: #ef4444;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
      <span>Logout</span>
    </button>
  </div>
</aside>
