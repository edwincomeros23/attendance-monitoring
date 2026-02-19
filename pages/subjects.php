<?php
session_start();
require_once __DIR__ . '/../db.php';

// Read year and section from URL
$year = isset($_GET['year']) ? trim($_GET['year']) : '';
$section = isset($_GET['section']) ? trim($_GET['section']) : '';

// Normalize grade level for query (accepts "Grade 7" or "7")
$subjects = [];
if ($year) {
  $gradeKey1 = trim($year);
  $gradeKey2 = $gradeKey1;
  // If numeric, also try "Grade X"; if starts with "Grade ", also try numeric part
  if (ctype_digit($gradeKey1)) {
    $gradeKey2 = 'Grade ' . $gradeKey1;
  } elseif (stripos($gradeKey1, 'Grade') === 0) {
    $num = trim(str_ireplace('Grade', '', $gradeKey1));
    if ($num !== '' && ctype_digit($num)) {
      $gradeKey2 = $num;
    }
  }

  // Prepare query to match either form
  if ($gradeKey1 === $gradeKey2) {
    $stmt = $conn->prepare("SELECT * FROM curriculum WHERE grade_level = ? ORDER BY subject_name");
    if ($stmt) {
      $stmt->bind_param('s', $gradeKey1);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
      }
      $stmt->close();
    }
  } else {
    $stmt = $conn->prepare("SELECT * FROM curriculum WHERE grade_level IN (?, ?) ORDER BY subject_name");
    if ($stmt) {
      $stmt->bind_param('ss', $gradeKey1, $gradeKey2);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
      }
      $stmt->close();
    }
  }
}

// Fetch teachers for adviser resolution
$teacherById = [];
$teacherRes = $conn->query("SELECT id, faculty_id, first_name, middle_initial, last_name FROM teachers");
if ($teacherRes && $teacherRes->num_rows > 0) {
  while ($t = $teacherRes->fetch_assoc()) {
    $name = trim($t['first_name'] . ' ' . ($t['middle_initial'] ? $t['middle_initial'] . ' ' : '') . $t['last_name']);
    $teacherById[(int)$t['id']] = $name;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Subjects - WMSU Attendance</title>
  <link rel="icon" type="image/png" href="../wmsulogo_circular.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; display:flex; background:#f5f5f5 }
    .main { flex:1; padding:20px; box-sizing:border-box }
    header { background:#b30000; color:#fff; padding:10px 20px; border-radius:8px; display:flex; justify-content:space-between; align-items:center }
    .card { margin-top:14px; background:#fff; padding:12px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05) }
    .card h3 { margin:0 0 8px 0; color:#b71c1c }
    table.main-table { width:100%; border-collapse:collapse; font-size:14px }
    table.main-table thead th { background:#b71c1c; color:#fff; padding:10px; text-align:left }
    table.main-table tbody td { padding:10px; border-bottom:1px solid #e0e0e0 }
    .small-btn { display:inline-flex; align-items:center; gap:8px; background:#b30000; color:#fff; padding:6px 10px; border-radius:6px; text-decoration:none; font-weight:600 }
    .small-btn:hover { background:#990000 }
  </style>
</head>
<body>
  <?php include '../sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>WMSU Attendance Tracking</h2>
      <div class="header-actions">
        <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
            <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
          </svg>
          <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a href="reports.php" class="small-btn" style="padding:8px 12px;display:inline-flex;align-items:center;gap:8px"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </header>

    <div class="teachers-container">
      <div class="faculty-wrapper">
        <div class="faculty-card centered">
          <div class="faculty-title">Subjects <?php if ($year && $section) echo ' - Grade ' . htmlspecialchars($year) . ', Section ' . htmlspecialchars($section); ?></div>
          <div class="faculty-body">
            <?php if (!$year || !$section): ?>
              <p style="padding:10px;text-align:center;color:#666">Please select a grade level and section to view subjects.</p>
            <?php elseif (empty($subjects)): ?>
              <p style="padding:10px;text-align:center;color:#666">No subjects found for this grade level. Please add subjects in the Curriculum page.</p>
            <?php else: ?>
            <table class="faculty-table">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Adviser</th>
                  <th>Day</th>
                  <th>Time-In</th>
                  <th>Time-Out</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($subjects as $subject): ?>
                <?php
                  // Use teacher_id if available, otherwise fallback to assigned_teacher text
                  $teacherId = !empty($subject['teacher_id']) ? (int)$subject['teacher_id'] : null;
                  $adviser = 'Not assigned';
                  if ($teacherId && isset($teacherById[$teacherId])) {
                    $adviser = $teacherById[$teacherId];
                  }
                  
                  $subjectName = htmlspecialchars($subject['subject_name'] ?? '');
                  $adviserSafe = htmlspecialchars($adviser);
                  $day = '-';
                  if (!empty($subject['day_of_week'])) {
                    $day = htmlspecialchars($subject['day_of_week']);
                  }

                  // Format time fields (convert from 24hr to 12hr format)
                  $timeIn = '-';
                  $timeOut = '-';
                  if (!empty($subject['time_in'])) {
                    $timeIn = date('h:i A', strtotime($subject['time_in']));
                  }
                  if (!empty($subject['time_out'])) {
                    $timeOut = date('h:i A', strtotime($subject['time_out']));
                  }
                ?>
                <tr>
                  <td><?php echo $subjectName; ?></td>
                  <td><div><?php echo $adviserSafe; ?></div></td>
                  <td><?php echo $day; ?></td>
                  <td><?php echo $timeIn; ?></td>
                  <td><?php echo $timeOut; ?></td>
                  <td><a class="small-btn" href="studentclasslog.php?year=<?php echo urlencode($year); ?>&amp;section=<?php echo urlencode($section); ?>&amp;subject=<?php echo urlencode($subject['subject_name'] ?? ''); ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
