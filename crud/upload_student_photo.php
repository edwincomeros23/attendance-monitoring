<?php
// Simple upload endpoint for student photos
// Expects multipart/form-data with 'photo' and optional 'student_id'
header('Content-Type: application/json');
try {
  if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    throw new Exception('No file uploaded or upload error');
  }
  $uploadsDir = __DIR__ . '/../students';
  if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
  $file = $_FILES['photo'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','gif','webp'];
  if (!in_array($ext, $allowed)) throw new Exception('Unsupported file type');
  $name = 'student_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dest = $uploadsDir . DIRECTORY_SEPARATOR . $name;
  if (!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception('Failed to save file');
  // return a path relative to this project (no leading slash) so client can resolve it correctly
  $webPath = 'students/' . $name;
  // if student_db_id provided, persist into students table (photo1 column)
  if (isset($_POST['student_db_id']) && is_numeric($_POST['student_db_id'])) {
    $sid = (int)$_POST['student_db_id'];
    // attempt to update DB; ignore errors but include in response if needed
    try {
      include __DIR__ . '/../db.php';
      $stmt = $conn->prepare("UPDATE students SET photo1 = ? WHERE id = ? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param('si', $webPath, $sid);
        $stmt->execute();
        // optional: check affected rows
        $stmt->close();
      }
    } catch (Exception $e) {
      // ignore DB errors for upload, but continue returning success for file save
    }
  }

  echo json_encode(['success'=>true,'path'=>$webPath]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
