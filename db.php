<?php
// Database configuration - supports both local XAMPP and Render.com deployment
if (getenv('RENDER') === 'true' || !empty(getenv('DB_HOST'))) {
    // Render.com or cloud environment (Aiven, etc.)
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');
    $dbname = getenv('DB_NAME') ?: 'attendance_monitoring';
    $port = getenv('DB_PORT') ?: 3306;

    // Enable SSL for Aiven (if required)
    $mysqli = mysqli_init();
    if (getenv('DB_SSL') === 'true' || getenv('DB_SSLMODE') === 'REQUIRED' || getenv('DB_SSLMODE') === 'required') {
        // Use built-in CA if available, or skip CA verification (not recommended for production)
        $ca = getenv('DB_SSL_CA') ?: null;
        if ($ca) {
            $mysqli->ssl_set($ca, null, null, null, null);
        } else {
            $mysqli->ssl_set(null, null, null, null, null);
        }
    }
    @$mysqli->real_connect($host, $user, $pass, $dbname, (int)$port, null, MYSQLI_CLIENT_SSL);
    $conn = $mysqli;
} else {
    // Local XAMPP environment
    $host = "localhost";
    $user = "root";          // default MySQL username in XAMPP
    $pass = "";              // default MySQL password is empty
    $dbname = "attendance_db";
    $port = 3306;
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
}

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
