<?php
session_start();
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

// Get filter inputs (persist in session so returning keeps the last selection)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : (isset($_SESSION['report_filter']) ? $_SESSION['report_filter'] : 'week'); // day, week, month
$filterDate = isset($_GET['date']) ? $_GET['date'] : (isset($_SESSION['report_date']) ? $_SESSION['report_date'] : date('Y-m-d'));
$filterYear = isset($_GET['year']) ? trim($_GET['year']) : (isset($_SESSION['report_year']) ? $_SESSION['report_year'] : '');
$filterSection = isset($_GET['section']) ? trim($_GET['section']) : (isset($_SESSION['report_section']) ? $_SESSION['report_section'] : '');
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : (isset($_SESSION['report_status']) ? $_SESSION['report_status'] : '');
$allowedStatuses = ['Present', 'Late', 'Absent'];
if (!in_array($filterStatus, $allowedStatuses, true)) {
  $filterStatus = '';
}

$_SESSION['report_filter'] = $filterType;
$_SESSION['report_date'] = $filterDate;
$_SESSION['report_year'] = $filterYear;
$_SESSION['report_section'] = $filterSection;
$_SESSION['report_status'] = $filterStatus;

// Persist report selection for dashboard cards
$_SESSION['dashboard_date'] = $filterDate;
$_SESSION['dashboard_status'] = $filterStatus;

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

// Ensure recognition_logs table exists for detection counts
$createLogs = "CREATE TABLE IF NOT EXISTS recognition_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    predicted_id INT NOT NULL,
    actual_id INT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    section VARCHAR(100) DEFAULT NULL,
    log_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($createLogs);

// Build attendance query
$attendanceRecords = [];
$summaryStats = ['Present' => 0, 'Late' => 0, 'Absent' => 0];

$sql = "SELECT a.student_id, a.date, a.status, a.time_in, a.time_out, a.section, 
               s.id, s.full_name, s.year_level, s.section AS student_section, 
               COALESCE(r.detect_count, 0) AS detect_count
  FROM attendance a
  LEFT JOIN students s ON a.student_id = s.id
  LEFT JOIN (
    SELECT actual_id AS student_id, log_date, COUNT(*) AS detect_count
    FROM recognition_logs
    WHERE is_correct = 1 AND log_date >= ? AND log_date <= ?";
$types = 'ss';
$params = [$startDate, $endDate];

if ($filterSection !== '') {
  $sql .= " AND section = ?";
  $types .= 's';
  $params[] = $filterSection;
}

$sql .= " GROUP BY actual_id, log_date
  ) r ON r.student_id = a.student_id AND r.log_date = a.date
  WHERE a.date >= ? AND a.date <= ?";
$types .= 'ss';
$params[] = $startDate;
$params[] = $endDate;

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

