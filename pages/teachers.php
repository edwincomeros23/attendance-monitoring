<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /attendance-monitoring/auth/signin.php");
    exit;
}
// Admin-only page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /attendance-monitoring/pages/dashboard.php");
    exit;
}
require_once '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WMSU Attendance Tracking</title>
  <link rel="icon" type="image/png" href="../wmsulogo_circular.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css" />
</head>
<body>
  <?php include '../sidebar.php'; ?>
  <?php require_once '../db.php'; ?>
  <div class="main">
    <header>
      <h2>Teachers</h2>
      <div class="header-actions">
        <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
            <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
          </svg>
          <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <div class="admin-info">
          <div class="admin-icon">👤 Admin</div>
        </div>
      </div>
    </header>
    <!-- teachers UI -->
    <div class="teachers-container">
      <div class="teachers-tools">
        <div class="tabs">
          <button class="tab active">Faculty List</button>
          <button class="tab">Teacher's Schedule</button>
        </div>
        <div class="top-controls">
          <div class="search-box">
            <i class="fa fa-search"></i>
            <input id="search" placeholder="Search Teacher" />
          </div>
          <div class="export-btns">
            <button class="btn small">PDF</button>
            <button class="btn small">Excel</button>
          </div>
        </div>
      </div>

      <div class="faculty-wrapper">
        <div class="faculty-card centered">
          <h3 class="faculty-title">Faculty List</h3>
          <div class="faculty-body">
            <table class="faculty-table" id="facultyTable">
            <thead>
              <tr>
                <th>Teacher Name</th>
                <th>Faculty ID</th>
                <th>Department</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
