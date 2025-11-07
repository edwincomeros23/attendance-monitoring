<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Invalid method']);
    exit;
}

$name = trim($_POST['sectionName'] ?? '');
$grade = (int)($_POST['gradeLevel'] ?? 0);

if ($name === '' || !in_array($grade, [7,8,9,10], true)) {
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO sections (name, grade_level) VALUES (?, ?)");
$stmt->bind_param('si', $name, $grade);
$ok = $stmt->execute();
$insertId = $stmt->insert_id;
$stmt->close();

if (!$ok) {
    echo json_encode(['success'=>false,'error'=>'DB insert failed']);
    exit;
}

// handle uploaded image (optional)
$imgPath = './images/peridot.jpg';
if (!empty($_FILES['sectionImage']) && $_FILES['sectionImage']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['sectionImage'];
    // basic validation: allow only common image types and size limit
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($f['type'], $allowed, true)) {
        // ignore invalid type, keep default
    } else {
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $base = 'section_' . time() . '_' . bin2hex(random_bytes(4));
        $target = $targetDir . $base . '.' . $ext;
        if (move_uploaded_file($f['tmp_name'], $target)) {
            // store relative path for web
            $imgPath = './images/' . basename($target);
            // try to update DB record with image path if table has image column
            $u = $conn->prepare("UPDATE sections SET image = ? WHERE id = ?");
            if ($u) {
                $u->bind_param('si', $imgPath, $insertId);
                $u->execute();
                $u->close();
            }
        }
    }
}

echo json_encode([
  'success' => true,
  'id' => $insertId,
  'name' => $name,
  'grade' => $grade,
  'img' => $imgPath
]);