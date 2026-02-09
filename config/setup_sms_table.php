<?php
/**
 * Setup SMS Notifications Table
 * Run this once to create the required database table
 */

require_once __DIR__ . '/../db.php';

$createTableSQL = "
CREATE TABLE IF NOT EXISTS sms_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    event_type VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'pending',
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $conn->query($createTableSQL);
    echo "✓ SMS notifications table created successfully!<br>";
    echo "You can now use the SMS notification feature.";
} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage();
}
