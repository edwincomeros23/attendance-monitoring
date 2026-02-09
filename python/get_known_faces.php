<?php
// get_known_faces.php
// Returns a JSON structure of known face images organized by label.
// FIXED: Only loads training data for students in the currently selected section
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

$baseDir = realpath(__DIR__ . '/../known_faces');
if ($baseDir === false || !is_dir($baseDir)) {
    echo json_encode(['ok' => false, 'message' => 'known_faces folder not found']);
    exit;
}

// Get current section from query params (same as livecamera.php uses)
$year = isset($_GET['year']) ? trim($_GET['year']) : '';
$section = isset($_GET['section']) ? trim($_GET['section']) : '';

// Build list of allowed labels (student DB IDs) based on current section
$allowedLabels = [];
if ($year && $section) {
    // Convert year to database format
    $year_level = is_numeric($year) ? ("Grade " . $year) : $year;
    
    // Extract section name
    $section_name = $section;
    if (preg_match('/^(\d+)[-\s]*(.+)$/', $section, $matches)) {
        $section_name = $matches[2];
    }
    
    // Query enrolled students
    if ($stmt = $conn->prepare("SELECT id, student_id FROM students WHERE year_level = ? AND section = ?")) {
        $stmt->bind_param('ss', $year_level, $section_name);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            // Allow legacy folder names based on DB id
            $allowedLabels[] = 'S' . (int)$row['id'];

            // Also allow folder names based on student_id
            $sidRaw = isset($row['student_id']) ? trim((string)$row['student_id']) : '';
            if ($sidRaw !== '') {
                $sidSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sidRaw);
                if ($sidSafe !== '') {
                    if (stripos($sidSafe, 'S') !== 0) {
                        $sidSafe = 'S' . $sidSafe;
                    }
                    $allowedLabels[] = $sidSafe;
                }
            }
        }
        $stmt->close();
    }
}

$result = [];
foreach (scandir($baseDir) as $label) {
    if ($label === '.' || $label === '..') continue;
    
    // SECURITY: Only return faces for students in current section
    if (!empty($allowedLabels)) {
        $allowed = false;
        foreach ($allowedLabels as $al) {
            if ($label === $al || strpos($label, $al . '_') === 0) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) continue;
    }
    
    $labelDir = $baseDir . DIRECTORY_SEPARATOR . $label;
    if (!is_dir($labelDir)) continue;
    $files = [];
    foreach (scandir($labelDir) as $f) {
        if (in_array($f, ['.', '..'])) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) continue;
        // Build a URL relative to the site root. Adjust if your site is mounted differently.
        $url = '/attendance-monitoring/known_faces/' . rawurlencode($label) . '/' . rawurlencode($f);
        $files[] = $url;
    }
    if (count($files) > 0) {
        $result[$label] = $files;
    }
}

echo json_encode(['ok' => true, 'data' => $result]);
