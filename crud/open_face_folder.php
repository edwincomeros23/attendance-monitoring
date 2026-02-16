<?php
// Opens the student's known_faces folder in Windows Explorer
// ONLY WORKS ON LOCALHOST / LOCAL SERVER (Windows)

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$studentId = isset($_POST['student_db_id']) ? (int)$_POST['student_db_id'] : 0;
$studentIdStr = isset($_POST['student_id_str']) ? trim($_POST['student_id_str']) : '';
$fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';

// Clean up studentIdStr: remove 'S_no' if present to avoid duplication
if (strpos($studentIdStr, 'S_no') === 0) {
    $studentIdStr = substr($studentIdStr, 4); // Remove first 4 chars
}

// Ensure the student ID string doesn't contain path traversal or other bad chars
$studentIdStr = preg_replace('/[^a-zA-Z0-9_\-]/', '', $studentIdStr);

// We need at least the student ID string or a fallback
if (empty($studentIdStr) && $studentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

$baseDir = __DIR__ . '/../known_faces';
$baseDir = str_replace('/', '\\', realpath($baseDir));

// Folders are named: S_no{Student_ID_String}_{Name}
// e.g. S_no1770448189171_Edwin_Comeros

$targetDir = '';

// Strategy 1: Search by Student ID String (most reliable for this project)
if (!empty($studentIdStr)) {
    // Check if folder starts with S_no{id}_
    $pattern = $baseDir . DIRECTORY_SEPARATOR . "S_no{$studentIdStr}_*";
    $matches = glob($pattern);
    if ($matches && count($matches) > 0) {
        $targetDir = $matches[0];
    }
}

// Strategy 2: If not found, try by DB ID (fallback)
if (empty($targetDir) && $studentId > 0) {
    $pattern = $baseDir . DIRECTORY_SEPARATOR . "S_no{$studentId}_*";
    $matches = glob($pattern);
    if ($matches && count($matches) > 0) {
        $targetDir = $matches[0];
    }
}

// Strategy 3: Create new if not found, using Student ID String preferably
if (empty($targetDir)) {
    if ($fullName) {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fullName);
        // Use string ID if available, else DB ID
        $useId = !empty($studentIdStr) ? $studentIdStr : $studentId;
        $newDirObj = __DIR__ . '/../known_faces/S_no' . $useId . '_' . $safeName;
        
        if (!is_dir($newDirObj)) mkdir($newDirObj, 0755, true);
        $targetDir = realpath($newDirObj);
    } else {
       // Cannot create without name, fallback to base
       $targetDir = $baseDir;
    }
}

if (!$targetDir || !is_dir($targetDir)) {
    $targetDir = $baseDir;
}

// Open explorer
$cmdPath = escapeshellarg($targetDir);
try {
    pclose(popen("start \"\" \"explorer\" $cmdPath", "r"));
    echo json_encode(['success' => true, 'message' => 'Folder opened']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to execute command']);
}
