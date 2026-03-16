<?php
// delete_student.php - soft-deletes a student by stamping deleted_at
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$student_db_id = isset($_POST['student_db_id']) ? intval($_POST['student_db_id']) : 0;
if (!$student_db_id) {
  echo json_encode(['success' => false, 'message' => 'Missing student_db_id']);
  exit;
}

try {
  // soft-delete: stamp deleted_at instead of removing the row
  $del = $conn->prepare('UPDATE students SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
  if (!$del) throw new Exception('Prepare failed');
  $del->bind_param('i', $student_db_id);
  $del->execute();
  $del->close();
  if ($conn->affected_rows === 0) throw new Exception('Student not found or already deleted');

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  error_log('delete_student error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
