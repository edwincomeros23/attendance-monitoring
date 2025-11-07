<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// If a fatal error happens, return JSON instead of HTML
register_shutdown_function(function(){
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Fatal error: '.$err['message']]);
        exit;
    }
});

require_once 'db.php';

// Expect POST with fields: faculty_id, first_name, middle_initial, last_name, email, status, department
$faculty_id = isset($_POST['faculty_id']) ? trim($_POST['faculty_id']) : null;
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
$middle_initial = isset($_POST['middle_initial']) ? trim($_POST['middle_initial']) : null;
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';
$department = isset($_POST['department']) ? trim($_POST['department']) : null;

// Basic validation
$errors = [];
if (!$faculty_id) $errors[] = 'faculty_id is required';
if (!$first_name) $errors[] = 'first_name is required';
if (!$last_name) $errors[] = 'last_name is required';
if (!$email) $errors[] = 'email is required';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Prepare insert
$stmt = $conn->prepare("INSERT INTO teachers (faculty_id, first_name, middle_initial, last_name, email, status, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'DB prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('sssssss', $faculty_id, $first_name, $middle_initial, $last_name, $email, $status, $department);
$ok = $stmt->execute();
if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'DB execute failed: ' . $stmt->error]);
    exit;
}
$newId = $stmt->insert_id;
$stmt->close();

// Return the inserted record
echo json_encode(['success' => true, 'id' => $newId, 'row' => [
    'id' => $newId,
    'faculty_id' => $faculty_id,
    'first_name' => $first_name,
    'middle_initial' => $middle_initial,
    'last_name' => $last_name,
    'email' => $email,
    'status' => $status,
    'department' => $department
]]);

?>