if ($filterStatus !== '') {
  $sql .= " AND a.status = ?";
  $types .= 's';
  $params[] = $filterStatus;
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

function grade_variants($grade) {
  $variants = [];
  if ($grade === null || $grade === '') return $variants;
  $g = trim((string)$grade);
  $variants[] = $g;
  if (stripos($g, 'Grade') === 0) {
    $num = trim(str_ireplace('Grade', '', $g));
    if ($num !== '' && ctype_digit($num)) $variants[] = $num;
  } elseif (ctype_digit($g)) {
    $variants[] = 'Grade ' . $g;
  }
  return array_values(array_unique($variants));
}

function get_subject_count_for_day($grade, $section, $dayName, $countsMap) {
  $variants = grade_variants($grade);
  $sectionKey = trim((string)$section);
  foreach ($variants as $variant) {
    if (isset($countsMap[$variant][$sectionKey][$dayName])) {
      return (int)$countsMap[$variant][$sectionKey][$dayName];
    }
    if (isset($countsMap[$variant][''][$dayName])) {
      return (int)$countsMap[$variant][''][$dayName];
    }
  }
  return 0;
}

// Preload curriculum subject counts for the report rows
$subjectCounts = [];
$gradeSet = [];
$sectionSet = [];
foreach ($attendanceRecords as $record) {
  if (!empty($record['year_level'])) {
    foreach (grade_variants($record['year_level']) as $gv) {
      $gradeSet[$gv] = true;
    }
  }
  if (!empty($record['section'])) {
    $sectionSet[trim($record['section'])] = true;
  }
}

if (!empty($gradeSet)) {
  $gradeList = array_keys($gradeSet);
  $gradePlaceholders = implode(',', array_fill(0, count($gradeList), '?'));
  $currSql = "SELECT grade_level, section, day_of_week, COUNT(*) AS cnt
              FROM curriculum
              WHERE grade_level IN ($gradePlaceholders)";
  $currParams = $gradeList;
  $currTypes = str_repeat('s', count($gradeList));

  if (!empty($sectionSet)) {
    $sectList = array_keys($sectionSet);
    $sectPlaceholders = implode(',', array_fill(0, count($sectList), '?'));
    $currSql .= " AND (section IN ($sectPlaceholders) OR section = '' OR section IS NULL)";
    $currParams = array_merge($currParams, $sectList);
    $currTypes .= str_repeat('s', count($sectList));
  }

  $currSql .= " GROUP BY grade_level, section, day_of_week";
  $stmt = $conn->prepare($currSql);
  if ($stmt) {
    $stmt->bind_param($currTypes, ...$currParams);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $g = $row['grade_level'] ?? '';
      $s = trim((string)($row['section'] ?? ''));
      $d = $row['day_of_week'] ?? '';
      if (!isset($subjectCounts[$g])) $subjectCounts[$g] = [];
      if (!isset($subjectCounts[$g][$s])) $subjectCounts[$g][$s] = [];
      $subjectCounts[$g][$s][$d] = (int)$row['cnt'];
    }
    $stmt->close();
  }
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
    .records-title { font-size: 16px; font-weight: 700; margin: 0; color: #b30000; }
    .records-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 0 0 12px 0; }
    .records-actions { display: inline-flex; gap: 8px; }
    .records-actions button { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #b30000; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; border: 1px solid #b30000; cursor: pointer; }
    .records-actions button:hover { background: #f7f7f7; }
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
    .print-btn { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #b30000; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; border: 1px solid #b30000; }
    .print-btn:hover { background: #f7f7f7; }
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
        <button type="button" class="print-btn" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
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
          <div class="filter-group">
            <label for="status">Status:</label>
            <select name="status" id="status" onchange="this.form.submit();">
              <option value="">-- All Statuses --</option>
              <option value="Present" <?php echo $filterStatus === 'Present' ? 'selected' : ''; ?>>Present</option>
              <option value="Late" <?php echo $filterStatus === 'Late' ? 'selected' : ''; ?>>Late</option>
              <option value="Absent" <?php echo $filterStatus === 'Absent' ? 'selected' : ''; ?>>Absent</option>
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
      <div class="records-header">
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
        <div class="records-actions">
          <button type="button" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
          <button type="button" id="exportPdfBtn"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
      </div>
      
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
                <th>Detected/Subjects</th>
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
                  <?php
                    $dayName = date('l', strtotime($record['date']));
                    $subjectTotal = get_subject_count_for_day($record['year_level'] ?? '', $record['section'] ?? '', $dayName, $subjectCounts);
                    $detectCount = (int)($record['detect_count'] ?? 0);
                  ?>
                  <td><?php echo $detectCount . '/' . $subjectTotal; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script>
    async function exportReportPdf() {
      const container = document.querySelector('.records-container');
      if (!container) return;

      const { jsPDF } = window.jspdf || {};
      if (!jsPDF) return;

      const originalScroll = container.scrollTop;
      container.scrollTop = 0;

      const canvas = await html2canvas(container, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff'
      });

      const imgData = canvas.toDataURL('image/png');
      const pdf = new jsPDF('p', 'pt', 'a4');
      const pageWidth = pdf.internal.pageSize.getWidth();
      const pageHeight = pdf.internal.pageSize.getHeight();

      const imgWidth = pageWidth - 40;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let position = 20;
      let heightLeft = imgHeight;

      pdf.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;

      while (heightLeft > 0) {
        position = heightLeft - imgHeight + 20;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }

      const filename = 'attendance-report-' + new Date().toISOString().split('T')[0] + '.pdf';
      pdf.save(filename);

      container.scrollTop = originalScroll;
    }

    const exportBtn = document.getElementById('exportPdfBtn');
    if (exportBtn) {
      exportBtn.addEventListener('click', exportReportPdf);
    }
  </script>
</body>
</html>
