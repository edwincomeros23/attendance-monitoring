<?php
// delete_student.php - deletes a student record and removes associated known_faces folder and photo file if present
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$student_db_id = isset($_POST['student_db_id']) ? intval($_POST['student_db_id']) : 0;
if (!$student_db_id) {
  echo json_encode(['success' => false, 'message' => 'Missing student_db_id']);
  exit;
}

// helper to recursively delete a directory
function rrmdir($dir) {
  if (!is_dir($dir)) return;
  $objects = scandir($dir);
  foreach ($objects as $object) {
    if ($object === '.' || $object === '..') continue;
    $path = $dir . DIRECTORY_SEPARATOR . $object;
    if (is_dir($path)) rrmdir($path); else @unlink($path);
  }
  @rmdir($dir);
}

try {
  // fetch student photo path if any
  $photoPath = '';
  $stmt = $conn->prepare('SELECT photo1 FROM students WHERE id = ? LIMIT 1');
  if ($stmt) {
    $stmt->bind_param('i', $student_db_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      $photoPath = $row['photo1'] ?: '';
    }
    $stmt->close();
  }

  // delete student row
  $del = $conn->prepare('DELETE FROM students WHERE id = ?');
  if (!$del) throw new Exception('Prepare failed');
  $del->bind_param('i', $student_db_id);
  $ok = $del->execute();
  $del->close();
  if (!$ok) throw new Exception('Delete failed');

  // remove uploaded photo file if it exists and is inside project
  if ($photoPath) {
    // sanitize path: remove leading ./
    $p = preg_replace('#^\./#', '', $photoPath);
    $full = __DIR__ . '/../' . $p;
    if (file_exists($full) && is_file($full)) {
      @unlink($full);
    }
  }

  // remove known_faces folder for this student if present (named like S<ID>)
  $kf = __DIR__ . '/../known_faces/S' . intval($student_db_id);
  if (is_dir($kf)) rrmdir($kf);

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  error_log('delete_student error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
