<?php
include 'db.php';

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

// load today's attendance for students in this section
$attendanceMap = [];
// ensure attendance table exists (in case no manual save has run yet)
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
if ($section && !empty($students)) {
  // build a list of student DB ids
  $ids = array_column($students, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  // prepare dynamic types string
  $types = str_repeat('i', count($ids));
  $sql = "SELECT student_id, status, time_in, time_out FROM attendance WHERE date = ? AND student_id IN ($placeholders)";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $date = date('Y-m-d');
    // bind parameters: first the date (s), then each id (i)
    $bind_params = array_merge([$date], $ids);
    $bind_types = 's' . $types;
    // use call_user_func_array for bind_param
    $refs = [];
    $refs[] = & $bind_types;
    foreach ($bind_params as $k => $v) { $refs[] = & $bind_params[$k]; }
    // php 5.3+ call_user_func_array requires references
    call_user_func_array(array($stmt, 'bind_param'), $refs);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $attendanceMap[(int)$row['student_id']] = $row;
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
  .main{flex:1;padding:20px;box-sizing:border-box}
  header{background:#b30000;color:#fff;padding:10px 20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
  table{width:100%;border-collapse:collapse;margin-top:14px;background:#fff;border-radius:6px;overflow:hidden}
  table thead{background:#f7f7f7}
  table th, table td{padding:12px;border-bottom:1px solid #f2f2f2;text-align:left}
  tr.present-row td{background:#f0fff3}
  .badge{display:inline-block;background:#28a745;color:#fff;padding:4px 8px;border-radius:999px;font-size:12px}
  .badge-late{display:inline-block;background:#ff9800;color:#fff;padding:4px 8px;border-radius:999px;font-size:12px}
  .badge-absent{display:inline-block;background:#999;color:#fff;padding:4px 8px;border-radius:999px;font-size:12px}
  .back-link{color:#fff;text-decoration:none;background:transparent;border:0}
  /* time column styling */
  td.time-cell{font-size:0.95rem;color:#444;width:170px}
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Section Attendance</h2>
      <div>
        <a class="back-link" href="dashboard.php">← Back</a>
      </div>
    </header>

    <h3 style="margin-top:12px"><?php echo htmlspecialchars($section ?: 'Unknown Section', ENT_QUOTES); ?></h3>

    <?php if (empty($section)): ?>
      <p>Please select a section from the dashboard.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Name</th><th>Student ID</th><th>Year</th><th>Time</th><th>Attendance</th></tr></thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="5" style="opacity:.6">No students found in this section.</td></tr>
          <?php else: ?>
            <?php foreach ($students as $s): 
                  $att = isset($attendanceMap[(int)$s['id']]) ? $attendanceMap[(int)$s['id']] : null;
                  $timeText = '';
                  $isPresent = false;
                  if ($att) {
                      if (!empty($att['time_in'])) $timeText = date('g:i A', strtotime($att['time_in']));
                      $isPresent = (strtolower($att['status']) === 'present');
                  }
            ?>
              <tr data-student-db-id="<?php echo (int)$s['id']; ?>">
                <td><?php echo htmlspecialchars($s['full_name'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($s['student_id'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($s['year_level'], ENT_QUOTES); ?></td>
                <td class="time-cell"><?php echo htmlspecialchars($timeText, ENT_QUOTES); ?></td>
                <td>
                  <?php
                    $statusText = ($att && !empty($att['status'])) ? $att['status'] : 'Absent';
                    $sLower = strtolower($statusText);
                    if ($sLower === 'present') {
                      echo "<span class='badge'>Present</span>";
                    } elseif ($sLower === 'late') {
                      echo "<span class='badge-late'>Late</span>";
                    } else {
                      echo "<span class='badge-absent'>Absent</span>";
                    }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <script>
    // Toggle present state locally: swaps button appearance and shows a badge
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('.mark-toggle').forEach(btn => {
        btn.addEventListener('click', function(){
          const tr = this.closest('tr');
          const timeCell = tr.querySelector('.time-cell');
          const studentDbId = tr.getAttribute('data-student-db-id') || tr.dataset.studentDbId || null;
          const section = new URLSearchParams(window.location.search).get('section') || '';
          const marked = this.classList.toggle('marked');

          if (marked) {
            // visually mark row
            tr.classList.add('present-row');
            this.textContent = 'Present';
            this.classList.add('marked');
            // set time to now (localized short time) and server time_in
            const now = new Date();
            const nowTime = now.toTimeString().split(' ')[0]; // HH:MM:SS
            timeCell.textContent = now.toLocaleTimeString();

            // persist
            const payload = new URLSearchParams();
            payload.append('student_db_id', studentDbId);
            payload.append('status', 'Present');
            payload.append('time_in', nowTime);
            payload.append('time_out', '');
            payload.append('section', section);
            fetch('crud/save_manual_attendance.php', { method: 'POST', body: payload }).catch(err => console.error('save error', err));
          } else {
            tr.classList.remove('present-row');
            this.textContent = 'Mark Present';
            this.classList.remove('marked');
            // clear time
            timeCell.textContent = '';

            // persist absence/clear
            const payload = new URLSearchParams();
            payload.append('student_db_id', studentDbId);
            payload.append('status', 'Absent');
            payload.append('time_in', '');
            payload.append('time_out', '');
            payload.append('section', section);
            fetch('crud/save_manual_attendance.php', { method: 'POST', body: payload }).catch(err => console.error('save error', err));
          }
        });
      });
    });
  </script>
</body>
</html>