<?php
  $res = $conn->query("SELECT id, faculty_id, first_name, middle_initial, last_name, email, status, department FROM teachers ORDER BY id DESC");
  if ($res && $res->num_rows > 0) {
      while ($row = $res->fetch_assoc()) {
          $fullname = htmlspecialchars($row['first_name'] . ($row['middle_initial'] ? ' ' . $row['middle_initial'] : '') . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8');
          $fid = htmlspecialchars($row['faculty_id'], ENT_QUOTES, 'UTF-8');
          $dept = htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8');
          $email = htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8');
          $status = htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8');
          $id = (int)$row['id'];
          echo "<tr data-id=\"{$id}\">\n";
          echo "  <td>{$fullname}</td>\n";
          echo "  <td>{$fid}</td>\n";
          echo "  <td>{$dept}</td>\n";
          echo "  <td>{$email}</td>\n";
          echo "  <td>{$status}</td>\n";
          echo "  <td><button class='btn edit'>Edit</button> <button class='btn deactivate'>Deactivate</button></td>\n";
          echo "</tr>\n";
      }
  } else {
      echo "<tr><td colspan=\"6\" style=\"opacity:.6\">No teachers found</td></tr>\n";
  }
?>
            </tbody>
            </table>
          </div>
        </div>
        <!-- Teacher Schedule card (hidden by default) -->
        <div class="faculty-card centered" id="scheduleCard" style="display:none;">
          <h3 class="faculty-title">Teacher's Schedule</h3>
          <div class="faculty-body">
            <table class="faculty-table" id="teacherScheduleTable">
              <thead>
                <tr>
                  <th>Teacher</th>
                  <th>Subject</th>
                  <th>Grade & Section</th>
                  <th>Day</th>
                  <th>Time</th>
                  <th>Room</th>
                </tr>
              </thead>
              <tbody>
<?php
// Load teacher schedules from curriculum
$scheduleQuery = "SELECT 
    CONCAT(t.first_name, ' ', IFNULL(CONCAT(t.middle_initial, '. '), ''), t.last_name) as teacher_name,
    c.subject_name,
    c.grade_level,
    c.section,
    c.day_of_week,
    c.time_in,
    c.time_out,
    c.room
FROM curriculum c
INNER JOIN teachers t ON c.teacher_id = t.id
WHERE c.teacher_id IS NOT NULL 
    AND c.day_of_week IS NOT NULL
    AND c.time_in IS NOT NULL
ORDER BY t.last_name, t.first_name, 
    FIELD(c.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
    c.time_in";

$scheduleRes = $conn->query($scheduleQuery);
if ($scheduleRes && $scheduleRes->num_rows > 0) {
    while ($sched = $scheduleRes->fetch_assoc()) {
        $teacherName = htmlspecialchars($sched['teacher_name'], ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($sched['subject_name'], ENT_QUOTES, 'UTF-8');
        $gradeSection = htmlspecialchars($sched['grade_level'], ENT_QUOTES, 'UTF-8');
        if (!empty($sched['section'])) {
            $gradeSection .= ' - ' . htmlspecialchars($sched['section'], ENT_QUOTES, 'UTF-8');
        }
        $day = htmlspecialchars($sched['day_of_week'], ENT_QUOTES, 'UTF-8');
        
        $timeIn = date('h:i A', strtotime($sched['time_in']));
        $timeOut = date('h:i A', strtotime($sched['time_out']));
        $time = $timeIn . ' - ' . $timeOut;
        
        $room = !empty($sched['room']) ? htmlspecialchars($sched['room'], ENT_QUOTES, 'UTF-8') : '-';
        
        echo "<tr>\n";
        echo "  <td>{$teacherName}</td>\n";
        echo "  <td>{$subject}</td>\n";
        echo "  <td>{$gradeSection}</td>\n";
        echo "  <td>{$day}</td>\n";
        echo "  <td>{$time}</td>\n";
        echo "  <td>{$room}</td>\n";
        echo "</tr>\n";
    }
} else {
    echo "<tr><td colspan=\"6\" style=\"opacity:.6; text-align:center; padding:40px\">No schedules assigned yet. Use Curriculum page to assign teachers to subjects.</td></tr>\n";
}
?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Edit Teacher Modal -->
      <div id="editTeacherModal" class="modal" aria-hidden="true">
        <div class="modal-content small">
          <button class="modal-close" aria-label="Close edit dialog">×</button>
          <h3>Edit Teacher Details</h3>
          <form id="editTeacherForm">
            <div class="form-grid">
              <label>Faculty ID<input type="text" id="et-faculty-id" required></label>
              <label>First Name<input type="text" id="et-first-name" required></label>
              <label>Middle Initial<input type="text" id="et-mi" placeholder="optional"></label>
              <label>Last Name<input type="text" id="et-last-name" required></label>
              <label>Email<input type="email" id="et-email" required></label>
              <label>Status
                <select id="et-status">
                  <option>Active</option>
                  <option>Inactive</option>
                </select>
              </label>
              <label>Department<input type="text" id="et-dept"></label>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn cancel" id="et-cancel">Cancel</button>
              <button type="submit" class="btn save">Save</button>
            </div>
          </form>
        </div>
      </div>

      <button class="add-teacher btn primary" id="openAddTeacher">+ Add Teacher</button>
    </div>

    <style>
      /* Ensure page main/header match camera.php */
      .main { flex: 1; padding: 20px; box-sizing: border-box; }
      header { background-color: #b30000; color: white; padding: 10px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
      /* Modal base (scoped for teachers page) */
      .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1200; align-items: center; justify-content: center; }
      .modal[aria-hidden="false"] { display: flex; }
      #addTeacherModal { z-index: 1250; }
      .modal-content.small { width: 92%; max-width: 760px; background: #fff; border-radius: 6px; padding: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.12); }
      .modal-content h3 { margin-top: 0; }
      .modal-close { position: absolute; right: 14px; top: 10px; background: transparent; border: none; font-size: 22px; cursor: pointer; }
  #addTeacherModal .modal-close { position: absolute; right: 14px; top: 10px; background: transparent; border: none; font-size: 22px; cursor: pointer; }
      .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 12px; }
      .form-grid label { display: flex; flex-direction: column; font-weight: 600; font-size: 13px; }
      .form-grid input, .form-grid select { margin-top: 6px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; }
      .modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 18px; }
      .btn.save { background: #b30000; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; }
      .btn.cancel { background: #f1f1f1; border: 1px solid #ddd; padding: 8px 14px; border-radius: 6px; cursor: pointer; }
      @media (max-width:720px){ .form-grid{grid-template-columns:1fr} .modal-content.small{padding:16px} }
  /* Edit button styles */
  .btn.edit { background: #fff; border: 1px solid #b30000; color: #b30000; padding: 6px 10px; border-radius: 5px; cursor: pointer; transition: background .12s, color .12s, transform .08s; }
  .btn.edit:hover, .btn.edit:focus { background: #b30000; color: #fff; transform: translateY(-1px); outline: none; box-shadow: 0 6px 18px rgba(179,0,0,0.12); }
  /* Add teacher button */
  .add-teacher.btn.primary { background: #b30000; color: #fff; border: none; padding: 10px 14px; border-radius: 6px; cursor: pointer; font-weight: 700; box-shadow: 0 8px 20px rgba(179,0,0,0.12); transition: transform .12s, box-shadow .12s; }
  .add-teacher.btn.primary:hover, .add-teacher.btn.primary:focus { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(179,0,0,0.16); }

  /* Add Teacher Modal specific (prevent overflow) */
  #addTeacherModal .modal-content.large{
    width:92%;
    max-width:760px;
    padding:18px;
    background:#fff;
    border-radius:6px;
    box-shadow:0 10px 40px rgba(0,0,0,0.12);
    position:relative;
    box-sizing:border-box;
    overflow:hidden;
  }
  #addTeacherModal .form-grid-2{
    display:grid;
    grid-template-columns: repeat(4,minmax(0,1fr));
    gap:10px;
    align-items:start;
  }
  #addTeacherModal .form-grid-2 label{ display:flex; flex-direction:column; font-weight:600; font-size:13px; }
  #addTeacherModal .form-grid-2 input, #addTeacherModal .form-grid-2 select{ margin-top:6px; padding:8px 10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; width:100%; }
  @media(max-width:900px){ #addTeacherModal .form-grid-2{grid-template-columns:1fr 1fr} }
  /* schedule row inside add modal */
  #addTeacherModal .schedule-row{ display:grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap:10px; margin-top:6px; align-items:end; grid-column: 1 / -1; }
  #addTeacherModal .schedule-row label{ display:flex; flex-direction:column; font-weight:600; font-size:13px }
  #addTeacherModal .schedule-row input{ margin-top:6px; padding:8px 10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; width:100% }
  #addTeacherModal .schedule-row > div { display:flex; align-items:center; justify-content:center; }
  #addTeacherModal .schedule-row button#at-add-schedule{ padding:6px 10px; border-radius:6px; background:#eee; border:1px solid #ccc; cursor:pointer }
  @media(max-width:900px){ #addTeacherModal .schedule-row{ grid-template-columns: 1fr 1fr; } }
  /* ensure inputs don't overflow modal */
  #addTeacherModal input, #addTeacherModal select { box-sizing: border-box; width: 100%; }
    </style>

    <script>
      (function(){
        const table = document.getElementById('facultyTable');
        const modal = document.getElementById('editTeacherModal');
        const form = document.getElementById('editTeacherForm');
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = document.getElementById('et-cancel');
        let currentRow = null;

        // Delegate click for Edit buttons
        table.addEventListener('click', (e)=>{
          const btn = e.target.closest('button');
          if(!btn) return;
          if(btn.classList.contains('edit')){
            currentRow = btn.closest('tr');
            openModalWithRow(currentRow);
          }
        });

        function openModalWithRow(row){
          // prefill fields
          const cells = row.querySelectorAll('td');
          document.getElementById('et-faculty-id').value = cells[1]?.textContent.trim() || '';
          // assume name cell contains full name; split into parts
          const fullname = (cells[0]?.textContent || '').trim();
          const parts = fullname.split(' ');
          document.getElementById('et-first-name').value = parts[0] || '';
          document.getElementById('et-last-name').value = parts.slice(1).join(' ') || '';
          document.getElementById('et-email').value = cells[3]?.textContent.trim() || '';
          document.getElementById('et-status').value = cells[4]?.textContent.trim() || 'Active';
          document.getElementById('et-dept').value = cells[2]?.textContent.trim() || '';

          modal.setAttribute('aria-hidden','false');
          // focus first input for accessibility
          document.getElementById('et-faculty-id').focus();
        }

        function closeModal(){
          modal.setAttribute('aria-hidden','true');
          currentRow = null;
        }

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });

        form.addEventListener('submit',(e)=>{
          e.preventDefault();
          if(!currentRow) return closeModal();
          const fid = document.getElementById('et-faculty-id').value.trim();
          const fname = document.getElementById('et-first-name').value.trim();
          const lname = document.getElementById('et-last-name').value.trim();
          const email = document.getElementById('et-email').value.trim();
          const status = document.getElementById('et-status').value.trim();
          const dept = document.getElementById('et-dept').value.trim();

          // update table cells (UI-only)
          const cells = currentRow.querySelectorAll('td');
          cells[0].textContent = (fname + (lname? ' ' + lname : '')).trim();
          cells[1].textContent = fid;
          cells[2].textContent = dept;
          cells[3].textContent = email;
          cells[4].textContent = status;

          closeModal();
        });
      })();
    </script>

    <script>
      // Tab switching for Faculty List / Teacher's Schedule
      (function(){
        const tabs = document.querySelectorAll('.tabs .tab');
        const facultyCard = document.querySelector('.faculty-wrapper .faculty-card');
        const scheduleCard = document.getElementById('scheduleCard');

        function showFaculty(){
          tabs[0].classList.add('active');
          tabs[1].classList.remove('active');
          facultyCard.style.display = '';
          if (scheduleCard) scheduleCard.style.display = 'none';
        }

        function showSchedule(){
          tabs[1].classList.add('active');
          tabs[0].classList.remove('active');
          facultyCard.style.display = 'none';
          if (scheduleCard) scheduleCard.style.display = '';
        }

        tabs.forEach((t, idx) => {
          t.addEventListener('click', (e) => {
            e.preventDefault();
            if (idx === 0) showFaculty(); else showSchedule();
          });
        });
      })();
    </script>

    <!-- Add Teacher Modal -->
    <div id="addTeacherModal" class="modal" aria-hidden="true">
      <div class="modal-content large">
        <button class="modal-close" aria-label="Close add teacher dialog">×</button>
        <h3>Add Teacher:</h3>
        <form id="addTeacherForm">
          <div class="form-grid-2">
            <label>Faculty ID<input id="at-faculty-id" required></label>
            <label>First Name<input id="at-first-name" required></label>
            <label>Middle Initial<input id="at-mi"></label>
            <label>Last Name<input id="at-last-name" required></label>
            <label>Email<input id="at-email" type="email" required></label>
            <label>Status<select id="at-status"><option>Active</option><option>Inactive</option></select></label>
            <label>Department<input id="at-dept"></label>
            <div class="schedule-row">
              <label>Subject<input id="at-subject"></label>
              <label>Subject Code<input id="at-subject-code"></label>
              <label>Time<input id="at-time"></label>
              <label>Day<input id="at-day"></label>
              <div style="display:flex;align-items:center;justify-content:center"><button type="button" class="btn" id="at-add-schedule">+</button></div>
            </div>
          </div>
          <div style="height:10px"></div>
          <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:8px">
            <button type="button" class="btn cancel" id="at-cancel">Cancel</button>
            <button type="submit" class="btn save">Save</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      (function(){
        const openBtn = document.getElementById('openAddTeacher');
        const modal = document.getElementById('addTeacherModal');
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = document.getElementById('at-cancel');
        const form = document.getElementById('addTeacherForm');
        const tableBody = document.querySelector('#facultyTable tbody');

        function open(){ modal.setAttribute('aria-hidden','false'); document.getElementById('at-faculty-id').focus(); }
        function close(){ modal.setAttribute('aria-hidden','true'); }

        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        cancelBtn.addEventListener('click', close);
        modal.addEventListener('click',(e)=>{ if(e.target===modal) close(); });

        form.addEventListener('submit',(e)=>{
          e.preventDefault();
          const fid = document.getElementById('at-faculty-id').value.trim();
          const fname = document.getElementById('at-first-name').value.trim();
          const mi = document.getElementById('at-mi').value.trim();
          const lname = document.getElementById('at-last-name').value.trim();
          const email = document.getElementById('at-email').value.trim();
          const status = document.getElementById('at-status').value.trim();
          const dept = document.getElementById('at-dept').value.trim();

          // POST to server
          const formData = new FormData();
          formData.append('faculty_id', fid);
          formData.append('first_name', fname);
          formData.append('middle_initial', mi);
          formData.append('last_name', lname);
          formData.append('email', email);
          formData.append('status', status);
          formData.append('department', dept);

          fetch('addteacher.php', { method: 'POST', body: formData })
            .then(async (r) => {
              if (!r.ok) {
                const txt = await r.text();
                let parsed;
                try { parsed = JSON.parse(txt); } catch(e) { parsed = null; }
                const serverMsg = parsed?.error || parsed?.errors?.join(', ') || txt || r.statusText;
                throw new Error('Server error '+r.status+': '+serverMsg);
              }
              return r.json();
            })
            .then(data => {
              if (!data.success) {
                console.error('Add teacher failed', data);
                alert('Failed to add teacher: ' + (data.error || (data.errors && data.errors.join(', ')) || 'Unknown'));
                return;
              }

              const row = data.row;
              const tr = document.createElement('tr');
              tr.dataset.id = row.id;
              tr.innerHTML = `<td>${row.first_name}${row.last_name? ' '+row.last_name : ''}</td><td>${row.faculty_id}</td><td>${row.department}</td><td>${row.email}</td><td>${row.status}</td><td><button class="btn edit">Edit</button> <button class="btn deactivate">Deactivate</button></td>`;
              tableBody.insertBefore(tr, tableBody.firstChild);
              form.reset();
              close();
            })
            .catch(err => {
              console.error('Add teacher error', err);
              alert('Error adding teacher: ' + err.message);
            });
        });
      })();
    </script>

    <script src="script.js"></script>

