<?php
require_once __DIR__ . '/../db.php';

// One-time backfill for attendance.time_in using earliest correct recognition_logs.
// This only fills missing time_in values and does not overwrite existing values.

$updateSql = "UPDATE attendance a
  JOIN (
    SELECT actual_id AS student_id,
           log_date,
           section,
           MIN(TIME(created_at)) AS first_time
    FROM recognition_logs
    WHERE is_correct = 1
    GROUP BY actual_id, log_date, section
  ) r ON r.student_id = a.student_id
     AND r.log_date = a.date
     AND (a.section = r.section OR r.section = '' OR r.section IS NULL)
  SET a.time_in = r.first_time
  WHERE a.time_in IS NULL";

$result = $conn->query($updateSql);
if ($result === false) {
  echo "Backfill failed: " . $conn->error;
  exit;
}

echo "Backfill complete. Updated rows: " . $conn->affected_rows;
?>
