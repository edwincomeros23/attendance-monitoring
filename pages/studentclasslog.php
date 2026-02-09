<?php
require_once '../db.php';

// Inputs
$yearRaw = isset($_GET['year']) ? trim($_GET['year']) : '';
$section = isset($_GET['section']) ? trim($_GET['section']) : '';
$subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';

// Normalize grade level to support "7" and "Grade 7"
$gradeLevels = [];
if ($yearRaw !== '') {
  if (ctype_digit($yearRaw)) {
    $gradeLevels = [$yearRaw, 'Grade ' . $yearRaw];
  } elseif (stripos($yearRaw, 'Grade') === 0) {
    $gradeLevels[] = $yearRaw;
    $num = trim(str_ireplace('Grade', '', $yearRaw));
    if ($num !== '' && ctype_digit($num)) {
      $gradeLevels[] = $num;
    }
  } else {
    $gradeLevels[] = $yearRaw;
  }
}

$today = date('Y-m-d');
$dayName = date('l');
$subjectNorm = strtolower(trim($subject));

// Pull matching subject schedule (time window) if provided
$schedule = null;
if ($subject !== '' && !empty($gradeLevels)) {
  $placeholders = implode(',', array_fill(0, count($gradeLevels), '?'));
  // Accept section variants: exact, stripped numeric prefix, and "{grade}-{section}"
  $sectionVariants = [];
  if ($section !== '') {
    $sectionVariants[] = $section;
    // remove numeric prefix like "7-Ruby" -> "Ruby"
    $sectionNoNum = preg_replace('/^\d+[-\s]*/', '', $section);
    if ($sectionNoNum !== $section && $sectionNoNum !== '') $sectionVariants[] = $sectionNoNum;
    // add grade-section form if grade is numeric
    $gradeNumeric = null;
    if (ctype_digit($yearRaw)) {
      $gradeNumeric = $yearRaw;
    } elseif (stripos($yearRaw, 'Grade') === 0) {
      $num = trim(str_ireplace('Grade', '', $yearRaw));
      if ($num !== '' && ctype_digit($num)) $gradeNumeric = $num;
    }
    if ($gradeNumeric !== null && $sectionNoNum !== '') {
      $sectionVariants[] = $gradeNumeric . '-' . $sectionNoNum;
    }
  }
  if (empty($sectionVariants)) $sectionVariants[] = ''; // fallback

  $sectionPlaceholders = implode(',', array_fill(0, count($sectionVariants), '?'));
  $sql = "SELECT * FROM curriculum WHERE LOWER(TRIM(subject_name)) = LOWER(TRIM(?)) AND grade_level IN ($placeholders) AND (TRIM(section) IN ($sectionPlaceholders) OR section = '' OR section IS NULL) AND ((LOWER(day_of_week) = LOWER(?) ) OR day_of_week = '' OR day_of_week IS NULL) ORDER BY id DESC LIMIT 1";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $types = 's' . str_repeat('s', count($gradeLevels)) . str_repeat('s', count($sectionVariants)) . 's';
    $params = array_merge([$subjectNorm], $gradeLevels, $sectionVariants, [$dayName]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && ($row = $res->fetch_assoc())) {
      $schedule = $row;
    }
    $stmt->close();
  }

  // Fallback: if still no schedule, ignore day filter and take the most recent matching grade/section/subject
  if (!$schedule) {
    $sql2 = "SELECT * FROM curriculum WHERE LOWER(TRIM(subject_name)) = LOWER(TRIM(?)) AND grade_level IN ($placeholders) AND (TRIM(section) IN ($sectionPlaceholders) OR section = '' OR section IS NULL) ORDER BY id DESC LIMIT 1";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
      $types2 = 's' . str_repeat('s', count($gradeLevels)) . str_repeat('s', count($sectionVariants));
      $params2 = array_merge([$subjectNorm], $gradeLevels, $sectionVariants);
      $stmt2->bind_param($types2, ...$params2);
      $stmt2->execute();
      $res2 = $stmt2->get_result();
      if ($res2 && ($row2 = $res2->fetch_assoc())) {
        $schedule = $row2;
      }
      $stmt2->close();
    }
  }

  // Final fallback: ignore section altogether, match by subject/grade only
  if (!$schedule) {
    $sql3 = "SELECT * FROM curriculum WHERE LOWER(TRIM(subject_name)) = LOWER(TRIM(?)) AND grade_level IN ($placeholders) ORDER BY id DESC LIMIT 1";
    $stmt3 = $conn->prepare($sql3);
    if ($stmt3) {
      $types3 = 's' . str_repeat('s', count($gradeLevels));
      $params3 = array_merge([$subjectNorm], $gradeLevels);
      $stmt3->bind_param($types3, ...$params3);
      $stmt3->execute();
      $res3 = $stmt3->get_result();
      if ($res3 && ($row3 = $res3->fetch_assoc())) {
        $schedule = $row3;
      }
      $stmt3->close();
    }
  }
}

