<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: signin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WMSU Attendance Tracking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css" />
    <style>
        /* Modal (Pop-up) styles */
        .modal {
            display: none; /* This hides the modal by default */
            position: fixed; /* Positions it over the other content */
            z-index: 1000; /* Ensures it's on top of everything */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4); /* Dark overlay */
        }
        .card{
          background: #b30000;
        }
header {
      background-color: #b30000; color: white; padding: 10px 20px;
      border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
    }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #submit-btn {
            width: 100%;
            padding: 10px;
            background-color: #990000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        #submit-btn:hover {
            background-color: #990000;
        }
        
        /* New Table Button Styles */
        .edit-row {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            font-size: 0.85em;
            background-color: #0275d8;
            margin-right: 5px;
        }
        .delete-row {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            font-size: 0.85em;
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <header>
            <h2>Wmsu Attendance Tracking</h2>
            <div class="admin-info"> 
                <div class="notif-wrapper" style="position:relative;display:inline-block;margin-right:12px;">
                                        <button id="notifBtn" aria-label="Notifications" title="Notifications" style="background:transparent;border:none;color:#fff;font-size:18px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;position:relative;">
                                            <span class="bell-wrap" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:transparent;padding:0;box-shadow:none;">
                                                <!-- bell icon: use solid white fill for strong contrast on red header -->
                                                <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M15 17H9a3 3 0 0 0 6 0z" fill="#ffffff"/>
                                                    <path d="M18 8a6 6 0 1 0-12 0v4l-2 2v1h16v-1l-2-2V8z" fill="#ffffff"/>
                                                </svg>
                                            </span>
                                            <span id="notifCount" style="background:#ff4d4d;color:#fff;border-radius:999px;padding:2px 6px;font-size:12px;position:absolute;right:-6px;top:-6px;border:2px solid #b30000;">2</span>
                                        </button>
                    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:34px;min-width:320px;background:#fff;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,0.12);overflow:hidden;z-index:2000">
                        <div style="padding:10px 12px;border-bottom:1px solid #eee;font-weight:700">Notifications</div>
                        <div style="max-height:260px;overflow:auto">
                            <a href="studentattendance.php?section=Ruby" class="notif-item" style="display:block;padding:10px 12px;border-bottom:1px solid #fafafa;color:#333;text-decoration:none">
                                <div style="font-weight:700">Juan Dela Cruz left the room</div>
                                <div style="font-size:13px;color:#666">Subject: Math — Grade 7 Ruby</div>
                                <div style="font-size:12px;color:#999">Oct 19, 2025 — 10:12 AM</div>
                            </a>
                            <a href="studentattendance.php?section=Emerald" class="notif-item" style="display:block;padding:10px 12px;border-bottom:1px solid #fafafa;color:#333;text-decoration:none">
                                <div style="font-weight:700">Jane Smith left the room</div>
                                <div style="font-size:13px;color:#666">Subject: Science — Grade 8 Emerald</div>
                                <div style="font-size:12px;color:#999">Oct 19, 2025 — 9:48 AM</div>
                            </a>
                        </div>
                        <div style="padding:8px;text-align:center;background:#f7f7f7"><a href="notifications.php" style="color:#b30000;text-decoration:none">View all</a></div>
                    </div>
                </div>
                <div class="admin-icon" style="display:inline-flex;align-items:center;gap:8px;color:#fff;font-weight:700">
                    <!-- user icon inline SVG fallback: solid white fill for stronger contrast -->
                    <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="12" cy="8" r="3" fill="#ffffff" />
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="#ffffff" />
                    </svg>
                    <span>Admin</span>
                </div>
            </div>
        </header>
        <section class="overview">
            <div class="welcome-row">
                <h3 class="welcome-text">Welcome, Admin Edwin</h3>
                <span id="dateTime" class="datetime"></span>
            </div>
            <div class="cards">
                <div class="card">
                    <div class="card-label">Total Student</div>
                    <span>16</span>
                    <a href="#" class="more-info">More info →</a>
                </div>
                <div class="card">
                    <div class="card-label">Present Today</div>
                    <span>10</span>
                    <a href="#" class="more-info">More info →</a>
                </div>
                <div class="card">
                    <div class="card-label">Absent</div>
                    <span>2</span>
                    <a href="#" class="more-info">More info →</a>
                </div>
                <div class="card">
                    <div class="card-label">Late</div>
                    <span>4</span>
                    <a href="#" class="more-info">More info →</a>
                </div>
            </div>
        </section>
        <section class="attendance">
            <div class="attendance-header">
                <h3>Students by Section</h3>
            </div>

            <div class="sections-grid">
                <!-- Example section cards; replace with dynamic data as needed -->
                <div class="section-card" data-section="Ruby" data-year="7">
                    <div class="section-title">Grade - 7 <span class="section-datetime" aria-hidden="true"></span></div>
                    <ul class="section-list">
                        <li>Ruby <a class="view-section" href="studentattendance.php?section=Ruby" title="View Ruby">View</a></li>
                        <li>Amethyst <a class="view-section" href="studentattendance.php?section=Amethyst" title="View Amethyst">View</a></li>
                        <li>Garnet <a class="view-section" href="studentattendance.php?section=Garnet" title="View Garnet">View</a></li>
                        <li>Sapphire <a class="view-section" href="studentattendance.php?section=Sapphire" title="View Sapphire">View</a></li>
                    </ul>
                </div>
                <div class="section-card" data-section="Emerald" data-year="8">
                    <div class="section-title">Grade - 8 <span class="section-datetime" aria-hidden="true"></span></div>
                    <ul class="section-list">
                        <li>Emerald <a class="view-section" href="studentattendance.php?section=Emerald">View</a></li>
                        <li>Peridot <a class="view-section" href="studentattendance.php?section=Peridot">View</a></li>
                    </ul>
                </div>
            </div>

            <!-- Section Attendance Modal -->
            <div id="sectionModal" class="modal" aria-hidden="true">
                <div class="modal-content">
                    <button class="close-btn" type="button" aria-label="Close section dialog">&times;</button>
                    <h3 id="sectionModalTitle">Section Attendance</h3>
                    <div id="sectionAttendanceList">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Extra styles for section cards (injected here for convenience)
            const style = document.createElement('style');
            style.textContent = `
                    /* Layout tweaks to prevent page scrolling and reduce vertical space */
                    html,body{height:100%;margin:0;padding:0}
                    /* Match camera.php: make .main flexible with consistent padding */
                    .main{flex: 1; padding: 20px; box-sizing: border-box; min-height:100%; overflow:hidden}
                    .welcome-row{display:flex;justify-content:space-between;align-items:center}
                    /* Stat cards: evenly fill the row so first/last align to container edges */
                    .cards{display:flex;gap:20px;margin-top:20px;align-items:stretch}
                    .card{flex:1 1 0;padding:16px 18px;min-height:120px;border-radius:8px;display:flex;flex-direction:column;justify-content:space-between}
                    .card .card-label{font-size:0.95rem}
                    .card span{font-size:36px;font-weight:700}

                    .sections-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-top:14px}
                    .section-card{background:#fff;border-radius:8px;border:1px solid #e8e8e8;padding:10px;box-shadow:0 4px 10px rgba(0,0,0,0.04)}
                    .section-title{background:#b30000;color:#fff;padding:8px;border-radius:6px;font-weight:700;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}
                    .section-title .section-datetime{font-weight:400;font-size:0.82em;opacity:0.95;margin-left:10px}
                    .section-list{list-style:none;padding:0;margin:0}
                    .section-list li{display:flex;justify-content:space-between;align-items:center;padding:8px 6px;border-bottom:1px solid #f2f2f2}
                    /* View link styled as a prominent filled red pill */
                    .section-list li a.view-section{background:#b30000;color:#fff;padding:8px 14px;border-radius:999px;text-decoration:none;display:inline-flex;gap:8px;align-items:center;border:1px solid rgba(179,0,0,0.08);transition:transform .12s ease,box-shadow .12s ease;box-shadow:0 8px 22px rgba(179,0,0,0.08);font-weight:500}
                    .section-list li a.view-section svg{color:#fff;fill:currentColor}
                    .section-list li a.view-section:hover{transform:translateY(-3px);box-shadow:0 18px 48px rgba(179,0,0,0.14)}
                    .section-list li a.view-section:focus{outline:3px solid rgba(255,255,255,0.12);outline-offset:3px}
                    /* notification bell wrapper and badge adjustments */
                    .bell-wrap{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center}
                    #notifCount{font-weight:700}
                    .section-list li button{background:#b30000;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer}

                    /* Constrain attendance area so page itself doesn't scroll; internal scroll if needed */
                    .attendance{max-height:calc(100vh - 220px);overflow:auto;padding-right:6px}

                    /* Modal layout: center with flex and limit content height so page doesn't scroll */
                    #sectionModal{display:none;position:fixed;inset:0;z-index:1000;background-color:rgba(0,0,0,0.4);align-items:center;justify-content:center}
                    #sectionModal .modal-content{background-color:#fefefe;padding:20px;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,0.3);width:90%;max-width:700px;max-height:calc(100vh - 120px);overflow-y:auto;position:relative}
                    #sectionModal .close-btn{position:absolute;right:14px;top:10px;z-index:10001;pointer-events:auto;cursor:pointer;background:transparent;border:none;font-size:22px}
            `;
            document.head.appendChild(style);

            // Section modal logic
            const sectionModal = document.getElementById('sectionModal');
            const sectionModalTitle = document.getElementById('sectionModalTitle');
            const sectionAttendanceList = document.getElementById('sectionAttendanceList');
            // student-modal may be absent on this page; obtain safely
            const studentModal = document.getElementById('student-modal');
            const modalTitle = studentModal ? studentModal.querySelector('#modal-title') : null;
            const studentForm = studentModal ? studentModal.querySelector('#student-form') : null;
            const submitBtn = studentModal ? studentModal.querySelector('#submit-btn') : null;
            const studentCloseBtn = studentModal ? studentModal.querySelector('.close-btn') : null;

            // Provide a safe alias `modal` for legacy usage in the script.
            const modal = studentModal || null;

            const addBtn = document.querySelector('.add');
            const editBtn = document.querySelector('.edit');
            const tableBody = document.querySelector('tbody');

            // View section links are anchors now; do not intercept clicks so browser navigates normally.

            function openSectionModal(sectionName){
                sectionModalTitle.textContent = `Attendance — ${sectionName}`;
                // sample content — replace with real data from server if desired
                sectionAttendanceList.innerHTML = `<table style="width:100%"><thead><tr><th>Name</th><th>Status</th><th>Time</th></tr></thead><tbody><tr><td>Juan Dela Cruz</td><td class='present'>Present</td><td>8:00 AM</td></tr><tr><td>Jane Smith</td><td class='present'>Present</td><td>8:15 AM</td></tr></tbody></table>`;
                // show modal as flex so it centers and the page itself doesn't scroll
                sectionModal.style.display = 'flex';
            }

            // Real-time date/time updater for header and each section
            function updateDateTime() {
                const now = new Date();
                // Format options
                const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                const datePart = now.toLocaleDateString(undefined, opts);
                const timePart = now.toLocaleTimeString();
                const header = document.getElementById('dateTime');
                if (header) header.textContent = `${datePart} ${timePart}`;

                // Update each section's datetime
                document.querySelectorAll('.section-datetime').forEach(span => {
                    span.textContent = `${datePart} ${timePart}`;
                });
            }

            // Start updater
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Notification dropdown toggle
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');
            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
                });
                document.addEventListener('click', (ev) => { if (!notifDropdown.contains(ev.target) && ev.target !== notifBtn) notifDropdown.style.display = 'none'; });
            }

            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    // ensure the modal and related nodes exist before using them
                    if (!modal) return console.warn('student modal not present');
                    modal.style.display = 'block';
                    if (modalTitle) modalTitle.textContent = 'Add New Student';
                    if (submitBtn) submitBtn.textContent = 'Add Student';
                    if (studentForm) studentForm.reset();
                });
            }

            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    if (!tableBody) return console.warn('table body not present');
                    const firstRow = tableBody.querySelector('tr');
                    if (firstRow) {
                        const data = firstRow.dataset;
                        const f = document.getElementById('firstName'); if (f) f.value = data.firstname || '';
                        const m = document.getElementById('middleName'); if (m) m.value = data.middlename || '';
                        const l = document.getElementById('lastName'); if (l) l.value = data.lastname || '';
                        const y = document.getElementById('year'); if (y) y.value = data.year || '';
                        const s = document.getElementById('section'); if (s) s.value = data.section || '';
                    }
                    if (!modal) return console.warn('student modal not present');
                    modal.style.display = 'block';
                    if (modalTitle) modalTitle.textContent = 'Edit Student';
                    if (submitBtn) submitBtn.textContent = 'Save Changes';
                });
            }

            // Event delegation to handle all table button clicks (guarded)
            if (tableBody) {
                tableBody.addEventListener('click', (event) => {
                    const target = event.target;
                    if (target.classList && target.classList.contains('edit-row')) {
                        const row = target.closest('tr');
                        const data = row ? row.dataset : {};

                        const f = document.getElementById('firstName'); if (f) f.value = data.firstname || '';
                        const m = document.getElementById('middleName'); if (m) m.value = data.middlename || '';
                        const l = document.getElementById('lastName'); if (l) l.value = data.lastname || '';
                        const y = document.getElementById('year'); if (y) y.value = data.year || '';
                        const s = document.getElementById('section'); if (s) s.value = data.section || '';
                        if (modal) modal.style.display = 'block';
                        if (modalTitle) modalTitle.textContent = 'Edit Student';
                        if (submitBtn) submitBtn.textContent = 'Save Changes';
                    }

                    if (target.classList && target.classList.contains('delete-row')) {
                        // This will confirm before deleting the row.
                        if (confirm("Are you sure you want to delete this student's record?")) {
                            const row = target.closest('tr');
                            if (row) row.remove(); // Removes the entire table row from the DOM
                            console.log("Deleted row for:", row ? row.dataset.firstname : 'unknown');
                        }
                    }
                });
            }

            if (studentCloseBtn && studentModal) {
                studentCloseBtn.addEventListener('click', () => { studentModal.style.display = 'none'; });
                window.addEventListener('click', (event) => {
                    if (event.target == studentModal) studentModal.style.display = 'none';
                });
            }

            if (studentForm) {
                studentForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const formData = new FormData(studentForm);
                    const data = Object.fromEntries(formData.entries());
                    console.log('Form Submitted:', data);
                    if (modal) modal.style.display = 'none';
                });
            }

            // Section modal close handler (delegated)
            if (sectionModal) {
                // delegated handler (keeps working for dynamic buttons)
                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('.close-btn');
                    if (btn && sectionModal.contains(btn)) {
                        console.log('delegated close-btn click');
                        sectionModal.style.display = 'none';
                        sectionModal.setAttribute('aria-hidden', 'true');
                    }
                });
                // Also attach direct handler to the close button for reliability (defensive)
                const directClose = sectionModal.querySelector('.close-btn');
                if (directClose) {
                    // ensure the button is clickable even if CSS overlay layers changed
                    directClose.style.pointerEvents = 'auto';
                    directClose.style.zIndex = '10001';
                    directClose.addEventListener('click', (ev) => {
                        try {
                            ev.preventDefault(); ev.stopPropagation();
                            console.log('sectionModal direct close clicked');
                            sectionModal.style.display = 'none';
                            sectionModal.setAttribute('aria-hidden', 'true');
                        } catch (err) {
                            console.error('close click handler error', err);
                            // fallback: try to hide via document query
                            const sm = document.getElementById('sectionModal'); if (sm) sm.style.display = 'none';
                        }
                    });
                }
                sectionModal.addEventListener('click', (e)=>{ if (e.target === sectionModal) { sectionModal.style.display = 'none'; sectionModal.setAttribute('aria-hidden', 'true'); } });
                // close on Escape
                document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') { sectionModal.style.display = 'none'; sectionModal.setAttribute('aria-hidden', 'true'); } });
            }
        });
    </script>
</body>
</html>