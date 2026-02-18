<?php
/**
 * stream_config.php
 * GET  → returns current stream URL config as JSON
 * POST → saves ngrok/stream URL to a local config file
 */
header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/../config/stream_url.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['stream_url'] ?? '');
    // Basic validation: must be empty or a valid http/https URL
    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid URL']);
        exit;
    }
    $data = ['stream_url' => $url, 'updated_at' => date('c')];
    file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'stream_url' => $url]);
    exit;
}

// GET
if (file_exists($configFile)) {
    $data = json_decode(file_get_contents($configFile), true);
    echo json_encode(['success' => true, 'stream_url' => $data['stream_url'] ?? '', 'updated_at' => $data['updated_at'] ?? null]);
} else {
    echo json_encode(['success' => true, 'stream_url' => '', 'updated_at' => null]);
}
