<?php
include 'db.php';
$section = isset($_GET['section']) ? $conn->real_escape_string($_GET['section']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Students in <?php echo htmlspecialchars($section); ?> — WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css" />
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;display:flex}
    .main{flex:1;padding:20px}
    header{background:#b71c1c;color:#fff;padding:12px 20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
    .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}
    .back-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:#b71c1c;color:#fff;border-radius:6px;text-decoration:none}
  </style>
</head>
<body>
<?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>
<div class="main">
  <header>
    <h2>Grade 7 <?php echo htmlspecialchars($section); ?></h2>
    <a href="students.php" class="back-btn">&larr; Back</a>
  </header>
  
  <div style="margin-top:12px" class="card list-card">
    <div class="list-card-header">Students List</div>
    <?php if ($section === ''): ?>
      <div>No section specified.</div>
    <?php else: ?>
      <style>
        .list-card-header{background:#b71c1c;color:#fff;padding:8px 12px;border-radius:6px;font-weight:600;margin-bottom:10px}
        /* stronger overrides to defeat the global .card rules in style.css */
        .list-card{
          width:100% !important;
          display:block !important;
          height:auto !important;
          min-height:40px !important;
          background:#fff !important;
          color:inherit !important;
          overflow:visible !important;
          padding:12px !important;
          box-shadow:0 1px 6px rgba(0,0,0,0.06) !important;
          border-radius:8px !important;
          margin-top:8px;
          position:relative;
          padding-bottom:56px !important; /* room for add button */
        }
  .list-card table{width:100%;border-collapse:collapse;table-layout:fixed}
  .list-card thead{background:#fafafa}
  /* header lighter and rows normal weight */
  .list-card thead th{padding:10px 12px;text-align:left;font-weight:600;font-size:13px;color:#222}
  .list-card th, .list-card td{padding:8px 12px;border-bottom:1px solid #f2f2f2;text-align:left}
  .list-card tbody td{font-size:13px;color:#222;font-weight:400;vertical-align:middle}
  .list-card tbody tr:hover{background:#fafafa}
        /* column widths to keep layout stable */
        .col-name{width:40%}
        .col-id{width:15%}
        .col-year{width:15%}
        .col-time{width:15%}
    .col-att{width:15%}
  .list-card .att-cell{display:flex;justify-content:flex-end;align-items:center;padding-right:12px}
  .list-card .att-badge{display:inline-flex;align-items:center;justify-content:center;background:#28a745;color:#fff;padding:0 10px;height:26px;min-width:64px;border-radius:14px;font-size:12px;font-weight:500;line-height:1}
  .list-card .att-badge--late{background:#ff9800}
  .list-card .att-badge--absent{background:#9e9e9e}
  td.time-cell{font-size:0.95rem;color:#444;width:140px;text-align:right;padding-right:12px}
  /* align attendance header text to the right so badges line up under the header */
  .list-card thead th.col-att{ text-align: right; padding-right: 12px }
  /* also right-align time column header to match time cells */
  .list-card thead th.col-time{ text-align: right; padding-right: 12px }
  /* Actions column styling */
  .list-card thead th.col-action{ text-align: left; width:20%; }
  .action-cell{ text-align:right; }
  .action-btn{ padding:6px 10px;border-radius:6px;border:0;margin-left:8px;cursor:pointer;font-size:13px }
  .view-btn{ background:#1976d2;color:#fff }
  .delete-btn{ background:#d32f2f;color:#fff }
  /* make floating add button use system red to match layout */
  .add-student-btn{ position:absolute; right:18px; bottom:18px; background:#b71c1c; color:#fff; border:0; padding:8px 12px; border-radius:6px; cursor:pointer; z-index:40; box-shadow:0 6px 18px rgba(0,0,0,0.12) }
  @media (max-width:600px){ .add-student-btn{ right:10px; bottom:8px; padding:6px 10px } }
  /* table column layout tweaks */
  .td-name{ text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:320px }
  .td-id{ text-align:center; width:110px }
  .td-actions{ text-align:right; width:180px }
  .btn-row{ display:inline-flex; gap:8px; align-items:center; justify-content:flex-end }
  .btn-row .action-btn{ padding:6px 10px; font-size:13px }
  @media (max-width:720px){ .td-name{ max-width:160px } .td-id{ width:90px } .td-actions{ width:120px } }
  /* Modal (student profile) styles */
  .sl-modal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; backdrop-filter: blur(2px); }
  .sl-modal[aria-hidden="false"]{ display:flex; align-items:flex-start; justify-content:center; padding:32px }
  /* make the modal slightly narrower and allow vertical scrolling for long content so footer buttons stay visible */
  .sl-modal-content{ background:#fff; border-radius:12px; width:820px; max-width:96%; box-shadow:0 18px 50px rgba(10,10,10,0.28); position:relative; padding:14px 16px 16px; overflow:hidden; max-height:80vh }
  .sl-modal-close{ position:absolute; right:12px; top:12px; border:0; background:#fff; color:#b71c1c; font-size:18px; cursor:pointer; width:34px; height:34px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06) }
  /* layout: left column for avatar, right column for scrollable content */
  .sl-modal-body{ display:grid; grid-template-columns: 280px 1fr; gap:18px; align-items:start }
  .sl-left{ background:linear-gradient(180deg,#fafafa,#f3f3f3); padding:12px; border-radius:10px; text-align:center; box-shadow:inset 0 1px 0 rgba(255,255,255,0.6) }
  .sl-avatar{ display:flex; align-items:center; justify-content:center; padding:8px }
  /* slightly smaller avatar to free vertical space */
  .sl-avatar img{ width:180px; height:180px; object-fit:cover; border-radius:12px; border:4px solid #fff; box-shadow:0 8px 26px rgba(0,0,0,0.12) }
  .sl-right{ padding-top:6px }
  .sl-right #modalName{ font-size:20px; margin:4px 0 8px; font-weight:700; color:#222; text-align:center }
  /* Add-modal title should match the view modal title */
  #addModalTitle{ font-size:20px; margin:4px 0 8px; font-weight:700; color:#222; text-align:center }
  /* allow the right column to scroll when content is tall, keep footer visible */
  .sl-right .sl-info-wrap{ max-height:calc(80vh - 300px); overflow:auto; padding-right:6px }
  /* Stack info rows vertically under the title: label above value and align strong like other info */
  .sl-info{ display:grid; grid-template-columns: 1fr; gap:10px; margin-top:6px }
  .sl-info > div{ display:flex; flex-direction:column; gap:6px; padding:6px 0 }
  .sl-info > div strong{ display:block; font-size:13px; color:#555; font-weight:700; width:100%; text-align:left; margin-bottom:4px }
  .sl-info > div span{ display:block; font-weight:600; color:#222 }
  .sl-info > div input.sl-input, .sl-info > div select.sl-input{ border:1px solid #e6e6e6; padding:8px 10px; border-radius:6px; background:#fff; width:100%; box-sizing:border-box }
  /* Student ID emphasized */
  #modalStudentId{ font-size:18px; font-weight:800; color:#222; text-align:center }
  /* modal buttons */
  .sl-actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:12px }
  .sl-actions .btn{ padding:8px 14px; border-radius:8px; border:0; cursor:pointer; font-weight:600 }
  .sl-actions .btn-primary{ background:#1976d2; color:#fff }
  /* make save and upload buttons use the system red */
  .sl-actions .btn-save, .upload-btn{ background:#b71c1c; color:#fff; border-radius:6px; padding:8px 12px }
  .sl-actions .btn-cancel{ background:#e0e0e0; color:#333 }

  /* small adjustments so modal footer buttons are nicely aligned */
  .sl-actions .btn{ min-width:88px }
  @media (max-width:760px){ .sl-modal-body{ grid-template-columns: 1fr; } .sl-avatar img{ width:160px; height:160px } .sl-info{ grid-template-columns: 1fr } }
  /* Add student modal form grid */
  .add-form-grid{ display:grid; grid-template-columns: 1fr 1fr; gap:10px 12px }
  .add-form-grid .full{ grid-column: 1 / -1 }
  #addStudentModal .sl-modal-content{ width:820px; max-width:96%; max-height:80vh; overflow:auto }
  /* ensure inputs inside add modal have the same look */
  #addStudentModal .sl-info input.sl-input, #addStudentModal .sl-info select.sl-input{ width:100%; border:1px solid #e6e6e6; padding:8px 10px; border-radius:6px }
  /* make upload button inside add modal use same red and style as upload in view modal */
  #addStudentModal .upload-btn, #addStudentModal .action-btn.upload-btn{ background:#b71c1c; color:#fff; border-radius:6px; padding:8px 12px; border:0 }
  /* ensure add modal content padding matches view modal */
  #addStudentModal .sl-modal-content{ padding:14px 16px 16px }
  @media (max-width:760px){ .add-form-grid{ grid-template-columns: 1fr } #addStudentModal .sl-modal-content{ width:94vw } }
      </style>

      <table>
        <thead>
          <tr>
            <th class="col-name">Name</th>
            <th class="col-id">Student ID</th>
            <th class="col-action">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          // load students from DB so edits persist across reloads
          $stmt = $conn->prepare("SELECT id, student_id, full_name, year_level, section, guardian, phone_no, birthdate, gender, photo1 FROM students WHERE section = ? ORDER BY full_name ASC");
          if ($stmt) {
            $stmt->bind_param('s', $section);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
              $sid = htmlspecialchars($row['student_id'] ?: '');
              $name = htmlspecialchars($row['full_name'] ?: '');
              $dbid = (int)$row['id'];
              $guardian = htmlspecialchars($row['guardian'] ?: '');
              $phone = htmlspecialchars($row['phone_no'] ?: '');
              $birthdate = htmlspecialchars($row['birthdate'] ?: '');
              $gender = htmlspecialchars($row['gender'] ?: '');
              $year_level = htmlspecialchars($row['year_level'] ?: '');
              $sectionVal = htmlspecialchars($row['section'] ?: $section);
              $avatar = htmlspecialchars($row['photo1'] ?: 'students/default-avatar.png');
              echo "<tr data-student-db-id=\"{$dbid}\" data-name=\"{$name}\" data-year=\"{$year_level}\" data-section=\"{$sectionVal}\" data-guardian=\"{$guardian}\" data-phone=\"{$phone}\" data-birthdate=\"{$birthdate}\" data-gender=\"{$gender}\" data-avatar=\"{$avatar}\">";
              echo "<td class=\"td-name\">{$name}</td>";
              echo "<td class=\"td-id\">{$sid}</td>";
              echo "<td class=\"td-actions\"><div class=\"btn-row\"><button class=\"action-btn view-btn\">View</button><button class=\"action-btn delete-btn\">Delete</button></div></td>";
              echo "</tr>\n";
            }
            $stmt->close();
          } else {
            // fallback: no students or prepare failed
            echo "<tr><td colspan=\"3\">No students found.</td></tr>";
          }
        ?>
        </tbody>
      </table>
      <!-- Add Student button inside the card (bottom-right) -->
      <button class="add-student-btn" id="addStudentBtn"> Add Student</button>
      <!-- Add Student Modal (new) - updated to match view modal aesthetic -->
      <div id="addStudentModal" class="sl-modal" aria-hidden="true">
        <div class="sl-modal-content">
          <button class="sl-modal-close" id="addModalClose">✖</button>
          <div class="sl-modal-body">
            <div class="sl-left">
              <div class="sl-avatar" style="margin-bottom:8px">
                <img id="addModalAvatar" src="students/default-avatar.png" alt="Avatar" />
              </div>
              <div style="text-align:center;margin-top:6px">
                <button id="addUploadBtn" class="action-btn upload-btn">Upload Photos</button>
                <input type="file" id="addPhotoInput" accept="image/*" style="display:none" />
                <div id="addUploadStatus" style="margin-top:8px;font-size:13px;color:#666"></div>
              </div>
            </div>
            <div class="sl-right">
              <h3 style="margin-top:2px" id="addModalTitle">Add Student</h3>
              <div class="sl-info-wrap">
                <div class="sl-info">
                  <div><strong>Student ID:</strong><input id="addStudentId" class="sl-input" placeholder="S_noX"></div>
                  <div><strong>First Name:</strong><input id="addFirstName" class="sl-input"></div>
                  <div><strong>Middle Initial:</strong><input id="addMiddle" class="sl-input" placeholder="optional..."></div>
                  <div><strong>Last Name:</strong><input id="addLastName" class="sl-input"></div>

                  <div><strong>Gender:</strong><input id="addGender" class="sl-input"></div>
                  <div><strong>Birthdate:</strong><input id="addBirthdate" type="date" class="sl-input"></div>

                  <div><strong>Year/Level:</strong><input id="addYear" class="sl-input" value="Grade 7"></div>
                  <div><strong>Section:</strong><input id="addSection" class="sl-input" value="<?php echo addslashes(htmlspecialchars($section)); ?>"></div>

                  <div><strong>Guardian/Parent's Name:</strong><input id="addGuardian" class="sl-input"></div>
                  <div><strong>Guardian/Parent's phone number:</strong><input id="addGuardianPhone" class="sl-input"></div>

                  <div><strong>Relationship:</strong><input id="addRelationship" class="sl-input"></div>
                </div>
              </div>
              <div class="sl-actions" style="margin-top:10px">
                <button id="addCancelBtn" class="btn btn-cancel">Cancel</button>
                <button id="addSaveBtn" class="btn btn-save">Save</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Student View Modal -->
      <div id="studentModal" class="sl-modal" aria-hidden="true">
        <div class="sl-modal-content">
          <button class="sl-modal-close" id="modalClose">✖</button>
          <div class="sl-modal-body">
            <div class="sl-left">
              <div class="sl-avatar" id="modalAvatar"> <img src="/students/default-avatar.png" alt="Avatar" /></div>
              <div style="text-align:center;margin-top:10px">
                <button id="uploadPhotoBtn" class="action-btn upload-btn">Upload photo</button>
                <input type="file" id="photoInput" accept="image/*" style="display:none" />
                <div id="uploadStatus" style="margin-top:8px;font-size:13px;color:#666"></div>
              </div>
            </div>
            <div class="sl-right">
              <h3 id="modalName">Student Name</h3>
              <div class="sl-info-wrap">
                <div class="sl-info">
                  <div><strong>Student ID:</strong> <span id="modalStudentId">S_no1</span></div>
                  <div><strong>Name:</strong> <input type="text" id="modalFullName" class="sl-input" readonly></div>
                  <div><strong>Year level:</strong> <input type="text" id="modalYear" class="sl-input" readonly></div>
                  <div><strong>Section:</strong> <input type="text" id="modalSection" class="sl-input" readonly></div>
                  <div><strong>Guardian/Parent:</strong> <input type="text" id="modalGuardian" class="sl-input" readonly></div>
                  <div><strong>Phone no.:</strong> <input type="text" id="modalPhone" class="sl-input" readonly></div>
                  <div><strong>Birthdate:</strong> <input type="date" id="modalBirthdate" class="sl-input" readonly></div>
                  <div><strong>Gender:</strong>
                    <select id="modalGender" class="sl-input" disabled>
                      <option value="">-- select --</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="sl-actions" style="margin-top:6px">
                <button id="cancelInfoBtn" class="btn btn-cancel" style="display:none">Cancel</button>
                <button id="editInfoBtn" class="btn btn-primary">Edit</button>
                <button id="saveInfoBtn" class="btn btn-save" style="display:none">Save</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
  const modal = document.getElementById('studentModal');
  const modalClose = document.getElementById('modalClose');
  const editBtn = document.getElementById('editInfoBtn');
  const saveBtn = document.getElementById('saveInfoBtn');

  function openModal(data){
    modal.setAttribute('aria-hidden','false');
    document.getElementById('modalName').textContent = data.name || '';
    document.getElementById('modalStudentId').textContent = data.studentId || '';
    document.getElementById('modalFullName').value = data.name || '';
    document.getElementById('modalYear').value = data.year || '';
    document.getElementById('modalSection').value = data.section || '';
    document.getElementById('modalGuardian').value = data.guardian || '';
  document.getElementById('modalPhone').value = data.phone || '';
  document.getElementById('modalBirthdate').value = data.birthdate || '';
  try { document.getElementById('modalGender').value = data.gender || ''; } catch(_){ }
    // set avatar image for this modal from provided data (prevent showing previous uploads)
    try {
      const img = document.getElementById('modalAvatar').querySelector('img');
      let av = data.avatar || 'students/default-avatar.png';
      // normalize relative path (keep leading / if present)
      if (!av.match(/^https?:\/\//) && av[0] !== '/') av = './' + av;
      img.src = av;
    } catch (err) { /* ignore if img not found */ }
    // lock inputs
    Array.from(document.querySelectorAll('.sl-input')).forEach(i=>i.readOnly=true);
    saveBtn.style.display = 'none'; editBtn.style.display = '';
    // store original state for cancel
    modal._original = {
      name: data.name || '',
      studentId: data.studentId || '',
      fullName: document.getElementById('modalFullName').value,
      year: document.getElementById('modalYear').value,
      section: document.getElementById('modalSection').value,
      guardian: document.getElementById('modalGuardian').value,
      phone: document.getElementById('modalPhone').value,
      birthdate: document.getElementById('modalBirthdate').value,
      gender: document.getElementById('modalGender').value,
      avatarSrc: (document.getElementById('modalAvatar').querySelector('img') || { src: '' }).src
    };
  }

  function closeModal(){ 
    modal.setAttribute('aria-hidden','true'); 
    try { document.getElementById('uploadStatus').textContent = ''; } catch(_){}
    try { const el = document.getElementById('photoInput'); if (el) el.value = ''; } catch(_){}
    // clear reference to the row so modal always initializes from row data on next open
    modal._row = null;
  }


  // wire view buttons
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', (e)=>{
      const row = e.target.closest('tr');
      // remember which row we're editing/viewing
      modal._row = row;
      const name = row.dataset.name || row.querySelector('td').textContent.trim();
      const studentId = row.querySelectorAll('td')[1].textContent.trim();
      const year = row.dataset.year || '<?php echo addslashes(htmlspecialchars($section)); ?>';
      const sectionVal = row.dataset.section || '<?php echo addslashes(htmlspecialchars($section)); ?>';
      const guardian = row.dataset.guardian || '';
      const phone = row.dataset.phone || '';
      const birthdate = row.dataset.birthdate || '';
      const gender = row.dataset.gender || '';
      const avatar = row.dataset.avatar || 'students/default-avatar.png';
      // populate modal with stored row data
      openModal({ name, studentId, year, section: sectionVal, guardian, phone, avatar, birthdate, gender });
    });
  });

  modalClose.addEventListener('click', closeModal);
  // allow clicking backdrop to close
  modal.addEventListener('click', (e)=>{ if (e.target === modal) closeModal(); });

  editBtn.addEventListener('click', ()=>{
    Array.from(document.querySelectorAll('.sl-input')).forEach(i=>i.readOnly=false);
    try { document.getElementById('modalGender').disabled = false; } catch(_){}
    editBtn.style.display='none'; saveBtn.style.display='';
    document.getElementById('cancelInfoBtn').style.display='';
  });

  saveBtn.addEventListener('click', ()=>{
    // gather values
    const sid = document.getElementById('modalStudentId').textContent.trim();
    const newName = document.getElementById('modalFullName').value.trim();
    const year = document.getElementById('modalYear').value.trim();
    const sectionVal = document.getElementById('modalSection').value.trim();
    const guardian = document.getElementById('modalGuardian').value.trim();
    const phone = document.getElementById('modalPhone').value.trim();
    // avatar as stored on the row (without leading ./)
    const avatar = (modal._row && modal._row.dataset.avatar) ? modal._row.dataset.avatar : '';

    // show temporary saving state
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    const payload = new URLSearchParams();
    // determine student DB id reliably. prefer modal._row, otherwise search the table by displayed student id
    let studentDbId = '';
    if (modal._row) {
      studentDbId = modal._row.getAttribute('data-student-db-id') || modal._row.dataset.studentDbId || '';
    }
    if (!studentDbId) {
      const displayedSid = document.getElementById('modalStudentId').textContent.trim();
      document.querySelectorAll('table tbody tr').forEach(r => {
        if (studentDbId) return; // already found
        const cells = r.querySelectorAll('td');
        const cellSid = (cells[1] && cells[1].textContent) ? cells[1].textContent.trim() : '';
        if (cellSid === displayedSid) {
          studentDbId = r.getAttribute('data-student-db-id') || r.dataset.studentDbId || '';
          // if modal._row was not set, assign it for future operations
          if (!modal._row) modal._row = r;
        }
      });
    }
    if (!studentDbId) {
      // fail fast with a helpful message instead of sending an empty id to the server
      alert('Missing student id — cannot save. Please open the student record from the list and try again.');
      saveBtn.textContent = 'Save';
      saveBtn.disabled = false;
      return;
    }
    payload.append('student_db_id', studentDbId);
    payload.append('full_name', newName);
    payload.append('year_level', year);
  payload.append('section', sectionVal);
  payload.append('guardian', guardian);
  payload.append('phone_no', phone);
  // include birthdate/gender
  const birthdate = document.getElementById('modalBirthdate').value.trim();
  const gender = (document.getElementById('modalGender') ? document.getElementById('modalGender').value : '').trim();
  if (birthdate) payload.append('birthdate', birthdate);
  if (gender) payload.append('gender', gender);
  if (avatar) payload.append('photo1', avatar);

    fetch('crud/save_student.php', { method: 'POST', body: payload })
      .then(r => r.json())
      .then(json => {
        if (json && json.success) {
          // apply to row in DOM
          const rows = document.querySelectorAll('table tbody tr');
          rows.forEach(r=>{
            if (r.querySelectorAll('td')[1].textContent.trim() === sid){
              r.querySelector('td').textContent = newName;
              r.dataset.name = newName;
              r.dataset.year = year;
              r.dataset.section = sectionVal;
              r.dataset.guardian = guardian;
              r.dataset.phone = phone;
              if (birthdate) r.dataset.birthdate = birthdate;
              if (gender) r.dataset.gender = gender;
              if (avatar) r.dataset.avatar = avatar.replace(/^\.\//, '');
            }
          });
          Array.from(document.querySelectorAll('.sl-input')).forEach(i=>i.readOnly=true);
          saveBtn.style.display='none'; editBtn.style.display='';
          closeModal();
        } else {
          alert('Save failed: ' + (json && json.message ? json.message : 'Unknown error'));
        }
      })
      .catch(err => {
        console.error('Save error', err);
        alert('Save failed due to network error');
      })
      .finally(() => {
        saveBtn.textContent = 'Save';
        saveBtn.disabled = false;
      });
  });

  // cancel edits: restore original values and avatar
  const cancelBtn = document.getElementById('cancelInfoBtn');
  cancelBtn.addEventListener('click', ()=>{
    if (!modal._original) return;
    document.getElementById('modalName').textContent = modal._original.name;
    document.getElementById('modalStudentId').textContent = modal._original.studentId;
    document.getElementById('modalFullName').value = modal._original.fullName;
    document.getElementById('modalYear').value = modal._original.year;
    document.getElementById('modalSection').value = modal._original.section;
    document.getElementById('modalGuardian').value = modal._original.guardian;
    document.getElementById('modalPhone').value = modal._original.phone;
  try { document.getElementById('modalBirthdate').value = modal._original.birthdate || ''; } catch(_){}
  try { document.getElementById('modalGender').value = modal._original.gender || ''; } catch(_){}
    modalAvatar.querySelector('img').src = modal._original.avatarSrc;
    Array.from(document.querySelectorAll('.sl-input')).forEach(i=>i.readOnly=true);
    cancelBtn.style.display='none'; saveBtn.style.display='none'; editBtn.style.display='';
  });

  // delete behavior
  document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      const row = e.target.closest('tr');
      if (confirm('Delete this student?')) row.remove();
    });
  });

  // Upload photo flow
  const uploadBtn = document.getElementById('uploadPhotoBtn');
  const photoInput = document.getElementById('photoInput');
  const uploadStatus = document.getElementById('uploadStatus');
  const modalAvatar = document.getElementById('modalAvatar');

  uploadBtn.addEventListener('click', ()=> photoInput.click());
  photoInput.addEventListener('change', async (e)=>{
    const f = e.target.files[0];
    if (!f) return;
    // preview
  const url = URL.createObjectURL(f);
  // preview locally immediately
  modalAvatar.querySelector('img').src = url;
    uploadStatus.textContent = 'Uploading...';

    const form = new FormData();
    form.append('photo', f);
    // attach student db id so server can persist the photo for the specific student
    let uploadStudentId = '';
    if (modal._row) uploadStudentId = modal._row.getAttribute('data-student-db-id') || modal._row.dataset.studentDbId || '';
    if (!uploadStudentId) {
      const displayedSid = document.getElementById('modalStudentId').textContent.trim();
      document.querySelectorAll('table tbody tr').forEach(r => {
        if (uploadStudentId) return;
        const cells = r.querySelectorAll('td');
        const cellSid = (cells[1] && cells[1].textContent) ? cells[1].textContent.trim() : '';
        if (cellSid === displayedSid) {
          uploadStudentId = r.getAttribute('data-student-db-id') || r.dataset.studentDbId || '';
          if (!modal._row) modal._row = r;
        }
      });
    }
    if (uploadStudentId) form.append('student_db_id', uploadStudentId);
    try {
      const res = await fetch('crud/upload_student_photo.php', { method:'POST', body: form });
      const json = await res.json();
      if (json && json.success) {
        // update to saved path. If returned path is relative, prefix with current origin/path
        let p = json.path || '';
        // if path doesn't start with http or /, make it relative to current location
        if (!p.match(/^https?:\/\//) && p[0] !== '/') p = './' + p;
        modalAvatar.querySelector('img').src = p;
        uploadStatus.textContent = 'Uploaded';
        try { URL.revokeObjectURL(url); } catch(_){}
        // persist to the currently-open row only
        if (modal._row) {
          // store without leading ./
          const storePath = p.replace(/^\.\//, '');
          modal._row.dataset.avatar = storePath;
          // also update DOM row in case other parts of the UI show the avatar later
          try {
            modal._row.setAttribute('data-avatar', storePath);
          } catch(_){}
        }
      } else {
        uploadStatus.textContent = 'Upload failed: ' + (json && json.message ? json.message : res.statusText);
      }
    } catch(err){
      uploadStatus.textContent = 'Upload error';
      console.error(err);
    }
    setTimeout(()=>uploadStatus.textContent='', 2500);
  });

  /* Add Student modal handlers */
  const addBtn = document.getElementById('addStudentBtn');
  const addModal = document.getElementById('addStudentModal');
  const addClose = document.getElementById('addModalClose');
  const addCancel = document.getElementById('addCancelBtn');
  const addSave = document.getElementById('addSaveBtn');
  const addUploadBtn = document.getElementById('addUploadBtn');
  const addPhotoInput = document.getElementById('addPhotoInput');
  const addAvatar = document.getElementById('addModalAvatar');

  function openAddModal(){ addModal.setAttribute('aria-hidden','false'); }
  function closeAddModal(){ addModal.setAttribute('aria-hidden','true'); }

  addBtn.addEventListener('click', ()=>{
    // clear fields
    ['addStudentId','addFirstName','addMiddle','addLastName','addGender','addBirthdate','addYear','addSection','addGuardian','addGuardianPhone','addRelationship'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; });
    addAvatar.src = 'students/default-avatar.png';
    // ensure add-modal inputs are editable (some code sets .sl-input readonly globally)
    try {
      document.querySelectorAll('#addStudentModal .sl-input').forEach(i=>{ i.readOnly = false; i.disabled = false; });
    } catch(_) {}
    // clear file input so previous selection doesn't block UI
    try { addPhotoInput.value = ''; } catch(_) {}
    openAddModal();
    // focus first input for faster data entry
    const firstField = document.getElementById('addFirstName') || document.getElementById('addStudentId');
    try { if (firstField) firstField.focus(); } catch(_) {}
  });
  addClose.addEventListener('click', closeAddModal);
  addCancel.addEventListener('click', closeAddModal);

  addUploadBtn.addEventListener('click', ()=> addPhotoInput.click());
  addPhotoInput.addEventListener('change', (e) => {
    const f = e.target.files[0]; if (!f) return; const url = URL.createObjectURL(f); addAvatar.src = url; setTimeout(()=>{ try{ URL.revokeObjectURL(url); }catch(_){} },3000);
  });

  addSave.addEventListener('click', async ()=>{
    // collect values
    addSave.disabled = true; addSave.textContent = 'Saving...';
    const sid = document.getElementById('addStudentId').value.trim() || ('S_no' + Date.now());
    const fname = document.getElementById('addFirstName').value.trim();
    const mname = document.getElementById('addMiddle').value.trim();
    const lname = document.getElementById('addLastName').value.trim();
    const fullname = [fname, mname, lname].filter(Boolean).join(' ');
    const year = document.getElementById('addYear').value.trim();
    const sectionVal = document.getElementById('addSection').value.trim();
    const guardian = document.getElementById('addGuardian').value.trim();
    const phone = document.getElementById('addGuardianPhone').value.trim();
    const birthdate = document.getElementById('addBirthdate').value.trim();
    const gender = document.getElementById('addGender').value.trim();

    try {
      const payload = new URLSearchParams();
      payload.append('full_name', fullname || sid);
      payload.append('year_level', year);
      payload.append('section', sectionVal);
      payload.append('guardian', guardian);
      payload.append('phone_no', phone);
      if (birthdate) payload.append('birthdate', birthdate);
      if (gender) payload.append('gender', gender);
  // send student_id to match DB schema
  payload.append('student_id', sid);

      const res = await fetch('crud/add_student.php', { method: 'POST', body: payload });
      const json = await res.json();
      if (!json || !json.success) throw new Error((json && json.message) ? json.message : 'Add failed');
      const newId = json.id;

      // if a photo was selected, upload it and let server persist photo1
      let avatarPath = 'students/default-avatar.png';
      const f = addPhotoInput.files && addPhotoInput.files[0];
      if (f) {
        const form = new FormData();
        form.append('photo', f);
        form.append('student_db_id', newId);
        const up = await fetch('crud/upload_student_photo.php', { method: 'POST', body: form });
        const upj = await up.json();
        if (upj && upj.success && upj.path) avatarPath = upj.path;
      }

      // create new row in table with real DB id
      const tbody = document.querySelector('table tbody');
      const tr = document.createElement('tr');
      tr.setAttribute('data-student-db-id', String(newId));
      tr.setAttribute('data-name', fullname || sid);
      tr.setAttribute('data-avatar', (avatarPath || '').replace(/^\.\//, ''));
      tr.innerHTML = `<td class="td-name">${(fullname||sid)}</td><td class="td-id">${sid}</td><td class="td-actions"><div class="btn-row"><button class="action-btn view-btn">View</button><button class="action-btn delete-btn">Delete</button></div></td>`;
      tbody.appendChild(tr);

      // wire view/delete for the new row (use same openModal behavior)
      tr.querySelector('.view-btn').addEventListener('click', (e)=>{
        const row = e.target.closest('tr');
        modal._row = row;
        const name = row.dataset.name || row.querySelector('td').textContent.trim();
        const studentId = row.querySelectorAll('td')[1].textContent.trim();
        const year2 = row.dataset.year || '<?php echo addslashes(htmlspecialchars($section)); ?>';
        const section2 = row.dataset.section || '<?php echo addslashes(htmlspecialchars($section)); ?>';
        const guardian2 = row.dataset.guardian || '';
        const phone2 = row.dataset.phone || '';
        const birthdate2 = row.dataset.birthdate || '';
        const gender2 = row.dataset.gender || '';
        const avatar2 = row.dataset.avatar || 'students/default-avatar.png';
        openModal({ name, studentId, year: year2, section: section2, guardian: guardian2, phone: phone2, avatar: avatar2, birthdate: birthdate2, gender: gender2 });
      });
      tr.querySelector('.delete-btn').addEventListener('click', ()=>{ if(confirm('Delete this student?')) tr.remove(); });

      closeAddModal();
    } catch (err) {
      console.error('Add student error', err);
      alert('Add failed: ' + (err.message || 'Unknown error'));
    } finally {
      addSave.disabled = false; addSave.textContent = 'Save';
    }
  });

});
</script>
</body>
</html>