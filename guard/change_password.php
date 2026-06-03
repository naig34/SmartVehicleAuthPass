<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

$guard_id = $_SESSION['guard_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM guards WHERE id = ?");
        $stmt->execute([$guard_id]);
        $guard = $stmt->fetch();

        if ($guard && password_verify($current_password, $guard['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE guards SET password = ? WHERE id = ?");

            if ($stmt->execute([$hashed_password, $guard_id])) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Guard</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        body {
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .change-password-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            color: #1f2937;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .header p {
            color: #6b7280;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #475569;
            box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(71, 85, 105, 0.4);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #475569;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        /* Dark mode overrides */
        html.dark-mode body,
        body.dark-mode {
            background: linear-gradient(135deg, #020617 0%, #0f172a 100%) !important;
        }
        html.dark-mode .change-password-container,
        body.dark-mode .change-password-container {
            background: #1e293b !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6) !important;
        }
        html.dark-mode .change-password-container .header h2,
        body.dark-mode .change-password-container .header h2 { color: #f1f5f9 !important; }
        html.dark-mode .change-password-container .header p,
        body.dark-mode .change-password-container .header p { color: #94a3b8 !important; }
        html.dark-mode .change-password-container .form-group label,
        body.dark-mode .change-password-container .form-group label { color: #cbd5e1 !important; }
        html.dark-mode .change-password-container .form-group input,
        body.dark-mode .change-password-container .form-group input {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }
        html.dark-mode .change-password-container .form-group input:focus,
        body.dark-mode .change-password-container .form-group input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2) !important;
        }
        html.dark-mode .change-password-container .alert-success,
        body.dark-mode .change-password-container .alert-success {
            background: #064e3b !important; color: #a7f3d0 !important; border-color: #10b981 !important;
        }
        html.dark-mode .change-password-container .alert-error,
        body.dark-mode .change-password-container .alert-error {
            background: #450a0a !important; color: #fca5a5 !important; border-color: #ef4444 !important;
        }
        html.dark-mode .change-password-container .back-link,
        body.dark-mode .change-password-container .back-link { color: #94a3b8 !important; }
        html.dark-mode .change-password-container .back-link:hover,
        body.dark-mode .change-password-container .back-link:hover { color: #3b82f6 !important; }
    
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 48px !important;
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: #4b5563; }
        .toggle-pw svg { width: 20px; height: 20px; stroke-width: 2; }

        .form-group { position: relative; }
        .password-wrapper input { box-sizing: border-box; }
</style>
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
    <div class="change-password-container">
        <div class="header">
            <h2>Change Password</h2>
            <p>Update your account password</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-wrapper"><input type="password" id="current_password" name="current_password" required><button type="button" class="toggle-pw" tabindex="-1" title="Show/hide password"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-wrapper"><input type="password" id="new_password" name="new_password" required minlength="6"><button type="button" class="toggle-pw" tabindex="-1" title="Show/hide password"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-wrapper"><input type="password" id="confirm_password" name="confirm_password" required minlength="6"><button type="button" class="toggle-pw" tabindex="-1" title="Show/hide password"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div>
            </div>

            <button type="submit" class="btn-submit">Change Password</button>
        </form>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
