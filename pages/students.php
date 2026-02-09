<?php
// Ensure user is logged in and sessions are available before rendering page
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  header('Location: /attendance-monitoring/auth/signin.php');
  exit;
}

include '../db.php'; // database connection

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';
$displayRole = ucfirst($userRole);
$userLabel = ($displayRole === 'Admin') ? 'Admin' : $displayRole;
$isAdmin = ($userRole === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Students — WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css" />
  <style>
  body { font-family: Arial, Helvetica, sans-serif; background:#f5f5f5; margin:0; display:flex; }
  /* match dashboard main sizing and spacing */
  /* match camera.php layout: larger content padding and header sizing */
  .main { flex: 1; padding: 20px; box-sizing: border-box; }
    header { background-color: #b30000; color: #fff; padding: 10px 20px; border-radius: 8px; display:flex; justify-content:space-between; align-items:center; }
  /* clear margin and inherit sizing from global styles so header matches other pages */
  header h2 { margin:0; font-weight:700; }
    .g7container { background:#fff; padding:18px; margin-top: 15px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .g7header { background:#b30000; color:#fff; padding:10px 14px; border-radius:6px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; text-transform:uppercase; }
    table { width:100%; border-collapse:collapse; margin:10px 0 18px; }
    table thead th { text-align:left; padding:10px; background:#f2f2f2; }
    table td { padding:10px; border-top:1px solid #eee; vertical-align:middle; }
    .download-btn { background:#b30000; color:#fff; padding:8px 14px; border-radius:6px; text-decoration:none; display:inline-block; cursor:pointer; }
    .btn-green { background:#28a745;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none; }
    .btn-red { background:#dc3545;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none; }
    .thumbnail { width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #ddd; }

    /* modal */
    .modal { display:none; position:fixed; z-index:1200; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.55); justify-content:center; align-items:center; padding:20px; overflow:auto; }
    .modal.open { display:flex; }
    .modal-box { background:#fff; width:100%; max-width:920px; border-radius:10px; padding:18px; box-shadow:0 8px 30px rgba(0,0,0,0.25); position:relative; }
    .modal-close { position:absolute; right:14px; top:10px; border:none; background:transparent; font-size:20px; cursor:pointer; color:#b30000; }
    .modal-title { color:#b30000; font-size:20px; font-weight:700; text-align:left; margin-bottom:8px; }

    /* form */
    .form-layout { display:flex; gap:18px; flex-wrap:wrap; }
    .left-section, .right-section { flex:1; min-width:260px; }
    .avatar-box { text-align:center; }
    .avatar-box img { width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid #eee; display:inline-block; }
    .upload-btn { display:inline-block; margin-top:12px; background:#b30000; color:#fff; padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
    form label { display:block; margin-top:10px; font-weight:600; }
    form input, form select { width:100%; padding:8px 10px; border-radius:6px; border:1px solid #ccc; margin-top:6px; }
    .form-actions { display:flex; justify-content:flex-end; gap:12px; margin-top:16px; }
    .btn-cancel, .btn-save { padding:8px 14px; border-radius:6px; border:none; cursor:pointer; }
    .btn-cancel { background:#ddd; }
    .btn-save { background:#b30000; color:#fff; }

    .small-btn { background:#b30000; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; }
    /* Match reports.php sections table styles */
    table.sections-table { width:100%; border-collapse:collapse; font-size:14px }
    table.sections-table th { text-align:left; padding:8px; border-bottom:1px solid #eee; background:#f7f7f7 }
    table.sections-table th.action { text-align:right; }
    table.sections-table td { padding:8px; border-bottom:1px solid #eee; text-align:left }
    table.sections-table td.action { text-align:right }
    .delete-btn { display:inline-flex; align-items:center; gap:6px; background:#b30000; color:#fff; padding:6px 10px; border-radius:6px; border:0; cursor:pointer; font-weight:600 }
    .delete-btn:hover { background:#c0392b }
    .small-btn { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; background: #b30000; color: #fff !important; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; box-shadow: 0 4px 10px rgba(179,0,0,0.12); transition: transform .12s ease, box-shadow .12s ease, background .12s ease; }
    .small-btn:hover { background: #990000; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.12); }
  </style>
</head>
<body>
<?php if(file_exists('../sidebar.php')) include '../sidebar.php'; ?>

<div class="main">
  <header>
    <h2>Wmsu Attendance Tracking</h2>
    <div class="header-actions">
      <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
          <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
        </svg>
        <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
      <div class="admin-info">👤 <?php echo htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
  </header>

  <div class="g7container">
    <div class="g7header">
      <div>Grade 7 Sections</div>
      <div>S.Y 2025-2026</div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;">Students Profile</h3>
    </div>

    <?php
    // Build a grouped view: year_level -> sections
    $yearsRes = $conn->query("SELECT year_level FROM students GROUP BY year_level ORDER BY year_level");
    if ($yearsRes && $yearsRes->num_rows > 0) {
      while ($yr = $yearsRes->fetch_assoc()) {
        $year = htmlspecialchars($yr['year_level']);
        // Skip Grade 12 per request
        if ($year === '12') continue;
        echo "<div style='background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;box-shadow:0 2px 6px rgba(0,0,0,0.04)'>";
  echo "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:6px'>";
  echo "<strong> " . $year . "</strong>";
  echo "</div>";

        // sections for this year
        $secRes = $conn->query("SELECT section, COUNT(*) as cnt FROM students WHERE year_level = '" . $conn->real_escape_string($year) . "' GROUP BY section ORDER BY section");
        echo "<table class='sections-table'>";
        echo "<thead><tr>";
        echo "<th style='text-align:left;padding:8px;border-bottom:1px solid #eee'>Section</th>";
        echo "<th style='text-align:left;padding:8px;border-bottom:1px solid #eee'>Time</th>";
        echo "<th class='action' style='text-align:right;padding:8px;border-bottom:1px solid #eee'>Action</th>";
        echo "<th class='action' style='text-align:right;padding:8px;border-bottom:1px solid #eee'>Reports</th>";
        echo "</tr></thead>";
        echo "<tbody>";
        while ($s = $secRes->fetch_assoc()) {
          $sectionName = htmlspecialchars($s['section']);
          $encSection = urlencode($s['section']);
          $encYear = urlencode($year);
          echo "<tr>";
          echo "<td>" . $sectionName . "</td>";
          echo "<td class='time-cell' data-year='" . $encYear . "' data-section='" . $encSection . "'></td>";
          echo "<td class='action' style='text-align:right'>";
          if ($isAdmin) {
            echo "<button class='delete-btn' onclick=\"if(confirm('Delete section ' + '" . addslashes($sectionName) . "' + '?')){window.location='deletesection.php?year=" . $encYear . "&section=" . $encSection . "'}\">Delete</button>";
          } else {
            echo "<span style='opacity:.6'>—</span>";
          }
          echo "</td>";
          echo "<td class='action' style='text-align:right'><a class='small-btn' href='students_list.php?year=" . $encYear . "&section=" . $encSection . "' style='text-decoration:none'>View Reports</a></td>";
          echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
      }
    } else {
      echo "<div>No students found.</div>";
    }
    ?>

    <!-- Add Section button removed -->
  </div>

  <!-- Grade 8 UI (frontend only) -->
  <div class="g7container" style="margin-top:18px;">
    <div class="g7header">
      <div>Grade 8 Sections</div>
      <div>S.Y 2025-2026</div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;">Students Profile</h3>
    </div>

    <table class="sections-table">
      <thead><tr><th>Section</th><th>Time</th><th class="action">Action</th><th class="action">Reports</th></tr></thead>
      <tbody>
        <tr><td style="padding:8px;border-bottom:1px solid #eee">Emerald</td><td class='time-cell' style="padding:8px;border-bottom:1px solid #eee">--:--:--</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right"><?php if ($isAdmin): ?><button class='delete-btn'>Delete</button><?php else: ?><span style='opacity:.6'>—</span><?php endif; ?></td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right"><a class='small-btn' href='#' style='text-decoration:none'>View Reports</a></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee">Topaz</td><td class='time-cell' style="padding:8px;border-bottom:1px solid #eee">--:--:--</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right"><?php if ($isAdmin): ?><button class='delete-btn'>Delete</button><?php else: ?><span style='opacity:.6'>—</span><?php endif; ?></td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right"><a class='small-btn' href='#' style='text-decoration:none'>View Reports</a></td></tr>
      </tbody>
    </table>

  </div>
</div>

<div id="students-list" style="margin:14px 20px;"> <!-- AJAX-inserted students list will appear here -->
</div>

<!-- ADD STUDENT MODAL -->
<div id="addStudentModal" class="modal" aria-hidden="true" role="dialog">
  <div class="modal-box" role="document">
    <button class="modal-close" data-close>&times;</button>
    <div class="modal-title">Add Student</div>

    <form id="addStudentForm" method="POST" action="savestudent.php" enctype="multipart/form-data">
      <input type="hidden" name="id" id="studentIdField">
      <div class="form-layout">
        <div class="left-section">
          <div class="avatar-box">
            <img id="mainAvatarPreview" src="../students/default-avatar.png" alt="avatar preview">
          </div>
          <input type="file" name="photo1" id="photo1Main" accept="image/*">
        </div>

        <div class="right-section">
          <label>Student ID</label>
          <input id="student_id" type="text" name="student_id" required>
          <label>Full Name</label>
          <input id="full_name" type="text" name="full_name" required>
          <label>Birthdate</label>
          <input id="birthdate" type="date" name="birthdate" required>
          <label>Gender</label>
          <select id="gender" name="gender" required>
            <option value="">--Select--</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
          <label>Year Level</label>
          <select id="year_level" name="year_level" required>
            <option value="">--Select--</option>
            <option value="Grade 7">Grade 7</option>
            <option value="Grade 8">Grade 8</option>
          </select>
          <label>Section</label>
          <input id="section" type="text" name="section" required>
          <label>Guardian / Parent</label>
          <input id="guardian" type="text" name="guardian" required>
          <label>Phone No.</label>
          <input id="phone_no" type="text" name="phone_no" required>
          <label>Guardian Email</label>
          <input id="guardian_email" type="email" name="guardian_email" placeholder="guardian@email.com">
          <div class="form-actions">
            <button type="button" class="btn-cancel" data-close>Cancel</button>
            <button type="submit" class="btn-save">Save Student</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // update time cells inside tables (matches header time format)
  function updateTimeCells() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString(undefined, { hour12: true });
    document.querySelectorAll('.time-cell').forEach(function(td){ td.textContent = timeStr; });
  }
  updateTimeCells();
  setInterval(updateTimeCells, 1000);
});
</script>
</body>
</html>
