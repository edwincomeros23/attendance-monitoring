<?php
// Shared auth guard: ensures only signed-in users can access protected pages.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: /attendance-monitoring/auth/signin.php');
    exit;
}
