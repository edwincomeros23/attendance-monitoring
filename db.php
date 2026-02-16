<?php
// Database configuration - supports both local XAMPP and Render.com deployment
if (getenv('RENDER') === 'true' || !empty(getenv('DB_HOST'))) {
    // Render.com or cloud environment
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');
    $dbname = getenv('DB_NAME') ?: 'attendance_monitoring';
} else {
    // Local XAMPP environment
    $host = "localhost";
    $user = "root";          // default MySQL username in XAMPP
    $pass = "";              // default MySQL password is empty
    $dbname = "attendance_db";
}

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    // Check if this is an API request
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '_api.php') !== false || 
        strpos($_SERVER['REQUEST_URI'] ?? '', 'curriculum_api') !== false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: set charset to avoid encoding issues
$conn->set_charset("utf8");
?>
