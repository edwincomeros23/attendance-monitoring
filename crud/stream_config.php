<?php
date_default_timezone_set('Asia/Manila');
error_reporting(0);
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

    // Ensure config directory exists
    $configDir = dirname($configFile);
    if (!is_dir($configDir)) {
        if (!@mkdir($configDir, 0777, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Cannot create config directory']);
            exit;
        }
    }

    $data = ['stream_url' => $url, 'updated_at' => date('c')];
    $written = @file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => $written !== false,
        'stream_url' => $url,
        'persistence' => $written !== false ? 'file' : 'skipped'
    ]);
    exit;
}

// GET
if (file_exists($configFile)) {
    $data = json_decode(file_get_contents($configFile), true);
    echo json_encode(['success' => true, 'stream_url' => $data['stream_url'] ?? '', 'updated_at' => $data['updated_at'] ?? null]);
} else {
    echo json_encode(['success' => true, 'stream_url' => '', 'updated_at' => null]);
}
