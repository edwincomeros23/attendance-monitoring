<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../db.php';

try {
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            listCurriculum();
            break;
        case 'add':
            addCurriculum();
            break;
        case 'update':
            updateCurriculum();
            break;
        case 'delete':
            deleteCurriculum();
            break;
        case 'get_teachers':
            getTeachers();
            break;
        case 'check_conflict':
            checkConflict();
            break;
        default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function listCurriculum() {
    global $conn;
    
    $gradeLevel = $_GET['grade_level'] ?? '';
    $section = $_GET['section'] ?? '';
    
    $sql = "SELECT c.*, 
            CONCAT(t.first_name, ' ', IFNULL(CONCAT(t.middle_initial, '. '), ''), t.last_name) as teacher_name,
            t.faculty_id
            FROM curriculum c
            LEFT JOIN teachers t ON c.teacher_id = t.id";
    
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($gradeLevel) {
        $conditions[] = "(c.grade_level = ? OR c.grade_level = ?)";
        $params[] = $gradeLevel;
        $params[] = "Grade " . $gradeLevel;
        $types .= 'ss';
    }
    
    if ($section) {
        $conditions[] = "(c.section = ? OR c.section IS NULL)";
        $params[] = $section;
        $types .= 's';
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY c.grade_level, c.subject_name";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $data]);
}

function addCurriculum() {
    global $conn;
    
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $time_in = trim($_POST['time_in'] ?? '');
    $time_out = trim($_POST['time_out'] ?? '');
    $room = trim($_POST['room'] ?? '');
    
    if (empty($subject_name) || empty($grade_level)) {
        echo json_encode(['success' => false, 'error' => 'Subject name and grade level are required']);
        return;
    }
    
    if (!preg_match('/^Grade\s/', $grade_level)) {
        $grade_level = 'Grade ' . $grade_level;
    }
    
    if ($teacher_id && $day_of_week && $time_in && $time_out) {
        $conflict = checkConflictInternal($teacher_id, $day_of_week, $time_in, $time_out, null);
        if ($conflict) {
            echo json_encode(['success' => false, 'conflict' => $conflict]);
            return;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO curriculum (subject_code, subject_name, grade_level, section, teacher_id, day_of_week, time_in, time_out, room) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    if (!$stmt->bind_param('ssssissss', $subject_code, $subject_name, $grade_level, $section, $teacher_id, $day_of_week, $time_in, $time_out, $room)) {
        throw new Exception('Bind error: ' . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Execute error: ' . $stmt->error);
    }
    
    $id = $conn->insert_id;
    $stmt->close();
    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Subject added successfully']);
}

function updateCurriculum() {
    global $conn;
    
    $id = intval($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Invalid ID');
    
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $time_in = trim($_POST['time_in'] ?? '');
    $time_out = trim($_POST['time_out'] ?? '');
    $room = trim($_POST['room'] ?? '');
    
    if (empty($subject_name) || empty($grade_level)) {
        echo json_encode(['success' => false, 'error' => 'Subject name and grade level are required']);
        return;
    }
    
    if (!preg_match('/^Grade\s/', $grade_level)) {
        $grade_level = 'Grade ' . $grade_level;
    }
    
    if ($teacher_id && $day_of_week && $time_in && $time_out) {
        $conflict = checkConflictInternal($teacher_id, $day_of_week, $time_in, $time_out, $id);
        if ($conflict) {
            echo json_encode(['success' => false, 'conflict' => $conflict]);
            return;
        }
    }
    
    $stmt = $conn->prepare("UPDATE curriculum SET subject_code = ?, subject_name = ?, grade_level = ?, section = ?, teacher_id = ?, day_of_week = ?, time_in = ?, time_out = ?, room = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    if (!$stmt->bind_param('ssssissssi', $subject_code, $subject_name, $grade_level, $section, $teacher_id, $day_of_week, $time_in, $time_out, $room, $id)) {
        throw new Exception('Bind error: ' . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Execute error: ' . $stmt->error);
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
}

function deleteCurriculum() {
    global $conn;
    
    $id = intval($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Invalid ID');
    
    $stmt = $conn->prepare("DELETE FROM curriculum WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute error: ' . $stmt->error);
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
}

function getTeachers() {
    global $conn;
    
    $result = $conn->query("SELECT id, faculty_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_initial, '. '), ''), last_name) as name, department FROM teachers WHERE status = 'Active' ORDER BY first_name, last_name");
    if (!$result) {
        throw new Exception('Query error: ' . $conn->error);
    }
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    echo json_encode(['success' => true, 'teachers' => $teachers]);
}

function checkConflict() {
    global $conn;
    
    $teacher_id = intval($_GET['teacher_id'] ?? 0);
    $day_of_week = trim($_GET['day_of_week'] ?? '');
    $time_in = trim($_GET['time_in'] ?? '');
    $time_out = trim($_GET['time_out'] ?? '');
    $exclude_id = intval($_GET['exclude_id'] ?? 0);
    
    if (!$teacher_id || !$day_of_week || !$time_in || !$time_out) {
        echo json_encode(['success' => true, 'conflict' => null]);
        return;
    }
    
    $conflict = checkConflictInternal($teacher_id, $day_of_week, $time_in, $time_out, $exclude_id);
    echo json_encode(['success' => true, 'conflict' => $conflict]);
}

function checkConflictInternal($teacher_id, $day_of_week, $time_in, $time_out, $exclude_id = null) {
    global $conn;
    
    $sql = "SELECT id, subject_name, time_in, time_out FROM curriculum 
            WHERE teacher_id = ? AND day_of_week = ? 
            AND time_in < ? AND time_out > ?";
    
    $params = [$teacher_id, $day_of_week, $time_out, $time_in];
    $types = 'isss';
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= 'i';
    }
    
    $sql .= " LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Conflict check prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conflict = $result->fetch_assoc();
    $stmt->close();
    
    return $conflict;
}
?>
