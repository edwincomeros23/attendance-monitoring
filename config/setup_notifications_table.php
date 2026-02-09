<?php
/**
 * Setup Notifications Log Table
 * Run this once to create the required database table
 */

require_once __DIR__ . '/../db.php';

$createTableSQL = "
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    channel VARCHAR(10) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    event_type VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'pending',
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_channel (channel),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$alterStudentsSQL = "
ALTER TABLE students
  ADD COLUMN guardian_email VARCHAR(255) NULL AFTER phone_no;
";

try {
    $conn->query($createTableSQL);
    echo "✓ notification_logs table created successfully!<br>";
} catch (Exception $e) {
    echo "✗ Error creating notification_logs table: " . $e->getMessage() . "<br>";
}

// Add guardian_email column if it does not exist
try {
    $conn->query($alterStudentsSQL);
    echo "✓ guardian_email column added to students table.<br>";
} catch (Exception $e) {
    // Ignore if column already exists
    echo "guardian_email column already exists or could not be added.<br>";
}
