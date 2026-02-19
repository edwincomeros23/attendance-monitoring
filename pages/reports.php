<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';
$displayRole = ucfirst($userRole);
$userLabel = ($displayRole === 'Admin') ? 'Admin' : $displayRole;
$isAdmin = ($userRole === 'admin');
$sectionsConfig = file_exists(__DIR__ . '/../config/sections.php')
  ? include __DIR__ . '/../config/sections.php'
  : ['7' => ['Ruby', 'Mahogany', 'Sunflower'], '8' => ['Ruby', 'Mahogany', 'Sunflower']];
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
  <style>
    /* Page layout to match camera.php screenshot */
    body { margin: 0; font-family: Arial, sans-serif; display: flex; background-color: #f5f5f5; }
    .main { flex: 1; padding: 20px; box-sizing: border-box; overflow:auto; max-height: calc(100vh - 40px); }
    header { background-color: #b30000; color: white; padding: 10px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
    .admin-info { font-weight: bold; }

  /* Sections table styles (copied from camera.php for identical UI) */
    table.sections-table th { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
    table.sections-table th.action { text-align: right; }
    table.sections-table td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
    table.sections-table td.action { text-align: right; }
  /* Delete button in Action column */
  .delete-btn { display:inline-flex; align-items:center; gap:6px; background:#b30000; color:#fff; padding:6px 10px; border-radius:6px; border:0; cursor:pointer; font-weight:600 }
  .delete-btn:hover { background:#c0392b }
    .small-btn { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; background: #b30000; color: #fff !important; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; box-shadow: 0 4px 10px rgba(179,0,0,0.12); transition: transform .12s ease, box-shadow .12s ease, background .12s ease; }
    .small-btn:hover { background: #990000; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.12); }
    .small-btn i { vertical-align: middle; }
    table.sections-table th.action { width: 160px; }
  </style>
</head>
<body>
  <?php include '../sidebar.php'; ?>
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

    <?php foreach ($sectionsConfig as $grade => $sections): ?>
      <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.05)">
        <h3 style="margin:0 0 8px 0;color:#b71c1c">Grade <?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?> Sections</h3>
        <div style="overflow:auto;max-height:420px">
          <table id="grade<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>Table" class="sections-table" style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:#f7f7f7"><tr><th>Section</th><th>Time</th><th>Action</th><th class="action">Reports</th></tr></thead>
            <tbody>
              <?php foreach ($sections as $sectionName): ?>
                <?php
                  $label = $grade . '-' . $sectionName;
                  $sectionParam = $sectionName;
                  $yearParam = 'Grade ' . $grade;
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="time-cell"></td>
                  <td>
                    <?php if ($isAdmin): ?>
                      <button class="delete-btn" data-year="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>" data-section="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>">Delete</button>
                    <?php else: ?>
                      <span style="opacity:.6">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="action"><a href="subjects.php?year=<?php echo urlencode($yearParam); ?>&amp;section=<?php echo urlencode($sectionParam); ?>" class="small-btn">View Reports</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if ($isAdmin): ?>
    <!-- Add Section button (right-aligned, in-flow, project red) -->
    <div style="margin-top:16px; text-align: right; margin-bottom: 8px;">
      <button id="addSectionBtn" class="small-btn" style="background:#b30000; padding:8px 12px;">Add Section</button>
    </div>

    <!-- Add Section Modal (UI-only) -->
    <div id="addSectionModal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:1200">
      <div style="background:#fff;border-radius:8px;padding:18px;min-width:320px;max-width:92%;box-shadow:0 8px 24px rgba(0,0,0,0.2)">
        <h3 style="margin-top:0;color:#b71c1c">Add Section</h3>
        <form id="addSectionForm">
          <div style="margin-bottom:10px">
            <label style="display:block;font-weight:700;margin-bottom:6px">Section Name</label>
            <input id="sectionName" name="section" type="text" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px" />
          </div>
          <div style="margin-bottom:10px">
            <label style="display:block;font-weight:700;margin-bottom:6px">Grade Level</label>
            <select id="gradeLevel" name="grade" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
              <option value="7">7</option>
              <option value="8">8</option>
              <option value="9">9</option>
              <option value="10">10</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
            <button type="button" id="cancelAddSection" class="small-btn" style="background:#6b6b6b">Cancel</button>
            <button type="submit" class="small-btn" style="background:#4caf50">Add Section</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- JS: render local machine time in Time column every second (UI-only) -->
  <script>
  (function renderLocalMachineTime(){
    const opts = { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true };
    function updateLocalTime() {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], opts);
      const rows = document.querySelectorAll('table.sections-table tbody tr');
      rows.forEach(r => {
        let td = r.querySelector('td.time-cell');
        if (!td) {
          const tds = r.querySelectorAll('td');
          td = tds && tds.length >= 2 ? tds[1] : null;
        }
        if (td) td.textContent = timeStr;
      });
    }
    updateLocalTime();
    setInterval(updateLocalTime, 1000);
  })();

  <?php if ($isAdmin): ?>
  // Modal and Add Section UI behavior (client-side only)
  (function(){
    const addBtn = document.getElementById('addSectionBtn');
    const modal = document.getElementById('addSectionModal');
    const cancelBtn = document.getElementById('cancelAddSection');
    const form = document.getElementById('addSectionForm');
    if (!addBtn || !modal || !form) return;

    function sortSectionTable(table) {
      if (!table) return;
      const tbody = table.querySelector('tbody');
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll('tr'));
      rows.sort((a, b) => {
        const aText = (a.querySelector('td')?.textContent || '').trim().toLowerCase();
        const bText = (b.querySelector('td')?.textContent || '').trim().toLowerCase();
        return aText.localeCompare(bText);
      });
      rows.forEach(row => tbody.appendChild(row));
    }

    function showModal(show){ modal.style.display = show ? 'flex' : 'none'; }
    addBtn.addEventListener('click', ()=> showModal(true));
    cancelBtn.addEventListener('click', ()=> showModal(false));

    // Close modal when clicking outside content
    modal.addEventListener('click', (e)=>{ if (e.target === modal) showModal(false); });

    form.addEventListener('submit', (e)=>{
      e.preventDefault();
      const name = document.getElementById('sectionName').value.trim();
      const grade = document.getElementById('gradeLevel').value;
      if (!name) return alert('Please enter a section name');

      const payload = new URLSearchParams();
      payload.append('action', 'add');
      payload.append('grade', grade);
      payload.append('section', name);

      // Build new row and append to the proper table
      const tableId = `grade${grade}Table`;
      const table = document.getElementById(tableId);
      if (!table) {
        alert('Target table not found');
        showModal(false);
        return;
      }
      const tbody = table.querySelector('tbody');
      const tr = document.createElement('tr');
      const tdName = document.createElement('td'); tdName.textContent = name;
      const tdTime = document.createElement('td'); tdTime.className = 'time-cell'; tdTime.textContent = '';
      const tdDelete = document.createElement('td');
      const delBtn = document.createElement('button'); delBtn.className = 'delete-btn'; delBtn.setAttribute('data-year', grade); delBtn.setAttribute('data-section', name); delBtn.textContent = 'Delete';
      tdDelete.appendChild(delBtn);
      const tdAction = document.createElement('td'); tdAction.className = 'action';
      const a = document.createElement('a');
      a.className = 'small-btn';
      a.href = `subjects.php?year=${encodeURIComponent(grade)}&section=${encodeURIComponent(name)}`;
      a.textContent = 'View Reports';
      tdAction.appendChild(a);
      tr.appendChild(tdName);
      tr.appendChild(tdTime);
      tr.appendChild(tdDelete);
      tr.appendChild(tdAction);
      fetch('/attendance-monitoring/crud/save_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload
      })
        .then(r => r.json())
        .then((data) => {
          if (!data || !data.ok) {
            alert(data && data.error ? data.error : 'Failed to save section');
            return;
          }
          tbody.appendChild(tr);
          sortSectionTable(table);
          form.reset();
          showModal(false);
        })
        .catch(() => alert('Failed to save section'));
    });
  })();

  // Delete button handling (event delegation)
  (function(){
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.delete-btn');
      if (!btn) return;
      if (!confirm('Delete this section?')) return;
      const sectionLabel = btn.getAttribute('data-section') || '';
      const grade = btn.getAttribute('data-year') || '';
      const sectionName = sectionLabel.includes('-') ? sectionLabel.split('-').slice(1).join('-') : sectionLabel;
      const payload = new URLSearchParams();
      payload.append('action', 'delete');
      payload.append('grade', grade);
      payload.append('section', sectionName);

      fetch('/attendance-monitoring/crud/save_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload
      })
        .then(r => r.json())
        .then((data) => {
          if (!data || !data.ok) {
            alert(data && data.error ? data.error : 'Failed to delete section');
            return;
          }
          const tr = btn.closest('tr');
          if (tr) {
            const table = tr.closest('table');
            tr.remove();
            sortSectionTable(table);
          }
        })
        .catch(() => alert('Failed to delete section'));
    });
  })();
  <?php endif; ?>
  </script>
</body>
</html>