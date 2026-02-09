<?php
require_once '../../db.php';

echo "=== Populating teacher_id from existing assignments ===\n\n";

// Get all curriculum records with assigned_teacher text
$res = $conn->query("SELECT id, assigned_teacher FROM curriculum WHERE assigned_teacher IS NOT NULL AND assigned_teacher != ''");

if (!$res) {
    echo "Error: " . $conn->error . "\n";
    exit;
}

// Get all teachers with their names
$teachers = [];
$teacherRes = $conn->query("SELECT id, first_name, middle_initial, last_name FROM teachers");
while ($t = $teacherRes->fetch_assoc()) {
    $name = trim($t['first_name'] . ' ' . ($t['middle_initial'] ? $t['middle_initial'] . ' ' : '') . $t['last_name']);
    $teachers[] = ['id' => $t['id'], 'name' => $name];
}

$updated = 0;
while ($row = $res->fetch_assoc()) {
    $assignedTeacher = $row['assigned_teacher'];
    $cleanName = preg_replace('/^(Mr\.|Mrs\.|Ms\.|Dr\.)\s*/i', '', $assignedTeacher);
    $cleanName = trim($cleanName);
    
    // Find matching teacher
    foreach ($teachers as $teacher) {
        if (stripos($teacher['name'], $cleanName) !== false || stripos($cleanName, $teacher['name']) !== false) {
            $stmt = $conn->prepare("UPDATE curriculum SET teacher_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $teacher['id'], $row['id']);
            if ($stmt->execute()) {
                echo "✓ Updated: '{$assignedTeacher}' -> Teacher ID {$teacher['id']} ({$teacher['name']})\n";
                $updated++;
            }
            break;
        }
    }
}

echo "\nTotal updated: {$updated} records\n";

// Show results
echo "\n=== Updated Assignments ===\n";
$res = $conn->query("SELECT c.id, c.subject_name, c.grade_level, c.teacher_id, 
                     CONCAT(t.first_name, ' ', IFNULL(CONCAT(t.middle_initial, '. '), ''), t.last_name) as teacher_name
                     FROM curriculum c
                     LEFT JOIN teachers t ON c.teacher_id = t.id
                     ORDER BY c.grade_level, c.subject_name");

while ($row = $res->fetch_assoc()) {
    $teacher = $row['teacher_name'] ? $row['teacher_name'] : 'Not assigned';
    echo "  {$row['subject_name']} (Grade {$row['grade_level']}) -> {$teacher}\n";
}

echo "\n✓ Done!\n";
?>
