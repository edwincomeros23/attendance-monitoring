<?php
header('Content-Type: application/json; charset=utf-8');
// Returns student info by student_id (not DB id)
// Example: /attendance-monitoring/get_student_info.php?student_id=S999
require_once __DIR__ . '/db.php';

$student_id_raw = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
if ($student_id_raw === '') {
  echo json_encode(['ok' => false, 'error' => 'missing_student_id']);
  exit;
}

// first try matching by student_id (case/whitespace-insensitive)
$student_id_norm = strtoupper($student_id_raw);
$student_id = $conn->real_escape_string($student_id_norm);

$row = null;
if ($stmt = $conn->prepare("SELECT id, student_id, full_name, photo1 FROM students WHERE UPPER(TRIM(student_id)) = ? LIMIT 1")) {
  $stmt->bind_param('s', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res) {
    $row = $res->fetch_assoc();
  }
  $stmt->close();
}

// if not found and the token looks like S{dbId} or plain numeric, match by primary key id
if (!$row && preg_match('/^S?(\d+)$/i', $student_id_raw, $m)) {
  $idInt = (int)$m[1];
  if ($idInt > 0 && ($stmt = $conn->prepare("SELECT id, student_id, full_name, photo1 FROM students WHERE id = ? LIMIT 1"))) {
    $stmt->bind_param('i', $idInt);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
      $row = $res->fetch_assoc();
    }
    $stmt->close();
  }
}

if ($row) {
  $photo = $row['photo1'] ?: 'students/default-avatar.png';
  if ($photo && $photo[0] !== '/') $photo = './' . $photo;
  echo json_encode(['ok' => true, 'student' => [
    'id' => (int)$row['id'],
    'student_id' => trim($row['student_id']),
    'full_name' => $row['full_name'],
    'photo' => $photo
  ]]);
  exit;
}

echo json_encode(['ok' => false, 'error' => 'not_found']);
