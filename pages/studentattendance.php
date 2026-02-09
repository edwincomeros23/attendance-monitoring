<?php
include '../db.php';

$section = isset($_GET['section']) ? trim($_GET['section']) : '';

// simple fetch of students in the given section
$students = [];
if ($section) {
    $stmt = $conn->prepare("SELECT id, student_id, full_name, year_level FROM students WHERE section = ? ORDER BY full_name ASC");
    $stmt->bind_param('s', $section);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $students[] = $r;
    $stmt->close();
}

// ensure attendance table exists
$create_sql = "CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  section VARCHAR(100) DEFAULT NULL,
  date DATE NOT NULL,
  status VARCHAR(50) DEFAULT NULL,
  time_in TIME DEFAULT NULL,
  time_out TIME DEFAULT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_date (student_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create_sql);

// load today's attendance
$attendanceToday = [];
$today = date('Y-m-d');
if ($section && !empty($students)) {
  $ids = array_column($students, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids));
  $sql = "SELECT student_id, status, time_in, time_out FROM attendance WHERE date = ? AND student_id IN ($placeholders)";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $bind_params = array_merge([$today], $ids);
    $bind_types = 's' . $types;
    $refs = [&$bind_types];
    foreach ($bind_params as $k => $v) { $refs[] = &$bind_params[$k]; }
    call_user_func_array(array($stmt, 'bind_param'), $refs);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $attendanceToday[(int)$row['student_id']] = $row;
    }
    $stmt->close();
  }
}

// load week attendance (last 7 days, present count)
$attendanceWeek = [];
if ($section && !empty($students)) {
  $ids = array_column($students, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids));
  $startDate = date('Y-m-d', strtotime('-6 days'));
  $sql = "SELECT student_id, date FROM attendance WHERE date >= ? AND date <= ? AND student_id IN ($placeholders) AND status = 'Present'";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $bind_params = array_merge([$startDate, $today], $ids);
    $bind_types = 'ss' . $types;
    $refs = [&$bind_types];
    foreach ($bind_params as $k => $v) { $refs[] = &$bind_params[$k]; }
    call_user_func_array(array($stmt, 'bind_param'), $refs);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $sid = (int)$row['student_id'];
      if (!isset($attendanceWeek[$sid])) $attendanceWeek[$sid] = 0;
      $attendanceWeek[$sid]++;
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Attendance — <?php echo htmlspecialchars($section ?: 'Section', ENT_QUOTES); ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
  <style>
  .main{flex:1;padding:20px;box-sizing:border-box}
  header{background:#b30000;color:#fff;padding:10px 20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
  table{width:100%;border-collapse:collapse;margin-top:14px;background:#fff;border-radius:6px;overflow:hidden}
  table thead{background:#f7f7f7}
  table th, table td{padding:12px;border-bottom:1px solid #f2f2f2;text-align:left;vertical-align:middle}
  tr:hover{background:#f9f9f9}
  /* Status badge styling */
  .status-badge {display:inline-block;padding:5px 10px;border-radius:4px;font-size:12px;font-weight:600;min-width:60px;text-align:center}
  .status-present {background:#d4edda;color:#155724}
  .status-absent {background:#f8d7da;color:#721c24}
  .status-late {background:#fff3cd;color:#856404}
  /* Week attendance chip */
  .week-attendance {display:inline-block;background:#f0f0f0;padding:4px 8px;border-radius:4px;font-size:13px;font-weight:600}
  .week-attendance.high {background:#d4edda;color:#155724}
  .week-attendance.medium {background:#fff3cd;color:#856404}
  .week-attendance.low {background:#f8d7da;color:#721c24}
  .back-link{color:#fff;text-decoration:none;background:transparent;border:0}
  </style>
</head>
<body>
  <?php include '../sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Section Attendance</h2>
      <div class="header-actions">
        <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
            <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
          </svg>
          <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a class="back-link" href="dashboard.php">← Back</a>
      </div>
    </header>

    <h3 style="margin-top:12px"><?php echo htmlspecialchars($section ?: 'Unknown Section', ENT_QUOTES); ?></h3>

    <?php if (empty($section)): ?>
      <p>Please select a section from the dashboard.</p>  
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Student ID</th>
            <th>Year</th>
            <th>Today's Status</th>
            <th>This Week</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="5" style="opacity:.6">No students found in this section.</td></tr>
          <?php else: ?>
            <?php foreach ($students as $s): 
                  $sid = (int)$s['id'];
                  $todayAtt = isset($attendanceToday[$sid]) ? $attendanceToday[$sid] : null;
                  $weekCount = isset($attendanceWeek[$sid]) ? $attendanceWeek[$sid] : 0;

                  $statusBadgeClass = 'status-absent';
                  $statusText = 'Absent';
                  if ($todayAtt) {
                    if ($todayAtt['status'] === 'Present') { $statusBadgeClass = 'status-present'; $statusText = 'Present'; }
                    elseif ($todayAtt['status'] === 'Late') { $statusBadgeClass = 'status-late'; $statusText = 'Late'; }
                  }

                  $weekClass = $weekCount >= 5 ? 'high' : ($weekCount >= 3 ? 'medium' : 'low');
            ?>
              <tr data-student-db-id="<?php echo $sid; ?>">
                <td><?php echo htmlspecialchars($s['full_name'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($s['student_id'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($s['year_level'], ENT_QUOTES); ?></td>
                <td><span class="status-badge <?php echo $statusBadgeClass; ?>"><?php echo htmlspecialchars($statusText, ENT_QUOTES); ?></span></td>
                <td><span class="week-attendance <?php echo $weekClass; ?>"><?php echo (int)$weekCount; ?>/7 days</span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // UI is rendered server-side; no interactive toggles needed for this summary view.
    });
  </script>
</body>
</html>
