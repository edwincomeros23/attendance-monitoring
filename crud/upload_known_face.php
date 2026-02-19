error_reporting(0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$studentId = isset($_POST['student_db_id']) ? (int)$_POST['student_db_id'] : 0;
// Note: Frontend currently sends student_db_id. If updated to send string ID, leverage it.
$studentIdStr = isset($_POST['student_id_str']) ? trim($_POST['student_id_str']) : '';
$fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';

if (strpos($studentIdStr, 'S_no') === 0) {
    $studentIdStr = substr($studentIdStr, 4);
}
$studentIdStr = preg_replace('/[^a-zA-Z0-9_\-]/', '', $studentIdStr);

// Prefer string ID, fallback to DB ID
$lookupId = !empty($studentIdStr) ? $studentIdStr : $studentId;

if (empty($lookupId) && $studentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

// Sanitize name for directory
$safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fullName);
$baseDir = __DIR__ . '/../known_faces';
if (!is_dir($baseDir)) {
    if (!@mkdir($baseDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Server write restricted (Render). Upload skipped.']);
        exit;
    }
}

$targetDir = '';
$existingDirs = glob($baseDir . "/S_no{$lookupId}_*");
if ($existingDirs && count($existingDirs) > 0) {
    $targetDir = $existingDirs[0];
} else {
    // Create new
    $strIdToUse = !empty($studentIdStr) ? $studentIdStr : $studentId;
    $targetDir = $baseDir . "/S_no{$strIdToUse}_{$safeName}";
}

if (!is_dir($targetDir)) {
    if (!@mkdir($targetDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Server write restricted (Render). Upload skipped.']);
        exit;
    }
}

if (!is_writable($targetDir)) {
    echo json_encode(['success' => false, 'message' => 'Directory is not writable (Render). Upload skipped.']);
    exit;
}

$file = $_FILES['photo'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

// Unique filename
$filename = "manual_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
$destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode([
        'success' => true,
        'path' => 'known_faces/' . basename($targetDir) . '/' . $filename
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}
