<?php
session_start();
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $employeeId = $_POST['employee_id'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO guards (name, employee_id, sex, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $employeeId, $sex, $password]);
        $success = 'Account created successfully!';
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error = 'This Employee ID is already registered';
        } else {
            $error = 'Error creating account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Signup</title>
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
    <a href="login.php" class="back-home">← Back to Login</a>

    <div class="auth-container guard-theme-auth">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">✍️</div>
                <h1 class="auth-title">Guard Registration</h1>
                <p class="auth-subtitle">Create your security guard account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-modern alert-success-modern">
                    <span>✓</span>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group-auth">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-input-modern"
                        placeholder="Enter your full name"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group-auth">
                    <label for="employee_id">Employee ID</label>
                    <input
                        type="text"
                        id="employee_id"
                        name="employee_id"
                        class="form-input-modern"
                        placeholder="Enter your employee ID"
                        required
                    >
                </div>

                <div class="form-group-auth">
                    <label for="sex">Gender</label>
                    <select
                        id="sex"
                        name="sex"
                        class="form-input-modern"
                        required
                    >
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group-auth">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input-modern"
                        placeholder="Create a strong password"
                        required
                        minlength="6"
                    >
                </div>

                <button type="submit" class="btn-auth">
                    <span>Create Account</span>
                </button>
            </form>

            <div class="auth-divider">
                <span>OR</span>
            </div>

            <div class="auth-links">
                <a href="login.php">← Already have an account? Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
