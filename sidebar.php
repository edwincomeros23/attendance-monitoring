<?php
// Attempt to start session only when possible.
// Some pages include this file after HTML has been sent; calling session_start()
// in that case raises a headers-sent warning. Guard against that.
if (session_status() == PHP_SESSION_NONE) {
  if (!headers_sent()) {
    session_start();
  }
}
$sy = isset($_SESSION['school_year']) && !empty($_SESSION['school_year']) ? htmlspecialchars($_SESSION['school_year']) : '2025–2026';
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';
$isAdmin = ($userRole === 'admin');
$notifCount = 0;
$notifCountDisplay = '0';
if (file_exists(__DIR__ . '/db.php')) {
  include_once __DIR__ . '/db.php';
  if (isset($conn)) {
    try {
      $res = $conn->query("SELECT COUNT(*) AS cnt FROM notification_logs");
      if ($res && ($row = $res->fetch_assoc())) {
        $notifCount = (int)$row['cnt'];
        $notifCountDisplay = (string)$notifCount;
      }
    } catch (Exception $e) {
      $notifCount = 0;
      $notifCountDisplay = '0';
    }
  }
}
?>
<div class="sidebar">
  <div class="logo-container">
    <img src="/attendance-monitoring/wmsulogo.jpeg" alt="WMSU Logo" class="wmsu-logo" />
    <p class="school-year"><?php echo $sy; ?></p>
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
      <a href="/attendance-monitoring/pages/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-house"></i><span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="/attendance-monitoring/pages/camera.php" class="<?= basename($_SERVER['PHP_SELF']) == 'camera.php' ? 'active' : '' ?>">
        <i class="fas fa-camera"></i><span>Camera</span>
      </a>
    </li>
    <li>
      <a href="/attendance-monitoring/pages/students.php" class="<?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>">
        <i class="fas fa-user-graduate"></i><span>Students</span>
      </a>
    </li>
    <li>
      <a href="/attendance-monitoring/pages/reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-bar"></i><span>Reports</span>
      </a>
    </li>
    <?php if ($isAdmin): ?>
    <li>
      <a href="/attendance-monitoring/pages/teachers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : '' ?>">
        <i class="fas fa-chalkboard-teacher"></i><span>Teachers</span>
      </a>
    </li>
    <li>
      <a href="/attendance-monitoring/pages/curicculum.php" class="<?= basename($_SERVER['PHP_SELF']) == 'curicculum.php' ? 'active' : '' ?>">
        <i class="fas fa-book"></i><span>Curicculum</span>
      </a>
    </li>
    <li>
      <a href="/attendance-monitoring/pages/sms_settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'sms_settings.php' ? 'active' : '' ?>">
        <i class="fas fa-bell"></i><span>SMS & Email Notifications</span>
      </a>
    </li>
    <?php endif; ?>
    <li>
      <a href="/attendance-monitoring/pages/attendance_report.php" class="<?= basename($_SERVER['PHP_SELF']) == 'attendance_report.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i><span>Attendance Report</span>
      </a>
    </li>
  </ul>
    <!-- Logout Button -->
  <div class="logout">
    <a href="/attendance-monitoring/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>
