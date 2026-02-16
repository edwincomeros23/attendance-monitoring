<?php
ini_set('display_errors', '0');
error_reporting(0);
if (!ob_get_level()) ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

function send_json($data) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode($data);
    exit;
}

$predictedId = isset($_POST['predicted_id']) ? (int)$_POST['predicted_id'] : 0;
$actualId = isset($_POST['actual_id']) ? (int)$_POST['actual_id'] : 0;
$section = isset($_POST['section']) ? trim($_POST['section']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';

if ($predictedId <= 0 || $actualId <= 0) {
    send_json(['success' => false, 'message' => 'Invalid predicted or actual id']);
}

if ($date === '' || !DateTime::createFromFormat('Y-m-d', $date)) {
    $date = date('Y-m-d');
}

$create = "CREATE TABLE IF NOT EXISTS recognition_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    predicted_id INT NOT NULL,
    actual_id INT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    section VARCHAR(100) DEFAULT NULL,
    log_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create);

$isCorrect = ($predictedId === $actualId) ? 1 : 0;
$stmt = $conn->prepare('INSERT INTO recognition_logs (predicted_id, actual_id, is_correct, section, log_date) VALUES (?, ?, ?, ?, ?)');
if (!$stmt) {
    send_json(['success' => false, 'message' => 'DB error: ' . $conn->error]);
}
$stmt->bind_param('iiiss', $predictedId, $actualId, $isCorrect, $section, $date);
if (!$stmt->execute()) {
    $stmt->close();
    send_json(['success' => false, 'message' => 'DB error: ' . $conn->error]);
}
$stmt->close();

send_json(['success' => true, 'message' => 'Saved', 'is_correct' => $isCorrect]);
