<?php
session_start();
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

$stmt = $pdo->query("SELECT COUNT(*) FROM admin");
$adminExists = $stmt->fetchColumn() > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
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

    <div class="auth-container admin-theme-auth">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="auth-title">Administrator</h1>
                <p class="auth-subtitle">Sign in to manage the system</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern">
                    <span>⚠</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$adminExists): ?>
                <div class="alert-modern alert-info-modern">
                    <span>i</span>
                    <span>No admin account exists. <a href="signup.php" style="text-decoration: underline; font-weight: 700;">Create one now</a></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group-auth">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input-modern"
                        placeholder="Enter your username"
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

                <button type="submit" class="btn-auth">
                    <span>Sign In</span>
                </button>
            </form>

            <div style="text-align:center;margin-top:16px;">
                <a href="forgot_password.php" style="font-size:0.875rem;color:#3b82f6;text-decoration:none;font-weight:500;">Forgot password?</a>
            </div>

            <?php if (!$adminExists): ?>
                <div class="auth-divider">
                    <span>OR</span>
                </div>

                <div class="auth-links">
                    <a href="signup.php">Create Admin Account →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
