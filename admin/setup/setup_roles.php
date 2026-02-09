<?php
require_once '../../db.php';

// Add role column to users table if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'teacher' AFTER password");
    echo "✓ Added 'role' column to users table<br>";
} else {
    echo "✓ 'role' column already exists<br>";
}

// Set admin role for the admin user
$conn->query("UPDATE users SET role = 'admin' WHERE username = 'admin'");
echo "✓ Set admin role for admin user<br>";

// Set all other users to teacher role
$conn->query("UPDATE users SET role = 'teacher' WHERE role IS NULL OR role = ''");
echo "✓ Set teacher role for all other users<br>";

echo "<br>✅ Role system setup complete!";
?>
