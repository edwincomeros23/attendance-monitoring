<?php
session_start();
include "../db.php"; // include your db connection

// If already logged in, go to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: /attendance-monitoring/pages/dashboard.php");
    exit;
}

$error = "";
$selected_year = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $selected_year = isset($_POST['school_year']) ? trim($_POST['school_year']) : '';
  $username = trim($_POST["username"]);
  $password = trim($_POST["password"]);

  // Server-side check for school year selection
  if (empty($selected_year)) {
    $error = "Please select a school year.";
  }

    // Only attempt login if no error so far
    if (empty($error)) {
        // Query users table for login (admin or teacher)
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
          $stmt->bind_result($user_id, $db_password, $role);
          $stmt->fetch();

          if ($password === $db_password) { // plain-text check
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $username;
            $_SESSION["role"] = $role ? $role : 'teacher';
            $_SESSION['school_year'] = $selected_year;
            header("Location: /attendance-monitoring/pages/dashboard.php");
            exit;
          } else {
            $error = "Invalid password!";
          }
        } else {
          $error = "No user found with that username!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WMSU ILS Attendance Tracking</title>
  <link rel="icon" type="image/png" href="../wmsulogo_circular.png">
  <style>
    * {
      box-sizing: border-box;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      background: url('../images/shs.jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-box {
      background-color: white;
      padding: 30px 40px;
      border-radius: 8px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      width: 320px;
      text-align: center;
    }

    /* Logo wrapper: make circular and slightly larger for visibility */
    .logo-wrap { width: 96px; height: 96px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden; display:block; border:4px solid #fff; box-shadow:0 8px 22px rgba(0,0,0,0.22), 0 2px 6px rgba(0,0,0,0.12); background:#fff }
    .logo-wrap img { width:100%; height:100%; object-fit:cover; display:block; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.18)); }

    .login-box h2 {
      font-size: 18px;
      margin-bottom: 15px;
      font-weight: bold;
      color: #000;
    }

    .login-box label {
      display: block;
      text-align: left;
      margin: 10px 0 5px;
      font-size: 14px;
    }

    .password-field { position: relative; display: flex; align-items: center; height: 42px; }
    .password-field input { width: 100%; padding: 10px; padding-right: 46px; height: 100%; box-sizing: border-box; border-radius: 4px; border: 1px solid #ccc; font-size: 14px; margin-bottom: 10px; }
    .toggle-password {
      position: absolute;
      right: 8px;
      background: transparent;
      border: none;
      color: #b30000;
      cursor: pointer;
      padding: 6px;
      display: none;
      align-items: center;
      justify-content: center;
      width: auto;
      height: auto;
      min-width: 0;
      opacity: 0;
      transition: opacity 0.2s ease;
    }
    .toggle-password.visible {
      display: inline-flex;
      opacity: 1;
    }
    .toggle-password.visible {
      display: inline-flex;
      opacity: 1;
    }
    .toggle-password svg { width: 18px; height: 18px; }
    .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 4px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    .login-box select {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 4px;
      border: 1px solid #ccc;
      font-size: 14px;
      background: #fff;
      color: #000;
      cursor: pointer;
    }

    .error-message {
      background: #ffe6e6;
      color: #900;
      padding: 8px 10px;
      border-radius: 4px;
      margin-bottom: 10px;
      text-align: left;
      font-size: 13px;
    }

    .forgot-password {
      text-align: right;
      font-size: 13px;
      margin-bottom: 15px;
    }

    .forgot-password a {
      color: #d00;
      text-decoration: none;
    }

    .login-box button[type="submit"] {
      background-color: #d00;
      color: white;
      padding: 10px;
      width: 100%;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
      margin-bottom: 15px;
    }

    .login-box .signup {
      font-size: 13px;
    }

    .login-box .signup a {
      color: #d00;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo-wrap"><img src="../images/logo.jpg" alt="logo"></div>
    <h2>WMSU ILS Attendance Tracking</h2>
     <form method="POST" action="">
      <?php if (!empty($error)) echo "<div class='error-message'>$error</div>"; ?>

      <label for="school_year">School Year:</label>
      <select id="school_year" name="school_year" required>
        <option value="" <?php if($selected_year=="") echo 'selected'; ?> disabled>Select Year</option>
        <option value="2023-2024" <?php if($selected_year=="2023-2024") echo 'selected'; ?>>2023-2024</option>
        <option value="2024-2025" <?php if($selected_year=="2024-2025") echo 'selected'; ?>>2024-2025</option>
        <option value="2025-2026" <?php if($selected_year=="2025-2026") echo 'selected'; ?>>2025-2026</option>
      </select>

      <label for="username">Username:</label>
      <input type="text" id="username" name="username" required>

      <label for="password">Password:</label>
      <div class="password-field">
        <input type="password" id="password" name="password" required>
        <button type="button" class="toggle-password" aria-label="Show password" data-target="password">
          <span class="visually-hidden">Show password</span>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </button>
      </div>

      <div class="forgot-password">
        <a href="#">Forgot Password</a>
      </div>

      <button type="submit">Log in</button>
    </form>
    <div class="signup">
      Doesn't have an account? <a href="signup.php">Sign up here</a>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const eye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
      const eyeOff = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.58 10.59A3 3 0 0 0 12 15a3 3 0 0 0 2.42-1.24M9.88 4.14A10.77 10.77 0 0 1 12 4c7 0 11 7 11 7a17.67 17.67 0 0 1-2.23 3.11m-4.4 2.76A10.51 10.51 0 0 1 12 20c-7 0-11-7-11-7a17.5 17.5 0 0 1 3.44-3.85" fill="none" stroke="currentColor" stroke-width="2"/></svg>';

      document.querySelectorAll('.toggle-password').forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        
        if (!input) return;

        // Check visibility on input change
        const updateVisibility = () => {
          if (input.value.length > 0) {
            btn.classList.add('visible');
          } else {
            btn.classList.remove('visible');
          }
        };

        // Listen to input events
        input.addEventListener('input', updateVisibility);
        input.addEventListener('change', updateVisibility);

        // Handle click to toggle visibility
        btn.addEventListener('click', () => {
          const isHidden = input.type === 'password';
          input.type = isHidden ? 'text' : 'password';
          btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
          btn.innerHTML = '<span class="visually-hidden">' + (isHidden ? 'Hide password' : 'Show password') + '</span>' + (isHidden ? eyeOff : eye);
        });
      });
    });
  </script>
</body>
</html>
