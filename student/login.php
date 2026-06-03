<?php
session_start();
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $student = $stmt->fetch();

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['name'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
<script>
    // Apply dark mode before paint to prevent flash
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('svaps_dark') === '1') {
            document.body.classList.add('dark-mode');
        }
    });
    // Listen for dark mode changes from OTHER pages/tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'svaps_dark') {
            var on = e.newValue === '1';
            document.documentElement.classList.toggle('dark-mode', on);
            document.body && document.body.classList.toggle('dark-mode', on);
            var icon = document.getElementById('darkModeIcon');
            if (icon) {
                icon.innerHTML = on
                    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2.34M18.36 5.64l-.71.71M6.34 17.66l-.71.71M18.36 18.36l-.71-.71M6.34 6.34l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />'
                    : '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />';
            }
        }
    });
</script>
</head>
<body>
    <a href="../index.php" class="back-home">← Back to Home</a>

    <div class="auth-container student-theme-auth">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">🎓</div>
                <h1 class="auth-title">Student Portal</h1>
                <p class="auth-subtitle">Sign in to access your profile</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group-auth">
                    <label for="email">School Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input-modern"
                        placeholder="Enter your school email"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group-auth">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input-modern"
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="toggle-pw" tabindex="-1" title="Show/hide password"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                    </div>
                </div>

                <div style="text-align:right;margin-top:-10px;margin-bottom:18px;">
                    <a href="../forgot_password/request.php?type=student"
                       style="font-size:13px;color:var(--theme-color);font-weight:600;text-decoration:none;">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn-auth">
                    <span>Sign In</span>
                </button>
            </form>

            <div class="auth-divider">
                <span>OR</span>
            </div>

        </div>
    </div>
</body>
</html>