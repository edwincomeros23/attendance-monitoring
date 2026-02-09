<?php
header('Content-Type: application/json');
// Accepts JSON body: { student_db_id: "123", student_id: "S123", student_name: "John Doe", images: ["data:image/jpeg;base64,...", ...] }
$body = @file_get_contents('php://input');
if (!$body) {
  echo json_encode(['success'=>false,'message'=>'No input']);
  exit;
}
$data = json_decode($body, true);
if (!is_array($data)) {
  echo json_encode(['success'=>false,'message'=>'Invalid JSON']);
  exit;
}
$student_db_id = isset($data['student_db_id']) ? trim((string)$data['student_db_id']) : '';
$student_id = isset($data['student_id']) ? trim((string)$data['student_id']) : '';
$student_name = isset($data['student_name']) ? trim((string)$data['student_name']) : '';
$images = isset($data['images']) && is_array($data['images']) ? $data['images'] : [];
if ($student_db_id === '' || count($images) === 0) {
  echo json_encode(['success'=>false,'message'=>'Missing student id or images']);
  exit;
}
// sanitize id to file-friendly token (prefer student_id)
$idSource = $student_id !== '' ? $student_id : $student_db_id;
$safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $idSource);
// sanitize name to file-friendly format (remove special chars, replace spaces with underscores)
$safeName = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $student_name);
$safeName = preg_replace('/\s+/', '_', trim($safeName));
// create folder with format: S{student_id}_{StudentName}
$folderName = $safeId;
if ($folderName !== '' && stripos($folderName, 'S') !== 0) {
  $folderName = 'S' . $folderName;
}
if ($safeName) {
  $folderName = $folderName . '_' . $safeName;
}
$dir = __DIR__ . '/../known_faces/' . $folderName;
if (!is_dir($dir)) {
  if (!mkdir($dir, 0755, true)) {
    echo json_encode(['success'=>false,'message'=>'Failed to create dir']);
    exit;
  }
}
$saved = [];
$idx = 0;
foreach ($images as $img) {
  $idx++;
  if (!is_string($img) || strlen($img) < 50) continue;
  // allow data URL or bare base64
  if (strpos($img, 'data:') === 0) {
    $parts = explode(',', $img, 2);
    $b64 = isset($parts[1]) ? $parts[1] : '';
  } else {
    $b64 = $img;
  }
  $b64 = preg_replace('/[^a-zA-Z0-9+\/=]/', '', $b64);
  $bin = base64_decode($b64);
  if ($bin === false) continue;
  $fn = sprintf('face_%s_%02d.jpg', date('YmdHis'), $idx);
  $path = $dir . DIRECTORY_SEPARATOR . $fn;
  if (file_put_contents($path, $bin) !== false) {
    // return relative path for web usage (pages/ expects ../)
    $rel = '../known_faces/' . $folderName . '/' . $fn;
    $saved[] = $rel;
  }
}
if (count($saved) === 0) {
  echo json_encode(['success'=>false,'message'=>'No images saved']);
  exit;
}
// persist first saved image path to students.photo1 for display
$firstPath = $saved[0];
// best-effort DB update; ignore failures to keep face files usable
try {
  require_once __DIR__ . '/../db.php';
  if ($conn) {
    // student_db_id is expected to be the primary key `id`
    $sidNum = (int)$student_db_id;
    if ($sidNum > 0) {
      if ($stmt = $conn->prepare("UPDATE students SET photo1 = ? WHERE id = ? LIMIT 1")) {
        $stmt->bind_param('si', $firstPath, $sidNum);
        $stmt->execute();
        $stmt->close();
      }
    }
  }
} catch (\Throwable $e) {
  // swallow DB errors; file save already succeeded
}

// success
echo json_encode(['success'=>true,'saved'=>count($saved),'paths'=>$saved]);
exit;
?>