<?php
// Redirect to login or dashboard based on auth status
session_start();

if (isset($_SESSION['id'])) {
  // User is logged in, redirect to dashboard
  header('Location: /attendance-monitoring/pages/dashboard.php');
  exit;
} else {
  // User is not logged in, redirect to signin
  header('Location: /attendance-monitoring/auth/signin.php');
  exit;
}
?>
