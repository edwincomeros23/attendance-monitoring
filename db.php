<?php
// Database configuration
$host = "localhost";
$user = "root";          // default MySQL username in XAMPP
$pass = "";              // default MySQL password is empty
$dbname = "attendance_db";

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
