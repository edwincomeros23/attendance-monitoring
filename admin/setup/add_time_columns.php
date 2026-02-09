<?php
require_once '../../db.php';

// Add time_in and time_out columns to curriculum table
$sql1 = "ALTER TABLE curriculum ADD COLUMN IF NOT EXISTS time_in TIME NULL AFTER assigned_teacher";
$sql2 = "ALTER TABLE curriculum ADD COLUMN IF NOT EXISTS time_out TIME NULL AFTER time_in";

if ($conn->query($sql1)) {
    echo "✓ Added time_in column\n";
} else {
    echo "Note: " . $conn->error . "\n";
}

if ($conn->query($sql2)) {
    echo "✓ Added time_out column\n";
} else {
    echo "Note: " . $conn->error . "\n";
}

// Add sample times for existing records
$conn->query("UPDATE curriculum SET time_in = '08:00:00', time_out = '09:00:00' WHERE subject_name = 'English' AND time_in IS NULL");
$conn->query("UPDATE curriculum SET time_in = '09:00:00', time_out = '10:00:00' WHERE subject_name = 'Filipino' AND time_in IS NULL");
$conn->query("UPDATE curriculum SET time_in = '10:00:00', time_out = '11:00:00' WHERE subject_name = 'Mathematics' AND time_in IS NULL");
$conn->query("UPDATE curriculum SET time_in = '11:00:00', time_out = '12:00:00' WHERE subject_name = 'Science' AND time_in IS NULL");
$conn->query("UPDATE curriculum SET time_in = '13:00:00', time_out = '14:00:00' WHERE subject_name = 'T.L.E' AND time_in IS NULL");

echo "✓ Added sample time schedules\n";

echo "\nCurriculum table structure:\n";
$res = $conn->query("DESCRIBE curriculum");
while ($row = $res->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
