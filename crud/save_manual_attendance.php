<?php
// Save manual attendance for a student (upsert per student per day)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// ensure attendance table exists
$create = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    section VARCHAR(100) DEFAULT NULL,
    date DATE NOT NULL,
    status VARCHAR(50) DEFAULT NULL,
    time_in TIME DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_date (student_id, date)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create);

$student_db_id = isset($_POST['student_db_id']) ? (int)$_POST['student_db_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : null;
$time_in = isset($_POST['time_in']) && $_POST['time_in'] !== '' ? $_POST['time_in'] : null;
$time_out = isset($_POST['time_out']) && $_POST['time_out'] !== '' ? $_POST['time_out'] : null;
$section = isset($_POST['section']) ? trim($_POST['section']) : null;

if ($student_db_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student id']);
    exit;
}

$date = date('Y-m-d');

// Use INSERT ... ON DUPLICATE KEY UPDATE to upsert
$sql = "INSERT INTO attendance (student_id, section, date, status, time_in, time_out) VALUES (?, ?, ?, ?, ?, ?)"
      . " ON DUPLICATE KEY UPDATE section = VALUES(section), status = VALUES(status), time_in = VALUES(time_in), time_out = VALUES(time_out), updated_at = CURRENT_TIMESTAMP";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('isssss', $student_db_id, $section, $date, $status, $time_in, $time_out);
$ok = $stmt->execute();
if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Attendance saved', 'student_db_id' => $student_db_id]);

?>
