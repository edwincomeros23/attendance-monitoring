<?php
session_start();
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

// Get filter inputs
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'week'; // day, week, month
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filterYear = isset($_GET['year']) ? trim($_GET['year']) : '';
$filterSection = isset($_GET['section']) ? trim($_GET['section']) : '';

// Calculate date range based on filter type
$startDate = $filterDate;
$endDate = $filterDate;

if ($filterType === 'week') {
  $dayOfWeek = date('N', strtotime($filterDate));
  $startDate = date('Y-m-d', strtotime($filterDate . ' -' . ($dayOfWeek - 1) . ' days'));
  $endDate = date('Y-m-d', strtotime($filterDate . ' +' . (7 - $dayOfWeek) . ' days'));
} elseif ($filterType === 'month') {
  $startDate = date('Y-m-01', strtotime($filterDate));
  $endDate = date('Y-m-t', strtotime($filterDate));
}

// Normalize grade level
$gradeLevels = [];
if ($filterYear !== '') {
  if (ctype_digit($filterYear)) {
    $gradeLevels = [$filterYear, 'Grade ' . $filterYear];
  } elseif (stripos($filterYear, 'Grade') === 0) {
    $gradeLevels[] = $filterYear;
    $num = trim(str_ireplace('Grade', '', $filterYear));
    if ($num !== '' && ctype_digit($num)) {
      $gradeLevels[] = $num;
    }
  } else {
    $gradeLevels[] = $filterYear;
  }
}

// Build attendance query
$attendanceRecords = [];
$summaryStats = ['Present' => 0, 'Late' => 0, 'Absent' => 0];

$sql = "SELECT a.*, s.full_name, s.year_level, s.section FROM attendance a
        LEFT JOIN students s ON a.student_id = s.id
        WHERE a.date >= ? AND a.date <= ?";
$types = 'ss';
$params = [$startDate, $endDate];

if (!empty($gradeLevels)) {
  $placeholders = implode(',', array_fill(0, count($gradeLevels), '?'));
  $sql .= " AND s.year_level IN ($placeholders)";
  $types .= str_repeat('s', count($gradeLevels));
  $params = array_merge($params, $gradeLevels);
}

if ($filterSection !== '') {
  $sql .= " AND a.section = ?";
  $types .= 's';
  $params[] = $filterSection;
}

$sql .= " ORDER BY a.date DESC, s.full_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $attendanceRecords[] = $row;
    if (!empty($row['status'])) {
      $summaryStats[$row['status']]++;
    } else {
      $summaryStats['Absent']++;
    }
  }
  $stmt->close();
}

// Fetch grade levels and sections for dropdowns
$gradeOptions = [];
$sectionOptions = [];
$gradeRes = $conn->query("SELECT DISTINCT year_level FROM students ORDER BY year_level ASC");
if ($gradeRes) {
  while ($r = $gradeRes->fetch_assoc()) {
    $gradeOptions[] = $r['year_level'];
  }
}

