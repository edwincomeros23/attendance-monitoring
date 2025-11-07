<?php
// get_known_faces.php
// Returns a JSON structure of known face images organized by label.
header('Content-Type: application/json; charset=utf-8');

$baseDir = realpath(__DIR__ . '/../known_faces');
if ($baseDir === false || !is_dir($baseDir)) {
    echo json_encode(['ok' => false, 'message' => 'known_faces folder not found']);
    exit;
}

$result = [];
foreach (scandir($baseDir) as $label) {
    if ($label === '.' || $label === '..') continue;
    $labelDir = $baseDir . DIRECTORY_SEPARATOR . $label;
    if (!is_dir($labelDir)) continue;
    $files = [];
    foreach (scandir($labelDir) as $f) {
        if (in_array($f, ['.', '..'])) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) continue;
        // Build a URL relative to the site root. Adjust if your site is mounted differently.
        $url = '/thesis2/known_faces/' . rawurlencode($label) . '/' . rawurlencode($f);
        $files[] = $url;
    }
    if (count($files) > 0) {
        $result[$label] = $files;
    }
}

echo json_encode(['ok' => true, 'data' => $result]);
