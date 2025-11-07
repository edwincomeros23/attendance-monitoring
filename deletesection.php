<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php'; // expects $conn (mysqli)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}
// Check if section exists and get image path (if any)
$imgPath = null;
$check = $conn->prepare("SELECT image FROM sections WHERE id = ? LIMIT 1");
if ($check) {
    $check->bind_param('i', $id);
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        $imgPath = $row['image'] ?? null;
    }
    $check->close();
}

$stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'DB prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
if ($ok === false) {
    $err = $stmt->error ?: $conn->error;
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'DB execute error: ' . $err]);
    exit;
}
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    // remove image file if exists and looks like a local image
    if ($imgPath) {
        $localPrefix = './images/';
        if (strpos($imgPath, $localPrefix) === 0) {
            $file = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . basename($imgPath);
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// no rows affected
// check if the row actually exists (maybe already deleted)
$exists = $conn->prepare("SELECT COUNT(*) AS cnt FROM sections WHERE id = ?");
if ($exists) {
    $exists->bind_param('i', $id);
    $exists->execute();
    $r = $exists->get_result()->fetch_assoc();
    $exists->close();
    if ($r && (int)$r['cnt'] === 0) {
        echo json_encode(['success' => false, 'error' => 'No section found with id ' . $id]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'No row deleted or DB error']);
exit;