// Load students for the section
$students = [];
if ($section !== '' && !empty($gradeLevels)) {
  $placeholders = implode(',', array_fill(0, count($gradeLevels), '?'));
  $sql = "SELECT id, student_id, full_name FROM students WHERE year_level IN ($placeholders) AND section = ? ORDER BY full_name ASC";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $types = str_repeat('s', count($gradeLevels)) . 's';
    $params = array_merge($gradeLevels, [$section]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $students[] = $row;
    }
    $stmt->close();
  }
}

// Build attendance map for today filtered by section; only consider rows when a schedule exists
$attendanceMap = [];
if ($section !== '' && $schedule) {
  $attSql = "SELECT student_id, status, time_in, time_out FROM attendance WHERE date = ? AND section = ?";
  $stmt = $conn->prepare($attSql);
  if ($stmt) {
    $stmt->bind_param('ss', $today, $section);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $attendanceMap[(int)$row['student_id']] = $row;
    }
    $stmt->close();
  }
}

// If schedule exists, keep only records inside the subject window when times are present
if ($schedule && !empty($attendanceMap)) {
  $start = !empty($schedule['time_in']) ? date('H:i:s', strtotime($schedule['time_in'])) : null;
  $end = !empty($schedule['time_out']) ? date('H:i:s', strtotime($schedule['time_out'])) : null;
  if ($start && $end) {
    foreach ($attendanceMap as $sid => $att) {
      $in = $att['time_in'] ?? null;
      $out = $att['time_out'] ?? null;
      $inWindow = ($in && $in >= $start && $in <= $end);
      $outWindow = ($out && $out >= $start && $out <= $end);
      if (!($inWindow || $outWindow)) {
        unset($attendanceMap[$sid]);
      }
    }
  } else {
    // No concrete time window -> treat as absent for this subject
    $attendanceMap = [];
  }
}

