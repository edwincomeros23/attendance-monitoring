<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $grade_level = trim($_POST['grade_level']);
    $assigned_teacher = trim($_POST['assigned_teacher']);

    // Create table if not exists
    $createTable = "CREATE TABLE IF NOT EXISTS curriculum (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_code VARCHAR(50) NOT NULL,
        subject_name VARCHAR(200) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        assigned_teacher VARCHAR(200),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($createTable);

    if ($id > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE curriculum SET subject_code=?, subject_name=?, grade_level=?, assigned_teacher=? WHERE id=?");
        $stmt->bind_param('ssssi', $subject_code, $subject_name, $grade_level, $assigned_teacher, $id);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO curriculum (subject_code, subject_name, grade_level, assigned_teacher) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $subject_code, $subject_name, $grade_level, $assigned_teacher);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $id > 0 ? $id : $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>
