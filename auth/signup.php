<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMSU ILS Attendance Tracking</title>
    <link rel="icon" type="image/png" href="../wmsulogo_circular.png">
    <style>
        /* Background and centering */
        body {
            background: url('../images/shs.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Container: wider on larger screens, responsive on small screens */
        .container {
            background-color: white;
            padding: 28px 32px;
            border-radius: 8px;
            box-shadow: 0 6px 26px rgba(0, 0, 0, 0.12);
            width: min(560px, 96vw);
            max-width: 560px;
            text-align: center;
        }

        /* Heading/logo */
        h3 { margin: 0 0 6px 0; font-size: 18px; color: #222; }
        /* Logo: slightly larger but constrained and responsive; wrap to make perfect circle */
        .logo-wrap { width: 96px; height: 96px; max-width:22%; max-height:110px; margin: 8px auto 12px; border-radius: 50%; overflow: hidden; display:block; border:4px solid #fff; box-shadow:0 8px 22px rgba(0,0,0,0.22), 0 2px 6px rgba(0,0,0,0.12); background: #fff }
        .logo-wrap img { width:100%; height:100%; object-fit:cover; display:block; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.18)); }
        h2 { margin: 4px 0 14px; font-size: 22px; }

        /* Form layout */
        .form-group { margin-bottom: 14px; text-align: left; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; font-size: 13px }
        input { width: 100%; padding: 10px 12px; height:44px; border: 1px solid #e2e2e2; border-radius: 6px; box-sizing: border-box; font-size:14px }

        .password-field { position: relative; display: flex; align-items: center; }
        .password-field input { padding-right: 46px; }
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
            font-size: 12px;
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
        .toggle-password svg { width: 18px; height: 18px; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

        /* Name row: allow wrapping on small screens */
        .name-fields { display: flex; gap: 10px; align-items: stretch; }
        .name-fields .form-group { flex: 1; min-width: 110px }

        /* Primary button */
        button:not(.toggle-password) { background-color: #d00; color: white; border: none; padding: 12px 18px; border-radius: 6px; cursor: pointer; width: 100%; font-size: 16px; margin-top: 8px }
        button:not(.toggle-password):hover { background-color: #c50000 }

        .login-link { margin-top: 14px; color: #666; font-size: 14px }
        .login-link a { color: #d00; text-decoration: none }
        .login-link a:hover { text-decoration: underline }

        /* Slightly smaller layout on very small devices */
        @media (max-width:420px) {
            .container { padding: 18px; }
            .name-fields { flex-direction: column }
            input { height:42px }
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>WMSU ILS Attendance Tracking</h3>
        <div class="logo-wrap"><img src="../images/logo.jpg" alt="logo"></div>

        <h2>Teacher Sign Up</h2>

        <form id="teacherSignupForm">
          <div class="form-group">
            <label for="faculty_id">Faculty ID</label>
            <input type="text" id="faculty_id" name="faculty_id" placeholder="Faculty ID" required>
          </div>

          <div class="name-fields">
            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="first_name" placeholder="Firstname" required>
            </div>
            <div class="form-group">
                <label for="mi">Middle Initial</label>
                <input type="text" id="mi" name="middle_initial" placeholder="M.I.">
            </div>
            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="last_name" placeholder="Lastname" required>
            </div>
        </div>

        <div class="form-group">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" placeholder="Department" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="button" class="toggle-password" aria-label="Show password" data-target="password">
                    <span class="visually-hidden">Show password</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-field">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="button" class="toggle-password" aria-label="Show password" data-target="confirm_password">
                    <span class="visually-hidden">Show password</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </button>
            </div>
        </div>

        <button type="submit">Create Teacher Account</button>
        
        <div class="login-link">
            Already have an account? <a href="signin.php">Login here</a>
        </div>
    </div>
        <script>
            // Password show/hide toggles with eye icon
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

            (function(){
                const form = document.getElementById('teacherSignupForm');
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const faculty_id = document.getElementById('faculty_id').value.trim();
                    const first_name = document.getElementById('firstname').value.trim();
                    const middle_initial = document.getElementById('mi').value.trim();
                    const last_name = document.getElementById('lastname').value.trim();
                    const department = document.getElementById('department').value.trim();
                    const email = document.getElementById('email').value.trim();
                    const password = document.getElementById('password').value;
                    const confirm = document.getElementById('confirm_password').value;

                    if (!faculty_id || !first_name || !last_name || !department || !email || !password) {
                        alert('Please fill in all required fields');
                        return;
                    }
                    if (password !== confirm) {
                        alert('Passwords do not match');
                        return;
                    }

                    // Submit to addteacher.php (UI-only; server should accept these fields)
                    const fd = new FormData();
                    fd.append('faculty_id', faculty_id);
                    fd.append('first_name', first_name);
                    fd.append('middle_initial', middle_initial);
                    fd.append('last_name', last_name);
                    fd.append('department', department);
                    fd.append('email', email);
                    // add a 'password' field if server supports storing password (ensure hashing server-side)
                    fd.append('password', password);
                    // set default status
                    fd.append('status', 'Active');

                    try {
                        const res = await fetch('addteacher.php', { method: 'POST', body: fd });
                        if (!res.ok) {
                            const txt = await res.text();
                            let parsed;
                            try { parsed = JSON.parse(txt); } catch(e) { parsed = null; }
                            const msg = parsed?.error || parsed?.errors?.join(', ') || txt || res.statusText;
                            throw new Error(msg);
                        }
                        const data = await res.json();
                        if (data && data.success) {
                            alert('Teacher account created successfully');
                            // redirect to signin or teachers list
                            window.location.href = 'signin.php';
                        } else {
                            alert('Failed to create account: ' + (data?.error || JSON.stringify(data)));
                        }
                    } catch (err) {
                        console.error('Signup error', err);
                        alert('Signup failed: ' + err.message);
                    }
                });
            })();
        </script>
</body>
</html>