// Pre-format schedule display
$scheduleInDisplay = '-';
$scheduleOutDisplay = '-';
if ($schedule) {
  if (!empty($schedule['time_in'])) $scheduleInDisplay = date('h:i A', strtotime($schedule['time_in']));
  if (!empty($schedule['time_out'])) $scheduleOutDisplay = date('h:i A', strtotime($schedule['time_out']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Student Class Log</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
  <style>
    header { background:#b30000; color:#fff; padding:10px 20px; border-radius:8px; display:flex; justify-content:space-between; align-items:center }
    .main { padding:20px }
    .card { background:#fff;padding:16px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05) }
    .small-btn{display:inline-flex;align-items:center;gap:8px;background:#b30000;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none}
    /* Class log table column alignment rules */
    #class-log { width: 100%; border-collapse: collapse; font-size: 14px; table-layout: auto; }
    #class-log th, #class-log td { padding:8px; border-bottom:1px solid #eee }
    /* Column-specific alignment: ID & Name left, Status center, Times center, Remarks left */
    #class-log th:nth-child(1), #class-log td:nth-child(1),
    #class-log th:nth-child(2), #class-log td:nth-child(2),
    #class-log th:nth-child(6), #class-log td:nth-child(6) {
      text-align: left;
    }
    #class-log th:nth-child(3), #class-log td:nth-child(3),
    #class-log th:nth-child(4), #class-log td:nth-child(4),
    #class-log th:nth-child(5), #class-log td:nth-child(5) {
      text-align: center;
    }
    /* Column widths to match livecamera.php */
    #class-log th:nth-child(1), #class-log td:nth-child(1) { width: 110px; }
    #class-log th:nth-child(3), #class-log td:nth-child(3) { width: 110px; }
    #class-log th:nth-child(4), #class-log td:nth-child(4),
    #class-log th:nth-child(5), #class-log td:nth-child(5) { width: 120px; }
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
        <a href="subjects.php?year=<?php echo urlencode($yearRaw); ?>&amp;section=<?php echo urlencode($section); ?>" class="small-btn" style="padding:8px 12px;display:inline-flex;align-items:center;gap:8px"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </header>
    <!-- Use the same wider container style as livecamera.php so the card
         fills the page content area instead of being a tiny box. -->
    <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05);position:relative;">
      <h2 style="margin:0 0 8px 0;color:#b71c1c">Class Log</h2>
      <p style="margin:0 0 4px 0"><strong>Grade:</strong> <?php echo htmlspecialchars($yearRaw) ?: '(not specified)'; ?> &nbsp; <strong>Section:</strong> <?php echo htmlspecialchars($section) ?: '(not specified)'; ?> &nbsp; <strong>Subject:</strong> <?php echo htmlspecialchars($subject) ?: '(not specified)'; ?></p>
      <p style="margin:0 0 8px 0;color:#555"><strong>Schedule:</strong> <?php echo htmlspecialchars($scheduleInDisplay); ?> — <?php echo htmlspecialchars($scheduleOutDisplay); ?></p>
      <div id="class-log-scroll" style="overflow:auto;max-height:420px;margin-top:8px;padding-bottom:0">
        <table id="class-log" style="width:100%;border-collapse:collapse;font-size:14px">
          <thead style="background:#f7f7f7"><tr><th style="padding:8px;border-bottom:1px solid #eee">Student ID</th><th style="padding:8px;border-bottom:1px solid #eee">Name</th><th style="padding:8px;border-bottom:1px solid #eee">Status</th><th style="padding:8px;border-bottom:1px solid #eee">Time-In</th><th style="padding:8px;border-bottom:1px solid #eee">Time-Out</th><th style="padding:8px;border-bottom:1px solid #eee">Remarks</th></tr></thead>
          <tbody>
            <?php
              if (empty($gradeLevels) || $section === '') {
                echo '<tr><td colspan="6" style="padding:10px;text-align:center;color:#666">Specify grade and section to view class log.</td></tr>';
              } elseif (empty($students)) {
                echo '<tr><td colspan="6" style="padding:10px;text-align:center;color:#666">No students found for this grade/section.</td></tr>';
              } else {
                foreach ($students as $stu) {
                  $dbId = (int)$stu['id'];
                  $sid = htmlspecialchars($stu['student_id'] ?: '');
                  $name = htmlspecialchars($stu['full_name'] ?: '');
                  $att = isset($attendanceMap[$dbId]) ? $attendanceMap[$dbId] : null;
                  $status = $att && !empty($att['status']) ? htmlspecialchars($att['status']) : 'Absent';
                  $statusColor = ($status === 'Present') ? 'color:green' : 'color:#999';
                  $timeIn = ($att && !empty($att['time_in'])) ? date('h:i A', strtotime($att['time_in'])) : '-';
                  $timeOut = ($att && !empty($att['time_out'])) ? date('h:i A', strtotime($att['time_out'])) : '-';
                  $remarks = $att ? 'Detected' : 'Not yet detected';
                  echo '<tr data-student-db-id="'.$dbId.'">';
                  echo '<td style="padding:8px;border-bottom:1px solid #eee">'.$sid.'</td>';
                  echo '<td style="padding:8px;border-bottom:1px solid #eee">'.$name.'</td>';
                  echo '<td style="padding:8px;'.$statusColor.';border-bottom:1px solid #eee">'.$status.'</td>';
                  echo '<td class="time-in" style="padding:8px;border-bottom:1px solid #eee">'.$timeIn.'</td>';
                  echo '<td class="time-out" style="padding:8px;border-bottom:1px solid #eee">'.$timeOut.'</td>';
                  echo '<td style="padding:8px;border-bottom:1px solid #eee">'.$remarks.'</td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </div>
    </div>
</body>
</html>
