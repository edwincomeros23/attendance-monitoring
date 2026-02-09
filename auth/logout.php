<?php
session_start();
session_unset(); // remove all session variables
session_destroy(); // destroy the session
header("Location: /attendance-monitoring/auth/signin.php"); // redirect to signin
exit;
?>
