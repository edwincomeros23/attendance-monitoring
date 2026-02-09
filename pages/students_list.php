<?php
include '../db.php';
$section = isset($_GET['section']) ? $conn->real_escape_string($_GET['section']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Students in <?php echo htmlspecialchars($section); ?> — WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css" />
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;display:flex;overflow:hidden}
    .sidebar{position:fixed;left:0;top:0;height:100vh;overflow-y:auto;z-index:100}
    .main{flex:1;padding:20px;margin-left:220px;height:100vh;overflow-y:auto}
    header{background:#b71c1c;color:#fff;padding:12px 20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
    .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}
    .back-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:#b71c1c;color:#fff;border-radius:6px;text-decoration:none}
  </style>
</head>
<body>
<?php if(file_exists('../sidebar.php')) include '../sidebar.php'; ?>
<div class="main">
  <header>
    <h2>Grade 7 <?php echo htmlspecialchars($section); ?></h2>
    <div class="header-actions">
      <a href="/attendance-monitoring/pages/notifications.php" class="notif-btn" aria-label="Notifications" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
          <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
        </svg>
        <span class="notif-count"><?php echo htmlspecialchars($notifCountDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
      <a href="students.php" class="back-btn">&larr; Back</a>
    </div>
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
  
  /* Face registration step indicators */
  #faceRegisterModal #faceSteps {
    list-style-type: decimal;
    padding-left: 22px;
    margin: 0;
  }
  #faceRegisterModal #faceSteps li {
    padding: 10px 8px;
    margin: 4px 0;
    border-radius: 6px;
    transition: all 0.3s ease;
    color: #666;
    font-size: 13px;
    font-weight: 500;
    border-left: 3px solid transparent;
  }
  #faceRegisterModal #faceSteps li.step-completed {
    background: #d4edda !important;
    color: #155724 !important;
    font-weight: 700 !important;
    border-left: 3px solid #28a745 !important;
  }
  #faceRegisterModal #faceSteps li.step-active {
    background: linear-gradient(90deg,rgba(179,0,0,0.12),transparent) !important;
    color: #b30000 !important;
    font-weight: 800 !important;
    font-size: 14px !important;
    border-left: 3px solid #b30000 !important;
  }
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
          $stmt = $conn->prepare("SELECT id, student_id, full_name, year_level, section, guardian, phone_no, guardian_email, birthdate, gender, photo1 FROM students WHERE section = ? ORDER BY full_name ASC");
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
              $guardian_email = htmlspecialchars($row['guardian_email'] ?: '');
              $birthdate = htmlspecialchars($row['birthdate'] ?: '');
              $gender = htmlspecialchars($row['gender'] ?: '');
              $year_level = htmlspecialchars($row['year_level'] ?: '');
              $sectionVal = htmlspecialchars($row['section'] ?: $section);
              $rawAvatar = $row['photo1'] ?: '';
              if ($rawAvatar && strpos($rawAvatar, 'known_faces/') === 0) {
                $rawAvatar = '../' . $rawAvatar;
              }
              $avatar = htmlspecialchars($rawAvatar ?: '../students/default-avatar.png');
              echo "<tr data-student-db-id=\"{$dbid}\" data-name=\"{$name}\" data-year=\"{$year_level}\" data-section=\"{$sectionVal}\" data-guardian=\"{$guardian}\" data-guardian-email=\"{$guardian_email}\" data-phone=\"{$phone}\" data-birthdate=\"{$birthdate}\" data-gender=\"{$gender}\" data-avatar=\"{$avatar}\">";
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
              <div class="sl-avatar" style="margin-bottom:8px;position:relative">
                <video id="addAvatarVideo" autoplay muted playsinline style="width:180px;height:180px;object-fit:cover;border-radius:12px;border:4px solid #fff;box-shadow:0 8px 26px rgba(0,0,0,0.12);background:#000"></video>
                <img id="addModalAvatar" src="../students/default-avatar.png" alt="Avatar" style="position:absolute;inset:0;margin:auto;display:none;width:180px;height:180px;object-fit:cover;border-radius:12px;border:4px solid #fff;box-shadow:0 8px 26px rgba(0,0,0,0.12)" />
              </div>
              <div style="text-align:center;margin-top:6px">
                <button id="addRegisterBtn" class="action-btn upload-btn">Register Face (save first)</button>
                <input type="file" id="addPhotoInput" accept="image/*" style="display:none" />
                <div id="addUploadStatus" style="margin-top:8px;font-size:13px;color:#666">Use live registration after saving the student.</div>
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

                  <div><strong>Gender:</strong>
                    <select id="addGender" class="sl-input">
                      <option value="">-- select --</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div><strong>Birthdate:</strong><input id="addBirthdate" type="date" class="sl-input"></div>

                  <div><strong>Year/Level:</strong>
                    <select id="addYear" class="sl-input">
                      <option value="Grade 7" selected>Grade 7</option>
                      <option value="Grade 8">Grade 8</option>
                    </select>
                  </div>
                  <div><strong>Section:</strong>
                    <select id="addSection" class="sl-input">
                      <?php
                        $sections = [];
                        if ($conn) {
                          $rs = $conn->query("SELECT DISTINCT section FROM students WHERE section <> '' ORDER BY section ASC");
                          if ($rs) { while ($r = $rs->fetch_assoc()) { if (!empty($r['section'])) $sections[] = $r['section']; } }
                        }
                        $defaultSection = $section;
                        if ($defaultSection && !in_array($defaultSection, $sections, true)) array_unshift($sections, $defaultSection);
                        if (empty($sections)) { $sections = [$defaultSection ?: '']; }
                        foreach ($sections as $secOpt) {
                          $esc = htmlspecialchars($secOpt);
                          $sel = ($secOpt === $defaultSection) ? ' selected' : '';
                          echo "<option value=\"{$esc}\"{$sel}>{$esc}</option>";
                        }
                      ?>
                    </select>
                  </div>

                  <div><strong>Guardian/Parent's Name:</strong><input id="addGuardian" class="sl-input"></div>
                  <div><strong>Guardian/Parent's phone number:</strong><input id="addGuardianPhone" class="sl-input"></div>
                  <div><strong>Guardian/Parent's email:</strong><input id="addGuardianEmail" class="sl-input" type="email"></div>

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
                    <div class="sl-avatar" id="modalAvatar" style="padding:20px;">
                      <div style="width:180px;height:180px;border-radius:12px;border:4px solid #fff;box-shadow:0 8px 26px rgba(0,0,0,0.12);background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#666;margin:0 auto">
                        No avatar in View
                      </div>
                    </div>
                    <div style="text-align:center;margin-top:10px">
                      <button id="registerFaceBtn" class="action-btn upload-btn">Register Face</button>
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
                  <div><strong>Guardian/Parent's phone number:</strong> <input type="text" id="modalPhone" class="sl-input" readonly></div>
                  <div><strong>Guardian/Parent's email:</strong> <input type="email" id="modalGuardianEmail" class="sl-input" readonly></div>
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

      <!-- Face registration modal: captures multiple angles from webcam -->
      <div id="faceRegisterModal" class="sl-modal" aria-hidden="true">
        <div class="sl-modal-content" style="max-width:700px;width:96%">
          <button class="sl-modal-close" id="faceModalClose">✖</button>
          <div style="padding:14px 16px">
            <h3 id="faceModalTitle" style="text-align:center;margin:0 0 14px 0;font-size:24px;font-weight:850;color:#111;letter-spacing:-0.3px">Register Face</h3>
            <!-- Step Indicator Banner -->
            <div id="currentStepBanner" style="background:linear-gradient(135deg,#b30000,#c53333);color:#fff;padding:18px 16px;border-radius:10px;text-align:center;margin:12px 0 16px 0;font-size:18px;font-weight:800;box-shadow:0 8px 24px rgba(179,0,0,0.18)">
              📸 STEP 1: Look straight at the camera
            </div>
            <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;align-items:start">
              <div style="text-align:center">
                <div style="position:relative;width:100%;background:#000;border-radius:12px;overflow:hidden;box-shadow:0 12px 32px rgba(0,0,0,0.18)">
                  <video id="faceVideo" autoplay playsinline style="width:100%;display:block;aspect-ratio:4/3"></video>
                  <img id="capturedPhotoPreview" src="" alt="First captured" style="display:none;width:100%;position:absolute;top:0;left:0;aspect-ratio:4/3" />
                </div>
                <canvas id="faceCanvas" style="display:none"></canvas>
                <div id="faceCaptureHint" style="margin-top:12px;font-size:13px;color:#555;font-weight:600">Follow the prompts and click Capture.</div>
              </div>
              <div style="background:#f8f9fc;border:1px solid #f0f0f0;border-radius:10px;padding:12px;box-shadow:0 4px 12px rgba(0,0,0,0.04)">
                <div style="font-size:12px;color:#555;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:10px">Steps</div>
                <ol id="faceSteps" style="padding-left:22px;margin:0;list-style-position:inside">
                  <li data-step="0" style="padding:10px 8px;margin:4px 0;border-radius:6px;transition:all .3s ease;color:#666;font-size:13px;font-weight:500">Look straight</li>
                  <li data-step="1" style="padding:10px 8px;margin:4px 0;border-radius:6px;transition:all .3s ease;color:#666;font-size:13px;font-weight:500">Turn left</li>
                  <li data-step="2" style="padding:10px 8px;margin:4px 0;border-radius:6px;transition:all .3s ease;color:#666;font-size:13px;font-weight:500">Turn right</li>
                  <li data-step="3" style="padding:10px 8px;margin:4px 0;border-radius:6px;transition:all .3s ease;color:#666;font-size:13px;font-weight:500">Tilt up</li>
                  <li data-step="4" style="padding:10px 8px;margin:4px 0;border-radius:6px;transition:all .3s ease;color:#666;font-size:13px;font-weight:500">Tilt down</li>
                </ol>
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                  <button id="faceCaptureBtn" class="btn btn-save" style="width:100%;padding:10px 12px;font-weight:700;border-radius:8px">Capture</button>
                  <button id="faceRetakeBtn" class="btn btn-cancel" style="width:100%;padding:10px 12px;font-weight:700;border-radius:8px">Retake</button>
                </div>
                <button id="faceFinishBtn" class="btn btn-primary" style="width:100%;padding:10px 12px;margin-top:8px;font-weight:700;border-radius:8px" disabled>Finish & Upload</button>
                <div id="faceResult" style="margin-top:10px;font-size:12px;color:#555;text-align:center;font-weight:600"></div>
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
  // Track which student's View modal initiated the face registration
  let faceCurrentRow = null;

  // Helper to render avatar inside the View modal
  function renderViewAvatar(src){
    const wrap = document.getElementById('modalAvatar');
    if (!wrap) return;
    if (src) {
      wrap.innerHTML = '<img src="'+src+'" alt="Avatar" style="width:180px;height:180px;object-fit:cover;border-radius:12px;border:4px solid #fff;box-shadow:0 8px 26px rgba(0,0,0,0.12)" />';
    } else {
      wrap.innerHTML = '<div style="width:180px;height:180px;border-radius:12px;border:4px solid #fff;box-shadow:0 8px 26px rgba(0,0,0,0.12);background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#666;margin:0 auto">No avatar in View</div>';
    }
  }

  function openModal(data){
    modal.setAttribute('aria-hidden','false');
    document.getElementById('modalName').textContent = data.name || '';
    document.getElementById('modalStudentId').textContent = data.studentId || '';
    document.getElementById('modalFullName').value = data.name || '';
    document.getElementById('modalYear').value = data.year || '';
    document.getElementById('modalSection').value = data.section || '';
    document.getElementById('modalGuardian').value = data.guardian || '';
  document.getElementById('modalPhone').value = data.phone || '';
  document.getElementById('modalGuardianEmail').value = data.guardianEmail || '';
  // Avoid setting invalid placeholder dates like 0000-00-00 on type="date"
  (function(){
    const bd = (data && data.birthdate) ? String(data.birthdate) : '';
    const cleanBd = (bd && bd !== '0000-00-00') ? bd : '';
    try { document.getElementById('modalBirthdate').value = cleanBd; } catch(_){}
  })();
  try { document.getElementById('modalGender').value = data.gender || ''; } catch(_){ }
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
      guardianEmail: document.getElementById('modalGuardianEmail').value,
      birthdate: document.getElementById('modalBirthdate').value,
      gender: document.getElementById('modalGender').value,
      avatarSrc: ''
    };
    // Set avatar in View modal: prefer saved avatar, else first pending captured photo for this same row
    try {
      const fromRow = (modal._row && modal._row.dataset && modal._row.dataset.avatar) ? modal._row.dataset.avatar : '';
      const hasRealRowAvatar = fromRow && fromRow !== '../students/default-avatar.png';
      const dataAvatar = (data && data.avatar && data.avatar !== '../students/default-avatar.png') ? data.avatar : '';
      const usePending = (typeof pendingFaceImages !== 'undefined' && pendingFaceImages && pendingFaceImages[0] && faceCurrentRow && modal._row === faceCurrentRow) ? pendingFaceImages[0] : '';
      const src = dataAvatar || (hasRealRowAvatar ? fromRow : usePending);
      renderViewAvatar(src);
    } catch(_) { /* noop */ }
  }

  function closeModal(){ 
    modal.setAttribute('aria-hidden','true'); 
    // clear reference to the row so modal always initializes from row data on next open
    modal._row = null;
    // View modal does not use upload/photo inputs or a small live preview
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
      const guardianEmail = row.dataset.guardianEmail || '';
      const phone = row.dataset.phone || '';
      const birthdate = row.dataset.birthdate || '';
      const gender = row.dataset.gender || '';
      const avatar = row.dataset.avatar || '../students/default-avatar.png';
      // populate modal with stored row data
      openModal({ name, studentId, year, section: sectionVal, guardian, guardianEmail, phone, avatar, birthdate, gender });
    });
  });

  if (modalClose) modalClose.addEventListener('click', closeModal);
  // allow clicking backdrop to close
  if (modal) modal.addEventListener('click', (e)=>{ if (e.target === modal) closeModal(); });

  if (editBtn) editBtn.addEventListener('click', ()=>{
    Array.from(document.querySelectorAll('.sl-input')).forEach(i=>i.readOnly=false);
    try { document.getElementById('modalGender').disabled = false; } catch(_){}
    editBtn.style.display='none'; saveBtn.style.display='';
    document.getElementById('cancelInfoBtn').style.display='';
  });

  if (saveBtn) saveBtn.addEventListener('click', ()=>{
    // gather values
    const sid = document.getElementById('modalStudentId').textContent.trim();
    const newName = document.getElementById('modalFullName').value.trim();
    const year = document.getElementById('modalYear').value.trim();
    const sectionVal = document.getElementById('modalSection').value.trim();
    const guardian = document.getElementById('modalGuardian').value.trim();
    const phone = document.getElementById('modalPhone').value.trim();
    const guardianEmail = document.getElementById('modalGuardianEmail').value.trim();
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
  payload.append('guardian_email', guardianEmail);
  // include birthdate/gender
  const birthdate = document.getElementById('modalBirthdate').value.trim();
  const gender = (document.getElementById('modalGender') ? document.getElementById('modalGender').value : '').trim();
  if (birthdate) payload.append('birthdate', birthdate);
  if (gender) payload.append('gender', gender);
  if (avatar) payload.append('photo1', avatar);

    fetch('../crud/save_student.php', { method: 'POST', body: payload })
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
              r.dataset.guardianEmail = guardianEmail;
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
  if (cancelBtn) cancelBtn.addEventListener('click', ()=>{
    if (!modal._original) return;
    document.getElementById('modalName').textContent = modal._original.name;
    document.getElementById('modalStudentId').textContent = modal._original.studentId;
    document.getElementById('modalFullName').value = modal._original.fullName;
    document.getElementById('modalYear').value = modal._original.year;
    document.getElementById('modalSection').value = modal._original.section;
    document.getElementById('modalGuardian').value = modal._original.guardian;
    document.getElementById('modalPhone').value = modal._original.phone;
    document.getElementById('modalGuardianEmail').value = modal._original.guardianEmail || '';
  try { document.getElementById('modalBirthdate').value = modal._original.birthdate || ''; } catch(_){}
  try { document.getElementById('modalGender').value = modal._original.gender || ''; } catch(_){}
    // no avatar image to restore in View modal
    Array.from(document.querySelectorAll('.sl-input')).forEach(i=>i.readOnly=true);
    cancelBtn.style.display='none'; saveBtn.style.display='none'; editBtn.style.display='';
  });

  // delete behavior
  document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click', async (e)=>{
      const row = e.target.closest('tr');
      if (!row) return;
      if (!confirm('Delete this student and associated data? This action cannot be undone.')) return;
      const studentDbId = row.getAttribute('data-student-db-id') || row.dataset.studentDbId || '';
      if (!studentDbId) { alert('Missing student id'); return; }
      const delBtn = e.currentTarget;
      delBtn.disabled = true; delBtn.textContent = 'Deleting...';
      try {
        const payload = new URLSearchParams(); payload.append('student_db_id', String(studentDbId));
        const res = await fetch('../crud/delete_student.php', { method: 'POST', body: payload });
        const json = await res.json();
        if (json && json.success) {
          row.remove();
        } else {
          alert('Delete failed: ' + (json && json.message ? json.message : res.statusText));
          delBtn.disabled = false; delBtn.textContent = 'Delete';
        }
      } catch (err) {
        console.error('Delete error', err);
        alert('Delete failed due to network error');
        delBtn.disabled = false; delBtn.textContent = 'Delete';
      }
    });
  });

  // View modal does not support direct photo upload; upload flow exists only in Add modal

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

  // Small preview management (start/stop camera for small avatar previews)
  const _smallStreams = new Map();
  async function startSmallPreviewFor(videoEl){
    if (!videoEl) return;
    if (_smallStreams.has(videoEl)) return;
    try {
      const s = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
      videoEl.srcObject = s;
      await videoEl.play();
      _smallStreams.set(videoEl, s);
      // hide fallback img if exists
      try { const img = videoEl.parentElement.querySelector('img'); if (img) img.style.display = 'none'; } catch(_){ }
    } catch (err) {
      console.error('small preview failed', err);
      // show fallback image if available
      try { const img = videoEl.parentElement.querySelector('img'); if (img) img.style.display = ''; } catch(_){ }
    }
  }
  function stopSmallPreviewFor(videoEl){
    if (!videoEl) return;
    const s = _smallStreams.get(videoEl);
    if (s) {
      try { s.getTracks().forEach(t=>t.stop()); } catch(_){ }
      _smallStreams.delete(videoEl);
    }
    try { videoEl.pause(); videoEl.srcObject = null; } catch(_){ }
    try { const img = videoEl.parentElement.querySelector('img'); if (img) img.style.display = ''; } catch(_){ }
  }

  if (addBtn) addBtn.addEventListener('click', ()=>{
    // clear fields with sensible defaults for selects
    ['addStudentId','addFirstName','addMiddle','addLastName','addBirthdate','addGuardian','addGuardianPhone','addRelationship'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; });
    const gSel = document.getElementById('addGender'); if (gSel) gSel.value = '';
    const ySel = document.getElementById('addYear'); if (ySel) ySel.value = 'Grade 7';
    const sSel = document.getElementById('addSection'); if (sSel) { try { sSel.value = <?php echo json_encode($section); ?> || sSel.options[0]?.value || ''; } catch(_) { sSel.selectedIndex = 0; } }
    if (addAvatar) addAvatar.src = '../students/default-avatar.png';
    // ensure add-modal inputs are editable (some code sets .sl-input readonly globally)
    try {
      document.querySelectorAll('#addStudentModal .sl-input').forEach(i=>{ i.readOnly = false; i.disabled = false; });
    } catch(_) {}
    // clear file input so previous selection doesn't block UI
    try { if (addPhotoInput) addPhotoInput.value = ''; } catch(_) {}
    openAddModal();
    // focus first input for faster data entry
    const firstField = document.getElementById('addFirstName') || document.getElementById('addStudentId');
    try { if (firstField) firstField.focus(); } catch(_) {}
    // start small preview for add modal avatar
    try { const v = document.getElementById('addAvatarVideo'); if (v) startSmallPreviewFor(v); } catch(_){ }
  });
  if (addClose) addClose.addEventListener('click', closeAddModal);
  if (addCancel) addCancel.addEventListener('click', closeAddModal);
  // stop preview when add modal closes
  if (addClose) addClose.addEventListener('click', ()=>{ try{ const v=document.getElementById('addAvatarVideo'); if(v) stopSmallPreviewFor(v);}catch(_){}});
  if (addCancel) addCancel.addEventListener('click', ()=>{ try{ const v=document.getElementById('addAvatarVideo'); if(v) stopSmallPreviewFor(v);}catch(_){}});

  if (addUploadBtn) addUploadBtn.addEventListener('click', ()=> { if (addPhotoInput) addPhotoInput.click(); });
  if (addPhotoInput) addPhotoInput.addEventListener('change', (e) => {
    const f = e.target.files[0]; if (!f) return; const url = URL.createObjectURL(f); if (addAvatar) addAvatar.src = url; setTimeout(()=>{ try{ URL.revokeObjectURL(url); }catch(_){} },3000);
  });

  if (addSave) addSave.addEventListener('click', async ()=>{
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
    const guardianEmail = document.getElementById('addGuardianEmail').value.trim();
    const birthdate = document.getElementById('addBirthdate').value.trim();
    const gender = document.getElementById('addGender').value.trim();

    try {
      const payload = new URLSearchParams();
      payload.append('full_name', fullname || sid);
      payload.append('year_level', year);
      payload.append('section', sectionVal);
      payload.append('guardian', guardian);
      payload.append('phone_no', phone);
      payload.append('guardian_email', guardianEmail);
      if (birthdate) payload.append('birthdate', birthdate);
      if (gender) payload.append('gender', gender);
  // send student_id to match DB schema
  payload.append('student_id', sid);

      const res = await fetch('../crud/add_student.php', { method: 'POST', body: payload });
      const json = await res.json();
      if (!json || !json.success) throw new Error((json && json.message) ? json.message : 'Add failed');
      const newId = json.id;

      // if a photo was selected, upload it and let server persist photo1
      let avatarPath = '../students/default-avatar.png';
      const f = addPhotoInput && addPhotoInput.files && addPhotoInput.files[0];
      if (f) {
        const form = new FormData();
        form.append('photo', f);
        form.append('student_db_id', newId);
        const up = await fetch('crud/upload_student_photo.php', { method: 'POST', body: form });
        const upj = await up.json();
        if (upj && upj.success && upj.path) avatarPath = upj.path;
      }

      // if there are pending captured face images (from Add modal), upload them now
      if (pendingFaceImages && pendingFaceImages.filter(Boolean).length > 0) {
        // move pending into faceImages so upload routine uses them
        faceImages = pendingFaceImages.slice();
        const uj = await uploadFaceImages(newId, fullname, sid);
        if (uj && uj.paths && uj.paths[0]) {
          avatarPath = uj.paths[0];
          // clear pending images after successful upload
          pendingFaceImages = [];
        }
      }

      // create new row in table with real DB id
      const tbody = document.querySelector('table tbody');
      const tr = document.createElement('tr');
      tr.setAttribute('data-student-db-id', String(newId));
      tr.setAttribute('data-name', fullname || sid);
      tr.setAttribute('data-guardian', guardian || '');
      tr.setAttribute('data-phone', phone || '');
      tr.setAttribute('data-guardian-email', guardianEmail || '');
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
        const avatar2 = row.dataset.avatar || '../students/default-avatar.png';
        openModal({ name, studentId, year: year2, section: section2, guardian: guardian2, phone: phone2, avatar: avatar2, birthdate: birthdate2, gender: gender2 });
      });
      tr.querySelector('.delete-btn').addEventListener('click', async (e)=>{
        if (!confirm('Delete this student and associated data? This action cannot be undone.')) return;
        const studentDbId = tr.getAttribute('data-student-db-id') || tr.dataset.studentDbId || '';
        if (!studentDbId) { alert('Missing student id'); return; }
        const btn = e.currentTarget;
        btn.disabled = true; btn.textContent = 'Deleting...';
        try {
          const payload = new URLSearchParams(); payload.append('student_db_id', String(studentDbId));
          const res = await fetch('../crud/delete_student.php', { method: 'POST', body: payload });
          const json = await res.json();
          if (json && json.success) {
            tr.remove();
          } else {
            alert('Delete failed: ' + (json && json.message ? json.message : res.statusText));
            btn.disabled = false; btn.textContent = 'Delete';
          }
        } catch (err) {
          console.error(err);
          alert('Delete failed due to network error');
          btn.disabled = false; btn.textContent = 'Delete';
        }
      });

      closeAddModal();
    } catch (err) {
      console.error('Add student error', err);
      alert('Add failed: ' + (err.message || 'Unknown error'));
    } finally {
      addSave.disabled = false; addSave.textContent = 'Save';
    }
  });

  // --- Face registration flow (live capture of multiple angles) ---
  const faceModal = document.getElementById('faceRegisterModal');
  const faceModalClose = document.getElementById('faceModalClose');
  const faceVideo = document.getElementById('faceVideo');
  const faceCanvas = document.getElementById('faceCanvas');
  const faceCaptureBtn = document.getElementById('faceCaptureBtn');
  const faceRetakeBtn = document.getElementById('faceRetakeBtn');
  const faceFinishBtn = document.getElementById('faceFinishBtn');
  const faceSteps = Array.from(document.querySelectorAll('#faceSteps li'));
  const faceResult = document.getElementById('faceResult');

  let faceStream = null;
  let faceImages = [];
  let pendingFaceImages = []; // images captured before student DB row exists
  let faceStepIndex = 0;
  const totalSteps = faceSteps.length;
  function openFaceModal(){
    faceImages = [];
    faceStepIndex = 0;
    console.log('Opening face modal, faceSteps count:', faceSteps.length);
    
    // Initialize banner for step 0
    const banner = document.getElementById('currentStepBanner');
    if (banner) {
      banner.textContent = '📸 STEP 1: Look straight at the camera';
    }
    
    // Check if there's a first captured photo to display
    const preview = document.getElementById('capturedPhotoPreview');
    const video = document.getElementById('faceVideo');
    if (preview && video) {
      // Check if pendingFaceImages[0] has a photo
      if (pendingFaceImages.length > 0 && pendingFaceImages[0]) {
        preview.src = pendingFaceImages[0];
        preview.style.display = 'block';
        video.style.display = 'none';
        console.log('Displaying first captured photo in preview');
      } else {
        preview.style.display = 'none';
        video.style.display = 'block';
      }
    }
    
    faceSteps.forEach((li,i)=> {
      // Reset all styles
      li.style.background = '';
      li.style.color = '';
      li.style.fontWeight = '';
      li.style.fontSize = '';
      li.style.borderLeft = '';
      
      // Remove any badges/checkmarks
      const existingCheck = li.querySelector('.step-check');
      if (existingCheck) existingCheck.remove();
      
      if (i === 0) {
        // Mark step 0 as active with inline styles
        li.style.background = '#f8d7da';
        li.style.color = '#721c24';
        li.style.fontWeight = '700';
        li.style.fontSize = '15px';
        li.style.borderLeft = '4px solid #b71c1c';
        
        // Add CURRENT badge to step 0
        const badge = document.createElement('span');
        badge.className = 'step-check';
        badge.textContent = ' ◀ CURRENT';
        badge.style.color = '#fff';
        badge.style.background = '#b71c1c';
        badge.style.fontWeight = '800';
        badge.style.fontSize = '11px';
        badge.style.padding = '2px 8px';
        badge.style.borderRadius = '4px';
        badge.style.marginLeft = '8px';
        li.appendChild(badge);
        
        console.log('Added step-active styles to step 0:', li.textContent);
      }
    });
    if (faceFinishBtn) faceFinishBtn.disabled = true;
    if (faceResult) faceResult.textContent = '';
    if (faceModal) faceModal.setAttribute('aria-hidden','false');
    startFaceCamera().catch(e=>{ if (faceResult) faceResult.textContent = 'Camera access denied or not available.'; console.error(e); });
  }
  function closeFaceModal(){
    if (faceModal) faceModal.setAttribute('aria-hidden','true');
    stopFaceCamera();
  }
  async function startFaceCamera(){
    if (faceStream) return;
    faceStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    if (faceVideo) {
      faceVideo.srcObject = faceStream;
      await faceVideo.play();
    }
  }
  function stopFaceCamera(){
    if (!faceStream) return;
    try { faceStream.getTracks().forEach(t=>t.stop()); } catch(_){}
    faceStream = null;
    try { if (faceVideo) { faceVideo.pause(); faceVideo.srcObject = null; } } catch(_){ }
  }

  function updateStepVisual(){
    try {
      console.log('=== updateStepVisual STARTING ===', 'faceStepIndex:', faceStepIndex);
      console.log('faceSteps element:', faceSteps, 'length:', faceSteps.length);
      
      faceSteps.forEach((li,i)=> {
        console.log('Processing step', i, 'faceStepIndex=', faceStepIndex);
        // Reset styles and remove any checkmarks
        li.style.background = '';
        li.style.color = '';
        li.style.fontWeight = '';
        li.style.fontSize = '';
        li.style.borderLeft = '';
        
        // Remove checkmark/badge if exists
        const existingCheck = li.querySelector('.step-check');
        if (existingCheck) existingCheck.remove();
        
        if (i < faceStepIndex) {
          console.log('Step', i, 'is COMPLETED (i < faceStepIndex)');
          // Completed step - green with checkmark
          li.style.background = '#d4edda';
          li.style.color = '#155724';
          li.style.fontWeight = '600';
          li.style.borderLeft = '4px solid #28a745';
          
          // Add green checkmark
          const check = document.createElement('span');
          check.className = 'step-check';
          check.textContent = ' ✓';
          check.style.color = '#28a745';
          check.style.fontWeight = '800';
          check.style.fontSize = '18px';
          check.style.marginLeft = '8px';
          li.appendChild(check);
        } else if (i === faceStepIndex) {
          console.log('Step', i, 'is CURRENT (i === faceStepIndex)');
          // Active step - red with CURRENT badge
          li.style.background = '#f8d7da';
          li.style.color = '#721c24';
          li.style.fontWeight = '700';
          li.style.fontSize = '15px';
          li.style.borderLeft = '4px solid #b71c1c';
          
          // Add CURRENT badge
          const badge = document.createElement('span');
          badge.className = 'step-check';
          badge.textContent = ' ◀ CURRENT';
          badge.style.color = '#fff';
          badge.style.background = '#b71c1c';
          badge.style.fontWeight = '800';
          badge.style.fontSize = '11px';
          badge.style.padding = '2px 8px';
          badge.style.borderRadius = '4px';
          badge.style.marginLeft = '8px';
          li.appendChild(badge);
        }
      });
      
      console.log('=== Loop complete, updating banner ===');
      
      // Update GIANT banner AFTER the loop (always update, outside loop)
      const banner = document.getElementById('currentStepBanner');
      console.log('Banner element found:', !!banner, 'faceStepIndex:', faceStepIndex);
      if (banner) {
        const stepTexts = ['Look straight at the camera', 'Turn your head left', 'Turn your head right', 'Tilt head up', 'Tilt head down'];
        const newText = `📸 STEP ${faceStepIndex+1}: ${stepTexts[faceStepIndex]}`;
        console.log('Setting banner text to:', newText);
        banner.textContent = newText;
        console.log('✓ Banner updated successfully');
      } else {
        console.error('❌ currentStepBanner element NOT FOUND!');
      }
      
      // Update hint text below video
      if (faceCaptureHint) {
        const stepTexts = ['Look straight at the camera', 'Turn your head left', 'Turn your head right', 'Tilt head up', 'Tilt head down'];
        faceCaptureHint.innerHTML = `<strong style="color:#b71c1c;font-size:16px">STEP ${faceStepIndex+1}:</strong> ${stepTexts[faceStepIndex]}`;
        console.log('✓ Hint updated');
      }
      
      console.log('=== updateStepVisual COMPLETE ===');
    } catch(err) {
      console.error('ERROR in updateStepVisual:', err);
    }
  }

  if (faceCaptureBtn) faceCaptureBtn.addEventListener('click', ()=>{
    console.log('CAPTURE CLICKED - NEW CODE RUNNING!', faceStepIndex);
    const currentStep = faceStepIndex; // remember which step we are capturing now
    if (!faceVideo || faceVideo.readyState < 2) { faceResult.textContent = 'Camera not ready'; return; }
    // capture current frame
    const w = faceVideo.videoWidth || 640;
    const h = faceVideo.videoHeight || 480;
    faceCanvas.width = w; faceCanvas.height = h;
    const ctx = faceCanvas.getContext('2d');
    ctx.drawImage(faceVideo, 0, 0, w, h);
    const data = faceCanvas.toDataURL('image/jpeg', 0.9);
    // store image for this step (replace if retaken)
    faceImages[faceStepIndex] = data;
    // Also store in pendingFaceImages so we can preview first capture later
    pendingFaceImages[faceStepIndex] = data;

    // If this is the first step capture, immediately reflect it in the View modal avatar
    try {
      if (currentStep === 0) {
        renderViewAvatar(data);
      }
    } catch(_) { /* non-blocking */ }
    faceResult.textContent = `Captured (${faceStepIndex+1}/${totalSteps})`;
    // mark current step as completed, then advance
    if (faceStepIndex < totalSteps - 1) {
      faceStepIndex++;
    }
    console.log('About to call updateStepVisual, current step:', faceStepIndex);
    updateStepVisual();
    // enable finish only when all steps captured
    if (faceImages.filter(Boolean).length === totalSteps) faceFinishBtn.disabled = false;
  });

  if (faceRetakeBtn) faceRetakeBtn.addEventListener('click', ()=>{
    // allow re-capturing current step
    faceImages[faceStepIndex] = null;
    faceResult.textContent = 'Retake the current pose then click Capture.';
    faceFinishBtn.disabled = true;
  });

  if (faceModalClose) faceModalClose.addEventListener('click', closeFaceModal);
  if (faceModal) faceModal.addEventListener('click', (e)=>{ if (e.target === faceModal) closeFaceModal(); });

  async function uploadFaceImages(studentDbId, studentName = '', studentIdValue = ''){
    if (!studentDbId) { faceResult.textContent = 'Missing student id.'; return; }
    faceResult.textContent = 'Uploading...';
    let j = null;
    try {
      const payload = { student_db_id: String(studentDbId), student_id: String(studentIdValue || ''), student_name: studentName || '', images: faceImages };
      const res = await fetch('../crud/register_face.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify(payload) });
      j = await res.json();
      if (j && j.success) {
        faceResult.textContent = 'Face registered successfully.';
        // update modal row avatar if provided
        if (modal._row && j.paths && j.paths[0]) {
          const p = j.paths[0].replace(/^\.\//,'');
          modal._row.dataset.avatar = p;
          try { modal._row.setAttribute('data-avatar', p); } catch(_){ }
          // update preview avatar in modal
          try { renderViewAvatar(p); } catch(_) { }
        }
      } else {
        faceResult.textContent = 'Upload failed: ' + (j && j.message ? j.message : (res && res.statusText ? res.statusText : 'Unknown'));
      }
    } catch (err) {
      console.error('Upload error', err);
      faceResult.textContent = 'Upload failed due to network error.';
    }
    return j;
  }

  if (faceFinishBtn) faceFinishBtn.addEventListener('click', async ()=>{
    // determine student DB id and name from currently-open modal row
    let studentDbId = '';
    let studentName = '';
    let studentIdValue = '';
    if (modal._row) {
      studentDbId = modal._row.getAttribute('data-student-db-id') || modal._row.dataset.studentDbId || '';
      // get student name from modal fields if available
      const nameEl = document.getElementById('modalFullName');
      if (nameEl) studentName = nameEl.value || '';
      const sidEl = document.getElementById('modalStudentId');
      if (sidEl) studentIdValue = sidEl.textContent.trim();
    }
    // If there's no student DB id (we're in Add flow), store captured images for later upload
    if (!studentDbId) {
      pendingFaceImages = faceImages.slice();
      faceResult.textContent = `Captured ${pendingFaceImages.filter(Boolean).length} images stored. Save the student to complete registration.`;
      setTimeout(()=>{ closeFaceModal(); }, 900);
      return;
    }
    faceFinishBtn.disabled = true;
    await uploadFaceImages(studentDbId, studentName, studentIdValue);
    setTimeout(()=>{ closeFaceModal(); }, 900);
  });

  // Wire register button in view modal
  const regBtn = document.getElementById('registerFaceBtn');
  if (regBtn) regBtn.addEventListener('click', ()=>{
    // ensure there is a selected row
    const row = modal._row;
    if (!row) { alert('Please open a student record first (click View).'); return; }
    // remember which row is registering now so we can show its pending image as avatar
    faceCurrentRow = row;
    openFaceModal();
  });

  // Wire register button in add modal: require saving first
  const addRegBtn = document.getElementById('addRegisterBtn');
  if (addRegBtn) addRegBtn.addEventListener('click', ()=>{
    // allow registering face before saving — captured images will be uploaded after Save completes
    openFaceModal();
  });


});
</script>
</body>
</html>