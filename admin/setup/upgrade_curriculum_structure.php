<?php
require_once '../../db.php';

echo "=== Upgrading Curriculum Table Structure ===\n\n";

// Step 1: Add new columns
echo "Step 1: Adding new columns...\n";
$columns = [
    "ALTER TABLE curriculum ADD COLUMN IF NOT EXISTS teacher_id INT NULL AFTER assigned_teacher",
    "ALTER TABLE curriculum ADD COLUMN IF NOT EXISTS day_of_week VARCHAR(20) NULL AFTER time_out",
    "ALTER TABLE curriculum ADD COLUMN IF NOT EXISTS room VARCHAR(50) NULL AFTER day_of_week",
    "ALTER TABLE curriculum ADD COLUMN IF NOT EXISTS section VARCHAR(100) NULL AFTER grade_level"
];

foreach ($columns as $sql) {
    if ($conn->query($sql)) {
        echo "  ✓ Column added successfully\n";
    } else {
        echo "  Note: " . $conn->error . "\n";
    }
}

// Step 2: Migrate existing teacher names to teacher_id
echo "\nStep 2: Migrating teacher assignments...\n";
$res = $conn->query("SELECT id, assigned_teacher FROM curriculum WHERE assigned_teacher IS NOT NULL AND assigned_teacher != ''");
if ($res) {
    $teacherRes = $conn->query("SELECT id, first_name, middle_initial, last_name FROM teachers");
    $teachers = [];
    while ($t = $teacherRes->fetch_assoc()) {
        $name = trim($t['first_name'] . ' ' . ($t['middle_initial'] ? $t['middle_initial'] . ' ' : '') . $t['last_name']);
        $teachers[$t['id']] = $name;
    }
    
    $migrated = 0;
    while ($row = $res->fetch_assoc()) {
        $assignedTeacher = $row['assigned_teacher'];
        $cleanName = preg_replace('/^(Mr\.|Mrs\.|Ms\.|Dr\.)\s*/i', '', $assignedTeacher);
        
        // Find matching teacher
        foreach ($teachers as $teacherId => $teacherName) {
            if (stripos($cleanName, $teacherName) !== false || stripos($teacherName, $cleanName) !== false) {
                $stmt = $conn->prepare("UPDATE curriculum SET teacher_id = ? WHERE id = ?");
                $stmt->bind_param('ii', $teacherId, $row['id']);
                $stmt->execute();
                $migrated++;
                echo "  ✓ Migrated: '{$assignedTeacher}' -> Teacher ID {$teacherId}\n";
                break;
            }
        }
    }
    echo "  Total migrated: {$migrated} records\n";
}

// Step 3: Add sample days for existing records
echo "\nStep 3: Adding sample schedule days...\n";
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$res = $conn->query("SELECT id FROM curriculum WHERE day_of_week IS NULL");
$i = 0;
while ($row = $res->fetch_assoc()) {
    $day = $days[$i % count($days)];
    $conn->query("UPDATE curriculum SET day_of_week = '{$day}' WHERE id = {$row['id']}");
    $i++;
}
echo "  ✓ Added sample days\n";

// Step 4: Show final structure
echo "\n=== Final Curriculum Table Structure ===\n";
$res = $conn->query("DESCRIBE curriculum");
while ($row = $res->fetch_assoc()) {
    echo "  {$row['Field']} ({$row['Type']}) " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "\n=== Sample Data ===\n";
$res = $conn->query("SELECT c.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name 
                     FROM curriculum c 
                     LEFT JOIN teachers t ON c.teacher_id = t.id 
                     LIMIT 3");
while ($row = $res->fetch_assoc()) {
    echo "\nSubject: {$row['subject_name']}\n";
    echo "  Grade: {$row['grade_level']}, Teacher ID: {$row['teacher_id']} ({$row['teacher_name']})\n";
    echo "  Day: {$row['day_of_week']}, Time: {$row['time_in']} - {$row['time_out']}\n";
}

echo "\n✓ Upgrade complete!\n";
?>
