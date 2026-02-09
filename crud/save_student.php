<?php
include dirname(__DIR__) . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['student_db_id']) ? (int)$_POST['student_db_id'] : 0;
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
$section = isset($_POST['section']) ? trim($_POST['section']) : '';
$guardian = isset($_POST['guardian']) ? trim($_POST['guardian']) : '';
$phone_no = isset($_POST['phone_no']) ? trim($_POST['phone_no']) : '';
$guardian_email = isset($_POST['guardian_email']) ? trim($_POST['guardian_email']) : '';
$birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$avatar = '';
// accept both 'avatar' and 'photo1' keys for compatibility
if (isset($_POST['avatar'])) $avatar = trim($_POST['avatar']);
if (isset($_POST['photo1'])) $avatar = trim($_POST['photo1']);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing student id']);
    exit;
}

// Build update statement dynamically to only update provided fields
$fields = [];
$types = '';
$values = [];

if ($full_name !== '') { $fields[] = 'full_name = ?'; $types .= 's'; $values[] = $full_name; }
if ($year_level !== '') { $fields[] = 'year_level = ?'; $types .= 's'; $values[] = $year_level; }
if ($section !== '') { $fields[] = 'section = ?'; $types .= 's'; $values[] = $section; }
if ($guardian !== '') { $fields[] = 'guardian = ?'; $types .= 's'; $values[] = $guardian; }
if ($phone_no !== '') { $fields[] = 'phone_no = ?'; $types .= 's'; $values[] = $phone_no; }
if ($guardian_email !== '') { $fields[] = 'guardian_email = ?'; $types .= 's'; $values[] = $guardian_email; }
if ($birthdate !== '') { $fields[] = 'birthdate = ?'; $types .= 's'; $values[] = $birthdate; }
if ($gender !== '') { $fields[] = 'gender = ?'; $types .= 's'; $values[] = $gender; }
if ($avatar !== '') { $fields[] = 'photo1 = ?'; $types .= 's'; $values[] = $avatar; }

if (empty($fields)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

$sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = ? LIMIT 1';
$types .= 'i';
$values[] = $id;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$bind_names = [];
$bind_names[] = & $types;
for ($i = 0; $i < count($values); $i++) { $bind_names[] = & $values[$i]; }
call_user_func_array(array($stmt, 'bind_param'), $bind_names);

$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
exit;

?>
