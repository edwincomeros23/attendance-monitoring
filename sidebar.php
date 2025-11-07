<div class="sidebar">
  <div class="logo-container">
    <img src="wmsulogo.jpeg" alt="WMSU Logo" class="wmsu-logo" />
    <p class="school-year">2025–2026</p>
  </div> 
  <style>
    .sidebar {
      display: flex;
      flex-direction: column;
      height: 100vh; /* make sidebar full height */
      background-color: #b30000;
      padding: 18px 12px;
      box-sizing: border-box;
    }

    .menu {
      flex-grow: 1; /* push logout down */
      padding: 0;
      margin: 0;
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    /* Sidebar menu link styles (shared) */
    .menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 10px;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: background-color 120ms ease, transform 120ms ease;
    }
    .menu a i { width: 28px; text-align: center; font-size: 18px }
    .menu a span { display: inline-block }
    .menu a:hover, .menu a.active { background: rgba(255,255,255,0.08); color: #fff }

    .logout {
      padding: 12px 6px 8px 6px;
      margin-top: 12px;
    }

    /* Make logout look like a sidebar item */
    .logout a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 10px;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 700;
      width: 100%;
      background: transparent;
      transition: background-color 120ms ease;
      justify-content: flex-start;
    }
    .logout a i{ width:28px; text-align:center; font-size:16px }
    .logout a:hover { background: rgba(255,255,255,0.08); color: #fff }

    /* Small responsive tweak: reduce padding on very small heights */
    @media (max-height: 600px) {
      .sidebar { padding: 12px 8px; }
      .logout a { padding: 8px 10px; font-size: 14px; }
    }
  </style>
  <ul class="menu">
    <li>
      <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-house"></i><span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="camera.php" class="<?= basename($_SERVER['PHP_SELF']) == 'camera.php' ? 'active' : '' ?>">
        <i class="fas fa-camera"></i><span>Camera</span>
      </a>
    </li>
    <li>
      <a href="students.php" class="<?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>">
        <i class="fas fa-user-graduate"></i><span>Students</span>
      </a>
    </li>
    <li>
      <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-bar"></i><span>Reports</span>
      </a>
    </li>
    <li>
      <a href="teachers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : '' ?>">
        <i class="fas fa-chalkboard-teacher"></i><span>Teachers</span>
      </a>
    </li>
  </ul>
    <!-- Logout Button -->
  <div class="logout">
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>
