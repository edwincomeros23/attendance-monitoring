<?php
include '../../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $year_level = $_POST['year_level'];
    $section = $_POST['section'];
    $guardian = $_POST['guardian'];
    $phone_no = $_POST['phone_no'];
    $guardian_email = isset($_POST['guardian_email']) ? $_POST['guardian_email'] : '';

    // handle photo upload
    $photo1 = null;
    if (isset($_FILES['photo1']) && $_FILES['photo1']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['photo1']['name']);
        $targetFilePath = $targetDir . $fileName;
        move_uploaded_file($_FILES['photo1']['tmp_name'], $targetFilePath);
        $photo1 = $fileName;
    }

    if (empty($id)) {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO students 
            (student_id, full_name, birthdate, gender, year_level, section, guardian, phone_no, guardian_email, photo1) 
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssss", $student_id, $full_name, $birthdate, $gender, $year_level, $section, $guardian, $phone_no, $guardian_email, $photo1);
        $stmt->execute();
    } else {
        // UPDATE
        if ($photo1) {
            $stmt = $conn->prepare("UPDATE students SET student_id=?, full_name=?, birthdate=?, gender=?, year_level=?, section=?, guardian=?, phone_no=?, guardian_email=?, photo1=? WHERE id=?");
            $stmt->bind_param("ssssssssssi", $student_id, $full_name, $birthdate, $gender, $year_level, $section, $guardian, $phone_no, $guardian_email, $photo1, $id);
        } else {
            $stmt = $conn->prepare("UPDATE students SET student_id=?, full_name=?, birthdate=?, gender=?, year_level=?, section=?, guardian=?, phone_no=?, guardian_email=? WHERE id=?");
            $stmt->bind_param("sssssssssi", $student_id, $full_name, $birthdate, $gender, $year_level, $section, $guardian, $phone_no, $guardian_email, $id);
        }
        $stmt->execute();
    }

    header("Location: students.php");
    exit();
}
?>
