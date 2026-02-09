<?php
// Save manual attendance for a student (upsert per student per day)
// Prevent PHP warnings/notices from breaking JSON responses and start output buffering
ini_set('display_errors', '0');
error_reporting(0);
if (!ob_get_level()) ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// basic request logging for debugging (appends POST data)
@file_put_contents(__DIR__ . '/save_manual_attendance.request.log', date('Y-m-d H:i:s') . " | REQUEST: " . json_encode($_POST) . "\n", FILE_APPEND | LOCK_EX);

// shutdown handler: catch fatal errors and ensure we log them and try to return JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        $dump = date('Y-m-d H:i:s') . " | SHUTDOWN ERROR: " . print_r($err, true) . "\n";
        @file_put_contents(__DIR__ . '/save_manual_attendance.log', $dump, FILE_APPEND | LOCK_EX);
        // try to send JSON-only response if nothing sent yet
        if (!headers_sent()) {
            while (ob_get_level()) ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Server fatal error', 'error' => $err]);
        }
    }
});

function send_json_and_exit($data) {
    // discard any buffered output which may corrupt JSON
    while (ob_get_level()) ob_end_clean();
    echo json_encode($data);
    exit;
}

// ensure attendance table exists
$create = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    section VARCHAR(100) DEFAULT NULL,
    date DATE NOT NULL,
    status VARCHAR(50) DEFAULT NULL,
    time_in TIME DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    edited_by INT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_date (student_id, date)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create);

// Ensure 'edited_by' column exists (if table existed before we added the column)
$colCheck = $conn->query("SHOW COLUMNS FROM attendance LIKE 'edited_by'");
 $hasEditedBy = false;
 if ($colCheck && $colCheck->num_rows > 0) {
     $hasEditedBy = true;
 } else {
     $alterSql = "ALTER TABLE attendance ADD COLUMN edited_by INT DEFAULT NULL AFTER time_out";
     if ($conn->query($alterSql)) {
         $hasEditedBy = true;
         @file_put_contents(__DIR__ . '/save_manual_attendance.request.log', date('Y-m-d H:i:s') . " | ALTERED attendance ADD edited_by\n", FILE_APPEND | LOCK_EX);
     } else {
         @file_put_contents(__DIR__ . '/save_manual_attendance.log', date('Y-m-d H:i:s') . " | ALTER failed: " . $conn->error . " | QUERY: " . $alterSql . "\n", FILE_APPEND | LOCK_EX);
         // leave $hasEditedBy false so we build INSERT without that column
     }
 }
 @file_put_contents(__DIR__ . '/save_manual_attendance.request.log', date('Y-m-d H:i:s') . " | hasEditedBy=" . ($hasEditedBy ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);

$student_db_id = isset($_POST['student_db_id']) ? (int)$_POST['student_db_id'] : 0;
 $status = isset($_POST['status']) ? trim($_POST['status']) : null;
 // normalize time inputs: treat empty, '-' or 'Absent' as NULL and convert valid times to H:i:s
 function normalize_time_input($val) {
     if (!isset($val) || $val === null) return null;
     $v = trim($val);
     if ($v === '' || $v === '-' || strtolower($v) === 'absent') return null;
     // try to parse with strtotime; if fails, return null
     $ts = strtotime($v);
     if ($ts === false) return null;
     return date('H:i:s', $ts);
 }

 $time_in = normalize_time_input(isset($_POST['time_in']) ? $_POST['time_in'] : null);
 $time_out = normalize_time_input(isset($_POST['time_out']) ? $_POST['time_out'] : null);
 $section = isset($_POST['section']) ? trim($_POST['section']) : null;
 $edited_by = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== '' ? (int)$_POST['teacher_id'] : null;

if ($student_db_id <= 0) {
    send_json_and_exit(['success' => false, 'message' => 'Invalid student id']);
}

$date = date('Y-m-d');

// handle delete action explicitly
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_sql = "DELETE FROM attendance WHERE student_id = " . intval($student_db_id) . " AND date = '" . $conn->real_escape_string($date) . "'";
    $dres = $conn->query($del_sql);
    if ($dres === false) {
        $logMsg = date('Y-m-d H:i:s') . " | DB delete error: " . $conn->error . " | query: " . $del_sql . "\n";
        @file_put_contents(__DIR__ . '/save_manual_attendance.log', $logMsg, FILE_APPEND | LOCK_EX);
        send_json_and_exit(['success' => false, 'message' => 'DB error: ' . $conn->error, 'query' => $del_sql]);
    }
    send_json_and_exit(['success' => true, 'message' => 'Deleted']);
}

// Use INSERT ... ON DUPLICATE KEY UPDATE to upsert
 $sql = "INSERT INTO attendance (student_id, section, date, status, time_in, time_out, edited_by) VALUES (?, ?, ?, ?, ?, ?, ?)"
     . " ON DUPLICATE KEY UPDATE section = VALUES(section), status = VALUES(status), time_in = VALUES(time_in), time_out = VALUES(time_out), edited_by = VALUES(edited_by), updated_at = CURRENT_TIMESTAMP";

// Use a safe, escaped query to avoid bind_param reference issues
$sec_esc = $section === null ? "NULL" : "'" . $conn->real_escape_string($section) . "'";
$status_esc = $status === null ? "NULL" : "'" . $conn->real_escape_string($status) . "'";
$time_in_esc = $time_in === null ? "NULL" : "'" . $conn->real_escape_string($time_in) . "'";
$time_out_esc = $time_out === null ? "NULL" : "'" . $conn->real_escape_string($time_out) . "'";
$edited_by_esc = $edited_by === null ? "NULL" : intval($edited_by);

$insert_sql = "INSERT INTO attendance (student_id, section, date, status, time_in, time_out, edited_by) VALUES (" . intval($student_db_id) . ", " . $sec_esc . ", '" . $conn->real_escape_string($date) . "', " . $status_esc . ", " . $time_in_esc . ", " . $time_out_esc . ", " . $edited_by_esc . ")"
    . " ON DUPLICATE KEY UPDATE section = VALUES(section), status = VALUES(status), time_in = VALUES(time_in), time_out = VALUES(time_out), edited_by = VALUES(edited_by), updated_at = CURRENT_TIMESTAMP";

$res = $conn->query($insert_sql);
if (!$res) {
    // write a short server-side log for debugging (won't be shown to end users by default)
    $logMsg = date('Y-m-d H:i:s') . " | DB error: " . $conn->error . " | query: " . $insert_sql . "\n";
    @file_put_contents(__DIR__ . '/save_manual_attendance.log', $logMsg, FILE_APPEND | LOCK_EX);
    // also write the full request for convenience
    @file_put_contents(__DIR__ . '/save_manual_attendance.request.log', date('Y-m-d H:i:s') . " | FAILED INSERT | POST: " . json_encode($_POST) . " | QUERY: " . $insert_sql . "\n", FILE_APPEND | LOCK_EX);
    send_json_and_exit(['success' => false, 'message' => 'DB error: ' . $conn->error, 'query' => $insert_sql]);
} else {
    // log success (small entry)
    @file_put_contents(__DIR__ . '/save_manual_attendance.request.log', date('Y-m-d H:i:s') . " | OK INSERT | POST: " . json_encode($_POST) . "\n", FILE_APPEND | LOCK_EX);
}

send_json_and_exit(['success' => true, 'message' => 'Attendance saved', 'student_db_id' => $student_db_id]);

?>
