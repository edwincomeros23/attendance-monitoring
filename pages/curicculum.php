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

// Get all teachers for dropdown
$teachers = [];
$teacherRes = $conn->query("SELECT id, faculty_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_initial, '. '), ''), last_name) as name, department FROM teachers WHERE status = 'Active' ORDER BY first_name, last_name");
if ($teacherRes) {
    while ($t = $teacherRes->fetch_assoc()) {
        $teachers[] = $t;
    }
}

// Get unique grade levels
$gradeLevels = [];
$gradeRes = $conn->query("SELECT DISTINCT grade_level FROM curriculum ORDER BY grade_level");
if ($gradeRes) {
    while ($g = $gradeRes->fetch_assoc()) {
        $gradeLevels[] = $g['grade_level'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curriculum Management - WMSU Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css" />
    <style>
        body { margin: 0; font-family: Arial, sans-serif; display:flex; background:#f5f5f5 }
        .main { flex:1; padding:20px; box-sizing:border-box }
        header { background:#b30000; color:#fff; padding:10px 20px; border-radius:8px; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px }
        
        .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; gap:12px; flex-wrap:wrap }
        .toolbar-left { display:flex; gap:12px; align-items:center }
        .toolbar-right { display:flex; gap:12px }
        
        .filter-select { padding:8px 12px; border:1px solid #ddd; border-radius:6px; background:#fff; min-width:150px }
        .search-input { padding:8px 12px; border:1px solid #ddd; border-radius:6px; min-width:250px }
        .btn { padding:8px 14px; border-radius:6px; border:none; cursor:pointer; font-weight:600; transition:all .2s }
        .btn.primary { background:#b30000; color:#fff }
        .btn.primary:hover { background:#990000; transform:translateY(-1px) }
        .btn.secondary { background:#fff; color:#b30000; border:1px solid #b30000 }
        .btn.secondary:hover { background:#b30000; color:#fff }
        .btn:disabled { opacity:0.5; cursor:not-allowed }
        
        .table-card { background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden }
        table { width:100%; border-collapse:collapse }
        thead { background:#b71c1c; color:#fff }
        thead th { padding:12px 10px; text-align:left; font-weight:600; font-size:14px }
        tbody td { padding:12px 10px; border-bottom:1px solid #f0f0f0; font-size:14px }
        tbody tr:hover { background:#f9f9f9 }
        
        .badge { display:inline-block; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600 }
        .badge.success { background:#e8f5e9; color:#2e7d32 }
        .badge.warning { background:#fff3e0; color:#f57c00 }
        .badge.danger { background:#ffebee; color:#c62828 }
        
        .action-btns { display:flex; gap:6px }
        .btn-icon { padding:6px 10px; border:none; border-radius:4px; cursor:pointer; transition:all .2s }
        .btn-edit { background:#fff; color:#1976d2; border:1px solid #1976d2 }
        .btn-edit:hover { background:#1976d2; color:#fff }
        .btn-delete { background:#fff; color:#d32f2f; border:1px solid #d32f2f }
        .btn-delete:hover { background:#d32f2f; color:#fff }
        
        /* Modal */
        .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center }
        .modal.active { display:flex }
        .modal-content { background:#fff; border-radius:8px; padding:24px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px }
        .modal-header h3 { margin:0; color:#b30000 }
        .modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:#999 }
        
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px }
        .form-group { display:flex; flex-direction:column }
        .form-group.full { grid-column:1/-1 }
        .form-group label { margin-bottom:6px; font-weight:600; font-size:13px; color:#555 }
        .form-group input, .form-group select, .form-group textarea { padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:14px }
        .form-group textarea { resize:vertical; min-height:60px }
        .form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px }
        
        .conflict-warning { background:#fff3cd; border:1px solid #ffc107; padding:12px; border-radius:6px; margin-bottom:16px; color:#856404 }
        .empty-state { text-align:center; padding:60px 20px; color:#999 }
        .empty-state i { font-size:48px; margin-bottom:16px; opacity:0.3 }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div class="main">
        <header>
            <h2><i class="fas fa-book"></i> Curriculum Management</h2>
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
        
        <div class="toolbar">
            <div class="toolbar-left">
                <select class="filter-select" id="filterGrade">
                    <option value="">All Grade Levels</option>
                    <?php foreach ($gradeLevels as $grade): ?>
                    <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($grade); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="search-input" id="searchSubject" placeholder="Search subject...">
            </div>
            <div class="toolbar-right">
                <button class="btn secondary" onclick="window.location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                <button class="btn primary" id="btnAddSubject"><i class="fas fa-plus"></i> Add Subject</button>
            </div>
        </div>
        
        <div class="table-card">
            <table id="curriculumTable">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Grade Level</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="curriculumBody">
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading curriculum data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="subjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Subject</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div id="conflictWarning" class="conflict-warning" style="display:none">
                <strong><i class="fas fa-exclamation-triangle"></i> Schedule Conflict!</strong>
                <p id="conflictMessage"></p>
            </div>
            
            <form id="subjectForm">
                <input type="hidden" id="subjectId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" id="subjectCode" placeholder="e.g., ENG101" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject Name *</label>
                        <input type="text" id="subjectName" placeholder="e.g., English" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Grade Level *</label>
                        <select id="gradeLevel" required>
                            <option value="">Select Grade</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" id="section" placeholder="e.g., Ruby, Pearl">
                    </div>
                    
                    <div class="form-group full">
                        <label>Assigned Teacher</label>
                        <select id="teacherId">
                            <option value="">Not Assigned</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>">
                                <?php echo htmlspecialchars($teacher['name']); ?> 
                                (<?php echo htmlspecialchars($teacher['faculty_id']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Day of Week</label>
                        <select id="dayOfWeek">
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Room</label>
                        <input type="text" id="room" placeholder="e.g., Room 101">
                    </div>
                    
                    <div class="form-group">
                        <label>Time In</label>
                        <input type="time" id="timeIn">
                    </div>
                    
                    <div class="form-group">
                        <label>Time Out</label>
                        <input type="time" id="timeOut">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn primary" id="btnSave">
                        <i class="fas fa-save"></i> Save Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let currentEditId = null;
        let allCurriculum = [];
        
        // Load curriculum data
        function loadCurriculum() {
            fetch('../crud/curriculum_api.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allCurriculum = data.data;
                        renderCurriculum();
                    }
                })
                .catch(err => {
                    console.error('Load error:', err);
                    showError('Failed to load curriculum data');
                });
        }
        
        // Render curriculum table
        function renderCurriculum() {
            const tbody = document.getElementById('curriculumBody');
            const filterGrade = document.getElementById('filterGrade').value;
            const searchTerm = document.getElementById('searchSubject').value.toLowerCase();
            
            let filtered = allCurriculum.filter(item => {
                const matchGrade = !filterGrade || item.grade_level === filterGrade || item.grade_level === 'Grade ' + filterGrade;
                const matchSearch = !searchTerm || 
                    item.subject_name.toLowerCase().includes(searchTerm) ||
                    (item.subject_code && item.subject_code.toLowerCase().includes(searchTerm));
                return matchGrade && matchSearch;
            });
            
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="empty-state"><i class="fas fa-inbox"></i><p>No subjects found</p></td></tr>';
                return;
            }
            
            tbody.innerHTML = filtered.map(item => {
                const teacherName = item.teacher_name || '<span class="badge warning">Not Assigned</span>';
                const day = item.day_of_week || '-';
                const time = (item.time_in && item.time_out) 
                    ? formatTime(item.time_in) + ' - ' + formatTime(item.time_out)
                    : '-';
                const room = item.room || '-';
                const section = item.section || '-';
                
                return `
                    <tr>
                        <td>${escapeHtml(item.subject_code || '-')}</td>
                        <td><strong>${escapeHtml(item.subject_name)}</strong></td>
                        <td>${escapeHtml(item.grade_level)}</td>
                        <td>${escapeHtml(section)}</td>
                        <td>${teacherName}</td>
                        <td>${escapeHtml(day)}</td>
                        <td>${time}</td>
                        <td>${escapeHtml(room)}</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon btn-edit" onclick="editSubject(${item.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-delete" onclick="deleteSubject(${item.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // Format time (24h to 12h)
        function formatTime(time24) {
            if (!time24) return '';
            const [h, m] = time24.split(':');
            const hour = parseInt(h);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${m} ${ampm}`;
        }
        
        // Open modal for add
        document.getElementById('btnAddSubject').addEventListener('click', () => {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Add Subject';
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectId').value = '';
            document.getElementById('conflictWarning').style.display = 'none';
            document.getElementById('subjectModal').classList.add('active');
        });
        
        // Edit subject
        function editSubject(id) {
            const subject = allCurriculum.find(s => s.id === id);
            if (!subject) return;
            
            currentEditId = id;
            document.getElementById('modalTitle').textContent = 'Edit Subject';
            document.getElementById('subjectId').value = subject.id;
            document.getElementById('subjectCode').value = subject.subject_code || '';
            document.getElementById('subjectName').value = subject.subject_name;
            document.getElementById('gradeLevel').value = subject.grade_level.replace('Grade ', '');
            document.getElementById('section').value = subject.section || '';
            document.getElementById('teacherId').value = subject.teacher_id || '';
            document.getElementById('dayOfWeek').value = subject.day_of_week || '';
            document.getElementById('timeIn').value = subject.time_in || '';
            document.getElementById('timeOut').value = subject.time_out || '';
            document.getElementById('room').value = subject.room || '';
            document.getElementById('conflictWarning').style.display = 'none';
            document.getElementById('subjectModal').classList.add('active');
        }
        
        // Delete subject
        function deleteSubject(id) {
            const subject = allCurriculum.find(s => s.id === id);
            if (!confirm(`Delete "${subject.subject_name}"?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('../crud/curriculum_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Subject deleted successfully');
                        loadCurriculum();
                    } else {
                        showError(data.error || 'Failed to delete');
                    }
                });
        }
        
        // Save subject
        document.getElementById('subjectForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const btnSave = document.getElementById('btnSave');
            btnSave.disabled = true;
            
            const formData = new FormData();
            formData.append('action', currentEditId ? 'update' : 'add');
            if (currentEditId) formData.append('id', currentEditId);
            formData.append('subject_code', document.getElementById('subjectCode').value);
            formData.append('subject_name', document.getElementById('subjectName').value);
            formData.append('grade_level', document.getElementById('gradeLevel').value);
            formData.append('section', document.getElementById('section').value);
            formData.append('teacher_id', document.getElementById('teacherId').value);
            formData.append('day_of_week', document.getElementById('dayOfWeek').value);
            formData.append('time_in', document.getElementById('timeIn').value);
            formData.append('time_out', document.getElementById('timeOut').value);
            formData.append('room', document.getElementById('room').value);
            
            fetch('../crud/curriculum_api.php', { method: 'POST', body: formData })
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        showSuccess(data.message || (currentEditId ? 'Subject updated successfully' : 'Subject added successfully'));
                        closeModal();
                        loadCurriculum(); // Reload to fetch changes
                    } else if (data.conflict) {
                        const msg = `This teacher already has a class scheduled at this time:<br><strong>${data.conflict.conflict_subject}</strong><br>${data.conflict.time_in} - ${data.conflict.time_out}`;
                        document.getElementById('conflictMessage').innerHTML = msg;
                        document.getElementById('conflictWarning').style.display = 'block';
                    } else {
                        showError(data.error || 'Save failed');
                    }
                })
                .catch(err => {
                    console.error('Save error:', err);
                    showError('Error saving: ' + err.message);
                })
                .finally(() => {
                    btnSave.disabled = false;
                });
        });
        
        // Close modal
        function closeModal() {
            document.getElementById('subjectModal').classList.remove('active');
        }
        
        // Filters
        document.getElementById('filterGrade').addEventListener('change', renderCurriculum);
        document.getElementById('searchSubject').addEventListener('input', renderCurriculum);
        
        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showSuccess(msg) {
            alert('✓ ' + msg);
        }
        
        function showError(msg) {
            alert('✗ ' + msg);
        }
        
        // Initial load
        loadCurriculum();
    </script>
</body>
</html>
