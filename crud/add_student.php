<?php
include dirname(__DIR__) . '/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
$section = isset($_POST['section']) ? trim($_POST['section']) : '';
$guardian = isset($_POST['guardian']) ? trim($_POST['guardian']) : '';
$phone_no = isset($_POST['phone_no']) ? trim($_POST['phone_no']) : '';
$guardian_email = isset($_POST['guardian_email']) ? trim($_POST['guardian_email']) : '';
$birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
// The schema uses student_id (numeric/text) column; accept student_id from POST if provided
$student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';

// Minimal required field check
if ($full_name === '') {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit;
}

// Insert into students table. Adjust columns if your schema differs.
$sql = "INSERT INTO students (full_name, year_level, section, guardian, phone_no, guardian_email, birthdate, gender, student_id, photo1) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('sssssssss', $full_name, $year_level, $section, $guardian, $phone_no, $guardian_email, $birthdate, $gender, $student_id);
$ok = $stmt->execute();
if (!$ok) {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
    exit;
}
$newId = $stmt->insert_id;
$stmt->close();

echo json_encode(['success' => true, 'id' => $newId]);
exit;

?>
