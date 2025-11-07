<?php
// stream_status.php
// Simple JSON status for the HLS manifest so you can check from a browser.
header('Content-Type: application/json; charset=utf-8');
$manifest = __DIR__ . '/stream/index.m3u8';
if (file_exists($manifest)) {
    $stat = stat($manifest);
    echo json_encode([
        'exists' => true,
        'path' => '/thesis2/stream/index.m3u8',
        'size' => filesize($manifest),
        'mtime' => date('c', $stat['mtime'])
    ]);
} else {
    echo json_encode([
        'exists' => false,
        'message' => 'Manifest not found',
        'expected_path' => '/thesis2/stream/index.m3u8'
    ]);
}
