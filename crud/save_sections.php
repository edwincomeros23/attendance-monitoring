<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Forbidden']);
  exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$grade = isset($_POST['grade']) ? trim($_POST['grade']) : '';
$section = isset($_POST['section']) ? trim($_POST['section']) : '';

if ($action === '' || $grade === '' || $section === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
  exit;
}

$grade = preg_replace('/[^0-9]/', '', $grade);
$section = preg_replace('/\s+/', ' ', $section);
$section = trim($section);

if ($grade === '' || $section === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid parameters']);
  exit;
}

$configPath = __DIR__ . '/../config/sections.php';
$sectionsConfig = file_exists($configPath) ? include $configPath : [];
if (!is_array($sectionsConfig)) {
  $sectionsConfig = [];
}
if (!isset($sectionsConfig[$grade]) || !is_array($sectionsConfig[$grade])) {
  $sectionsConfig[$grade] = [];
}

$normalizedExisting = array_map('mb_strtolower', $sectionsConfig[$grade]);
$normalizedSection = mb_strtolower($section);

if ($action === 'add') {
  if (in_array($normalizedSection, $normalizedExisting, true)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'Section already exists']);
    exit;
  }
  $sectionsConfig[$grade][] = $section;
  natcasesort($sectionsConfig[$grade]);
  $sectionsConfig[$grade] = array_values($sectionsConfig[$grade]);
} elseif ($action === 'delete') {
  $sectionsConfig[$grade] = array_values(array_filter(
    $sectionsConfig[$grade],
    function ($item) use ($normalizedSection) {
      return mb_strtolower($item) !== $normalizedSection;
    }
  ));
} else {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid action']);
  exit;
}

$export = "<?php\n// Shared sections list for reports and dashboard.\n// Update this file to change visible sections across the system.\n\nreturn " . var_export($sectionsConfig, true) . ";\n";
$written = file_put_contents($configPath, $export, LOCK_EX);
if ($written === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to save sections']);
  exit;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'sections' => $sectionsConfig]);
