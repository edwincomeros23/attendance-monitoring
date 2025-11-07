<?php
// enroll_face.php
// Accepts JSON POST { label: string, image: dataURL }
header('Content-Type: application/json; charset=utf-8');

$body = file_get_contents('php://input');
if (!$body) {
    echo json_encode(['ok' => false, 'message' => 'No input']);
    exit;
}

$data = json_decode($body, true);
if (!$data || !isset($data['label']) || !isset($data['image'])) {
    echo json_encode(['ok' => false, 'message' => 'Invalid payload']);
    exit;
}

$label = preg_replace('/[^a-zA-Z0-9_\- ]/', '', trim($data['label']));
$image = $data['image'];

$baseDir = realpath(__DIR__ . '/../known_faces');
if ($baseDir === false) {
    // try to create
    $baseDir = __DIR__ . '/../known_faces';
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true)) {
        echo json_encode(['ok' => false, 'message' => 'Could not access known_faces folder']);
        exit;
    }
}

$labelDir = $baseDir . DIRECTORY_SEPARATOR . $label;
if (!is_dir($labelDir)) {
    if (!mkdir($labelDir, 0755, true)) {
        echo json_encode(['ok' => false, 'message' => 'Could not create label folder']);
        exit;
    }
}

// image can be data:image/jpeg;base64,... or plain base64
if (preg_match('/^data:\s*image\/(png|jpeg|jpg);base64,/', $image, $m)) {
    $imgData = substr($image, strpos($image, ',') + 1);
    $imgData = base64_decode($imgData);
    $ext = ($m[1] === 'png') ? 'png' : 'jpg';
} else {
    // try to treat as plain base64
    $imgData = base64_decode($image);
    $ext = 'jpg';
}

if ($imgData === false) {
    echo json_encode(['ok' => false, 'message' => 'Invalid image data']);
    exit;
}

$fname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$path = $labelDir . DIRECTORY_SEPARATOR . $fname;
if (file_put_contents($path, $imgData) === false) {
    echo json_encode(['ok' => false, 'message' => 'Failed to save image']);
    exit;
}

// Build a public URL relative to site root
$labelEnc = rawurlencode($label);
$fileEnc = rawurlencode($fname);
$url = '/thesis2/known_faces/' . $labelEnc . '/' . $fileEnc;

echo json_encode(['ok' => true, 'message' => 'Saved', 'url' => $url, 'filename' => $fname]);

?>