if ($filterYear !== '' && !empty($gradeLevels)) {
  $placeholders = implode(',', array_fill(0, count($gradeLevels), '?'));
  $sectSql = "SELECT DISTINCT section FROM students WHERE year_level IN ($placeholders) ORDER BY section ASC";
  $stmt = $conn->prepare($sectSql);
  if ($stmt) {
    $stmt->bind_param(str_repeat('s', count($gradeLevels)), ...$gradeLevels);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
      if (!empty($r['section'])) $sectionOptions[] = $r['section'];
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Report - WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; display: flex; background: #f5f5f5; height: 100vh; overflow: hidden; }
    .sidebar { position: fixed; left: 0; top: 0; height: 100vh; }
    .main { flex: 1; padding: 20px; box-sizing: border-box; overflow: auto; height: 100vh; margin-left: 220px; }
    header { background: #b30000; color: #fff; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
    .filters { background: #fff; padding: 16px; border-radius: 8px; margin-top: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px; }
    .filter-group { display: flex; flex-direction: column; }
    .filter-group label { font-weight: 600; margin-bottom: 4px; color: #333; font-size: 13px; }
    .filter-group input, .filter-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
    .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #b30000; }
    .filter-btn { background: #b30000; color: #fff; padding: 8px 16px; border: 0; border-radius: 4px; cursor: pointer; font-weight: 600; }
    .filter-btn:hover { background: #990000; }
    .summary { background: #fff; padding: 16px; border-radius: 8px; margin-top: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
    .summary-card { background: #f9f9f9; padding: 12px; border-left: 4px solid #b30000; border-radius: 4px; text-align: center; }
    .summary-card h4 { margin: 0 0 6px 0; font-size: 13px; color: #666; font-weight: 600; }
    .summary-card .count { font-size: 24px; font-weight: 700; color: #b30000; }
    .records-container { background: #fff; padding: 16px; border-radius: 8px; margin-top: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .records-title { font-size: 16px; font-weight: 700; margin: 0 0 12px 0; color: #b30000; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { background: #f7f7f7; padding: 10px 8px; text-align: left; font-weight: 600; border-bottom: 2px solid #e0e0e0; }
    td { padding: 8px; border-bottom: 1px solid #eee; }
    tr:hover { background: #f9f9f9; }
    .status-badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-weight: 600; font-size: 12px; }
    .status-present { background: #d4edda; color: #155724; }
    .status-late { background: #fff3cd; color: #856404; }
    .status-absent { background: #f8d7da; color: #721c24; }
    .back-btn { display: inline-flex; align-items: center; gap: 6px; background: #b30000; color: #fff; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
    .back-btn:hover { background: #990000; }
  </style>
</head>
<body>
  <?php include '../sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Attendance Report</h2>
      <div class="header-actions">
        <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
            <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
          </svg>
          <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </header>

    <!-- Filters -->
    <div class="filters">
      <h3 style="margin: 0 0 12px 0; color: #333;">Filter Attendance</h3>
      <form method="GET" style="display: contents;">
        <div class="filter-row">
          <div class="filter-group">
            <label for="filter">Filter By:</label>
            <select name="filter" id="filter" onchange="this.form.submit();">
              <option value="day" <?php echo $filterType === 'day' ? 'selected' : ''; ?>>Day</option>
              <option value="week" <?php echo $filterType === 'week' ? 'selected' : ''; ?>>Week</option>
              <option value="month" <?php echo $filterType === 'month' ? 'selected' : ''; ?>>Month</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="date">Date:</label>
            <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($filterDate); ?>" onchange="this.form.submit();" />
          </div>
          <div class="filter-group">
            <label for="year">Grade Level:</label>
            <select name="year" id="year" onchange="this.form.submit();">
              <option value="">-- All Grades --</option>
              <?php foreach ($gradeOptions as $g): ?>
                <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $filterYear === $g ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($g); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label for="section">Section:</label>
            <select name="section" id="section" onchange="this.form.submit();">
              <option value="">-- All Sections --</option>
              <?php foreach ($sectionOptions as $s): ?>
                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $filterSection === $s ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($s); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>
    </div>

    <!-- Summary Stats -->
    <div class="summary">
      <div class="summary-card">
        <h4>Present</h4>
        <div class="count"><?php echo $summaryStats['Present']; ?></div>
      </div>
      <div class="summary-card">
        <h4>Late</h4>
        <div class="count"><?php echo $summaryStats['Late']; ?></div>
      </div>
      <div class="summary-card">
        <h4>Absent</h4>
        <div class="count"><?php echo $summaryStats['Absent']; ?></div>
      </div>
      <div class="summary-card">
        <h4>Total Records</h4>
        <div class="count"><?php echo count($attendanceRecords); ?></div>
      </div>
    </div>

    <!-- Records Table -->
    <div class="records-container">
      <h3 class="records-title">
        <?php
          if ($filterType === 'day') {
            echo 'Daily Attendance - ' . date('F j, Y', strtotime($filterDate));
          } elseif ($filterType === 'week') {
            echo 'Weekly Attendance - ' . date('M j', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate));
          } else {
            echo 'Monthly Attendance - ' . date('F Y', strtotime($filterDate));
          }
        ?>
      </h3>
      
      <?php if (empty($attendanceRecords)): ?>
        <p style="text-align: center; color: #999; padding: 20px;">No attendance records found for the selected period.</p>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Status</th>
                <th>Time-In</th>
                <th>Time-Out</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendanceRecords as $record): ?>
                <tr>
                  <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                  <td><?php echo htmlspecialchars($record['student_id'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($record['full_name'] ?? 'Unknown'); ?></td>
                  <td><?php echo htmlspecialchars($record['year_level'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($record['section'] ?? ''); ?></td>
                  <td>
                    <?php
                      $status = $record['status'] ?? 'Absent';
                      $statusClass = '';
                      if ($status === 'Present') $statusClass = 'status-present';
                      elseif ($status === 'Late') $statusClass = 'status-late';
                      else $statusClass = 'status-absent';
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>">
                      <?php echo htmlspecialchars($status); ?>
                    </span>
                  </td>
                  <td><?php echo !empty($record['time_in']) ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                  <td><?php echo !empty($record['time_out']) ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
