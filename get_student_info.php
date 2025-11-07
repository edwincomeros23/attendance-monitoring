<?php
header('Content-Type: application/json; charset=utf-8');
// Returns student info by student_id (not DB id)
// Example: /thesis2/get_student_info.php?student_id=S999
require_once __DIR__ . '/db.php';

$student_id = isset($_GET['student_id']) ? $conn->real_escape_string($_GET['student_id']) : '';
if (!$student_id) {
  echo json_encode(['ok' => false, 'error' => 'missing_student_id']);
  exit;
}

$sql = "SELECT id, student_id, full_name, photo1 FROM students WHERE student_id = ? LIMIT 1";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param('s', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $photo = $row['photo1'] ?: 'students/default-avatar.png';
    // normalize leading slash for public path
    if ($photo && $photo[0] !== '/') $photo = './' . $photo;
    echo json_encode(['ok' => true, 'student' => [
      'id' => (int)$row['id'],
      'student_id' => $row['student_id'],
      'full_name' => $row['full_name'],
      'photo' => $photo
    ]]);
  } else {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
  }
  $stmt->close();
} else {
  echo json_encode(['ok' => false, 'error' => 'db_prepare_failed']);
}
