<?php
// create_sample_student.php
// Inserts a sample student record for 'Akoto Adona' with student_id 'S999'
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../db.php';

$student_id = 'S999';
$full_name = 'Akoto Adona';
$birthdate = '2005-01-01';
$gender = 'Male';
$year_level = '12';
$section = 'A';
$guardian = '';
$phone_no = '';
$guardian_email = '';
$photo1 = '';

// check if exists by student_id
$stmt = $conn->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    echo json_encode(['ok' => true, 'message' => 'Student already exists', 'student_id' => $student_id]);
    exit;
}

$ins = $conn->prepare('INSERT INTO students (student_id, full_name, birthdate, gender, year_level, section, guardian, phone_no, guardian_email, photo1) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
if (!$ins) {
    echo json_encode(['ok' => false, 'message' => 'DB prepare failed']);
    exit;
}
$ins->bind_param('ssssssssss', $student_id, $full_name, $birthdate, $gender, $year_level, $section, $guardian, $phone_no, $guardian_email, $photo1);
$ok = $ins->execute();
if ($ok) {
    echo json_encode(['ok' => true, 'message' => 'Sample student created', 'student_id' => $student_id]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Insert failed', 'error' => $conn->error]);
}

?>
