<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css" />
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f8f9fa; }
    header {
      background-color: #b30000; color: white; padding: 8px 14px;
      border-radius: 6px; display: flex; justify-content: space-between; align-items: center;
      height: 52px; box-sizing: border-box;
    }
    .manual-attendance-container {
      background: white;
      padding: 2px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-top: 5px;
    }
    .manual-attendance-header {
      background-color: #b30000;
      color: white;
      padding: 8px 10px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 6px 6px 0 0;
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    table thead { background: #f2f2f2; }
    table th, table td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: center;
    }
    .btn-green {
      background-color: #28a745;
      color: white;
      padding: 6px 12px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
    }
    .btn-red {
      background-color: #dc3545;
      color: white;
      padding: 6px 12px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
    }
    .download-btn {
      background-color: #b30000;
      color: white;
      padding: 8px 16px;
      border-radius: 5px;
      text-decoration: none;
      display: inline-block;
      margin: 15px;
    }
    .download-btn:hover { background-color: #990000; }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      position: relative;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .close-btn {
      position: absolute;
      top: 10px; right: 15px;
      font-size: 20px;
      cursor: pointer;
      color: #666;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
    .form-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .status-select { 
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
      background: white;
      appearance: menulist;
    }
    .save-btn {
      background: #990000;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
    }
    .back-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background-color: #b30000;
  color: white;
  padding: 0;
  border-radius: 50%;
  text-decoration: none;
  margin-top: 8px;
  font-size: 16px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  transition: transform .12s ease, box-shadow .12s ease, background-color .12s;
}
.back-btn i { font-size: 18px; line-height: 1; }
.back-btn:hover {
  background-color: #990000;
  transform: translateY(-2px);
  box-shadow: 0 10px 26px rgba(0,0,0,0.12);
}
.back-btn:focus {
  outline: none;
  box-shadow: 0 0 0 4px rgba(179,0,0,0.12);
}

  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Manual Attendance</h2>
      <div style="display:flex;align-items:center;gap:12px">
        <a href="livecamera.php" class="back-btn" role="button" aria-label="Back to live camera" title="Back to live camera" tabindex="0">
          <i class="fa fa-arrow-left" aria-hidden="true"></i>
        </a>
      </div>
    </header>
    <div class="manual-attendance-container">
      <div class="manual-attendance-header">Manual Attendance</div>
      <table id="class-log-table">
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // load DB and students for the class log
        include_once 'db.php';

        // ensure attendance table exists
        $createSql = "CREATE TABLE IF NOT EXISTS attendance (
          id INT AUTO_INCREMENT PRIMARY KEY,
          student_id INT NOT NULL,
          section VARCHAR(100) DEFAULT '',
          date DATE NOT NULL,
          status VARCHAR(50) DEFAULT '',
          time_in TIME DEFAULT NULL,
          time_out TIME DEFAULT NULL,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uniq_student_date (student_id,date)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4";
        $conn->query($createSql);

        // optional: filter by section if provided via GET
        $sectionFilter = '';
        if (!empty($_GET['section'])) {
          $sectionFilter = " WHERE section = '" . $conn->real_escape_string($_GET['section']) . "'";
        }

        $studentsSql = "SELECT * FROM students" . $sectionFilter . " ORDER BY section, full_name";
        $studentsRes = $conn->query($studentsSql);

        // fetch today's attendance map
        $attendanceMap = [];
        $attRes = $conn->query("SELECT * FROM attendance WHERE date = CURDATE()");
        if ($attRes) {
          while ($a = $attRes->fetch_assoc()) {
            $attendanceMap[intval($a['student_id'])] = $a;
          }
        }

        if ($studentsRes && $studentsRes->num_rows > 0) {
          while ($s = $studentsRes->fetch_assoc()) {
            $sid = intval($s['id']);
            $studNo = !empty($s['student_id']) ? htmlspecialchars($s['student_id']) : 'S_no' . $sid;
            $fullName = htmlspecialchars($s['full_name']);
            $section = htmlspecialchars($s['section']);
            $att = isset($attendanceMap[$sid]) ? $attendanceMap[$sid] : null;
            $status = $att && !empty($att['status']) ? htmlspecialchars($att['status']) : '-';
            $timeIn = $att && !empty($att['time_in']) ? $att['time_in'] : '-';
            $timeOut = $att && !empty($att['time_out']) ? $att['time_out'] : '-';
            $remarks = ($status === '-' ? '-' : ($att['status'] === 'Present' ? 'Detected' : '-'));

            echo "<tr data-id='" . $sid . "' data-student-db-id='" . $sid . "' data-section='" . $section . "'>";
            echo "<td class='student-no'>" . $studNo . "</td>";
            echo "<td class='name'>" . $fullName . "</td>";
            echo "<td class='status'>" . $status . "</td>";
            echo "<td class='time-in'>" . $timeIn . "</td>";
            echo "<td class='time-out'>" . $timeOut . "</td>";
            echo "<td class='remarks'>" . $remarks . "</td>";
            echo "<td><button class='btn-green update-btn'>Edit Manally</button></td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='7'>No students found.</td></tr>";
        }
        ?>
        </tbody>
      </table>
      <a href="#" class="download-btn">Download Report</a>
    </div>
  </div>

  <!-- Update Modal -->
  <div id="update-modal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Update Student Record</h2>
      <form id="update-form">
        <input type="hidden" id="student-id">
        <div class="form-group">
          <label for="update-name">Name</label>
          <input type="text" id="update-name" required>
        </div>
        <div class="form-group">
          <label for="update-time-in">Time In</label>
          <input type="text" id="update-time-in">
        </div>
        <div class="form-group">
          <label for="update-time-out">Time Out</label>
          <input type="text" id="update-time-out">
        </div>
        <div class="form-group">
          <label for="update-status">Status</label>
          <select id="update-status" class="status-select" required>
            <option value="" disabled selected>-- select status --</option>
            <option value="Present">Present</option>
            <option value="Late">Late</option>
            <option value="Absent">Absent</option>
            <option value="Delete">Delete</option>
          </select>
        </div>
        <button type="submit" class="save-btn">Save Changes</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const tableBody = document.querySelector("table tbody");
      const updateModal = document.getElementById("update-modal");
      const closeBtn = document.querySelector(".close-btn");
      const updateForm = document.getElementById("update-form");

      // Open Update Modal
      tableBody.addEventListener("click", (event) => {
        const button = event.target.closest("button");
        if (!button) return;

        const row = button.closest("tr");

        if (button.classList.contains("update-btn")) {
          const studentId = row.dataset.id;
          const name = row.querySelector(".name").textContent;
          const timeIn = row.querySelector(".time-in").textContent;
          const timeOut = row.querySelector(".time-out").textContent;
          // Do not prefill status — require user to choose explicitly
          document.getElementById("student-id").value = studentId;
          document.getElementById("update-name").value = name;
          document.getElementById("update-time-in").value = timeIn;
          document.getElementById("update-time-out").value = timeOut;
          // Reset status to placeholder
          const statusSelect = document.getElementById("update-status");
          if (statusSelect) statusSelect.selectedIndex = 0;

          updateModal.style.display = "flex"; // show modal
        }

        if (button.classList.contains("delete-btn")) {
          if (confirm("Are you sure you want to delete this record?")) {
            row.remove();
          }
        }
      });

      // Close modal
      closeBtn.addEventListener("click", () => {
        updateModal.style.display = "none";
      });

      // Close if clicked outside modal
      window.addEventListener("click", (event) => {
        if (event.target === updateModal) {
          updateModal.style.display = "none";
        }
      });

      // Handle Update form submit
      updateForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const studentId = document.getElementById("student-id").value;
        const row = document.querySelector(`tr[data-id="${studentId}"]`);
        if (!row) { alert('Row not found'); updateModal.style.display = 'none'; return; }

        const name = document.getElementById("update-name").value;
        const timeIn = document.getElementById("update-time-in").value;
        const timeOut = document.getElementById("update-time-out").value;
        const status = document.getElementById("update-status").value;

        if (!status) {
          alert('Please select a status.');
          return;
        }

        // If user chose Delete, confirm and remove row
        const studentDbId = row.getAttribute('data-student-db-id') || row.dataset.studentDbId || null;
        const section = row.getAttribute('data-section') || '';

        if (status === 'Delete') {
          if (!confirm('Are you sure you want to delete this attendance record?')) {
            return;
          }

          // optimistic remove
          row.remove();

          const payloadDel = new URLSearchParams();
          payloadDel.append('student_db_id', studentDbId);
          payloadDel.append('action', 'delete');
          payloadDel.append('section', section);

          fetch('crud/save_manual_attendance.php', { method: 'POST', body: payloadDel })
            .then(r => r.json())
            .then(json => {
              if (!json || !json.success) {
                alert('Failed to delete attendance: ' + (json && json.message ? json.message : 'unknown'));
              }
            }).catch(err => { console.error('delete error', err); alert('Error deleting attendance'); });

          updateModal.style.display = "none";
          return;
        }

        // optimistic UI update for status/time
        row.querySelector(".name").textContent = name;
        row.querySelector(".time-in").textContent = timeIn || 'Absent';
        row.querySelector(".time-out").textContent = timeOut || 'Absent';
        row.querySelector(".status").textContent = status || 'Absent';

        // persist to server via fetch POST
        const payload = new URLSearchParams();
        payload.append('student_db_id', studentDbId);
        payload.append('status', status);
        payload.append('time_in', timeIn);
        payload.append('time_out', timeOut);
        payload.append('section', section);

        fetch('crud/save_manual_attendance.php', { method: 'POST', body: payload })
          .then(r => r.json())
          .then(json => {
            if (!json || !json.success) {
              alert('Failed to save attendance: ' + (json && json.message ? json.message : 'unknown'));
            }
          }).catch(err => {
            console.error('save error', err); alert('Error saving attendance');
          });

        updateModal.style.display = "none";
      });

      // If page opened with ?student_db_id=..., pre-open the modal for that student
      const params = new URLSearchParams(window.location.search);
      const preId = params.get('student_db_id');
      if (preId) {
        const preRow = document.querySelector(`tr[data-student-db-id="${preId}"]`);
        if (preRow) {
          const studentId = preRow.dataset.id || preId;
          const name = preRow.querySelector('.name').textContent;
          const timeIn = preRow.querySelector('.time-in').textContent;
          const timeOut = preRow.querySelector('.time-out').textContent;
          const status = preRow.querySelector('.status').textContent;
          document.getElementById('student-id').value = studentId;
          document.getElementById('update-name').value = name;
          document.getElementById('update-time-in').value = timeIn;
          document.getElementById('update-time-out').value = timeOut;
          // leave status unselected so user must pick
          const preStatusSel = document.getElementById('update-status'); if (preStatusSel) preStatusSel.selectedIndex = 0;
          updateModal.style.display = 'flex';
          // Remove query param from URL to avoid reopening on refresh
          if (history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('student_db_id');
            window.history.replaceState({}, document.title, url.toString());
          }
        }
      }
    });
  </script>
</body>
</html>
