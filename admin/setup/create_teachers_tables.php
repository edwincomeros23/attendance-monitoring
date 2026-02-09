<?php
require_once '../../db.php';
header('Content-Type: text/plain; charset=utf-8');

$sql1 = "CREATE TABLE IF NOT EXISTS `teachers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_id` VARCHAR(100) NOT NULL,
  `first_name` VARCHAR(150) NOT NULL,
  `middle_initial` VARCHAR(10) DEFAULT NULL,
  `last_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `department` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_faculty_id` (`faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$sql2 = "CREATE TABLE IF NOT EXISTS `teacher_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `subject_code` VARCHAR(100) DEFAULT NULL,
  `time` VARCHAR(100) DEFAULT NULL,
  `day` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_teacher` (`teacher_id`),
  CONSTRAINT `fk_schedule_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

echo "Creating teachers table...\n";
if ($conn->query($sql1) === TRUE) {
    echo "teachers table OK\n";
} else {
    echo "Error creating teachers table: " . $conn->error . "\n";
}

echo "Creating teacher_schedules table...\n";
if ($conn->query($sql2) === TRUE) {
    echo "teacher_schedules table OK\n";
} else {
    echo "Error creating teacher_schedules table: " . $conn->error . "\n";
}

echo "Done.\n";
?>