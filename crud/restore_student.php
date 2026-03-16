<?php
// restore_student.php - restores a soft-deleted student by clearing deleted_at
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$student_db_id = isset($_POST['student_db_id']) ? intval($_POST['student_db_id']) : 0;
if (!$student_db_id) {
  echo json_encode(['success' => false, 'message' => 'Missing student_db_id']);
  exit;
}

try {
  $stmt = $conn->prepare('UPDATE students SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL');
  if (!$stmt) throw new Exception('Prepare failed');
  $stmt->bind_param('i', $student_db_id);
  $stmt->execute();
  $stmt->close();
  if ($conn->affected_rows === 0) throw new Exception('Student not found or not deleted');

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  error_log('restore_student error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
