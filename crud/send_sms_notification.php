<?php
/**
 * AJAX Endpoint for sending SMS notifications
 * Called from livecamera.php when student is detected
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/sms_functions.php';
require_once __DIR__ . '/../config/email_functions.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$studentId = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$eventType = isset($_POST['event_type']) ? $_POST['event_type'] : '';

if (!$studentId || !in_array($eventType, ['time_in', 'time_out'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get student details
$stmt = $conn->prepare("SELECT full_name, student_id FROM students WHERE id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Send notifications
$smsSuccess = sendAttendanceNotification(
    $studentId,
    $student['full_name'],
    $student['student_id'],
    $eventType
);

$emailSuccess = sendAttendanceEmailNotification(
    $studentId,
    $student['full_name'],
    $student['student_id'],
    $eventType
);

$telegramSuccess = sendAttendanceTelegramNotification(
    $studentId,
    $student['full_name'],
    $student['student_id'],
    $eventType
);

echo json_encode([
    'success' => ($smsSuccess || $emailSuccess || $telegramSuccess),
    'sms' => $smsSuccess,
    'email' => $emailSuccess,
    'telegram' => $telegramSuccess,
    'message' => 'Notification processed'
]);
