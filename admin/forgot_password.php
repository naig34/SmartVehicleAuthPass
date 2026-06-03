<?php
/**
 * admin/forgot_password.php
 * Lets the admin verify their identity and reset their own password.
 * Step 1 – Enter username  →  Step 2 – Enter current name to verify  →  Step 3 – New password
 */
session_start();
require_once '../config/db.php';

$step    = $_SESSION['admin_fp_step'] ?? 1;
$error   = '';
$success = '';

/* ── Step 1: verify username ──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == '1') {
    $username = trim($_POST['username'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin) {
        $_SESSION['admin_fp_step'] = 2;
        $_SESSION['admin_fp_id']   = $admin['id'];
        $_SESSION['admin_fp_name'] = $admin['name'];
        $step = 2;
    } else {
        $error = 'No admin account found with that username.';
    }
}

/* ── Step 2: verify full name ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == '2') {
    $enteredName = trim($_POST['full_name'] ?? '');
    $storedName  = $_SESSION['admin_fp_name'] ?? '';
    if (strcasecmp($enteredName, $storedName) === 0) {
        $_SESSION['admin_fp_step'] = 3;
        $step = 3;
    } else {
        $error = 'Full name does not match our records. Please try again.';
        $step  = 2;
    }
}

/* ── Step 3: set new password ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == '3') {
    $newPw  = $_POST['new_password']     ?? '';
    $confPw = $_POST['confirm_password'] ?? '';
    if (strlen($newPw) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step  = 3;
    } elseif ($newPw !== $confPw) {
        $error = 'Passwords do not match.';
        $step  = 3;
    } else {
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION['admin_fp_id']]);
        // Clear session keys
        unset($_SESSION['admin_fp_step'], $_SESSION['admin_fp_id'], $_SESSION['admin_fp_name']);
        $success = 'Password reset successfully! You can now log in.';
        $step    = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .step-indicator { display:flex; gap:8px; justify-content:center; margin-bottom:24px; }
        .step-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem; font-weight:700; border:2px solid #e5e7eb; background:#f9fafb; color:#9ca3af; transition:all .3s; }
        .step-dot.active  { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
        .step-dot.done    { background:#10b981; border-color:#10b981; color:#fff; }
        .hint-text { font-size:0.82rem; color:#6b7280; margin-top:6px; }
    </style>
<script>
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('svaps_dark') === '1') {
            document.body.classList.add('dark-mode');
        }
    });
</script>
</head>
<body>
    <a href="login.php" class="back-home">← Back to Login</a>

    <div class="auth-container admin-theme-auth">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">🔑</div>
                <h1 class="auth-title">Forgot Password</h1>
                <p class="auth-subtitle">Reset your administrator password</p>
            </div>

            <?php if ($step !== 'done'): ?>
            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
                <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2</div>
                <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern">
                    <span>⚠</span><span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-modern alert-success-modern">
                    <span>✓</span><span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- STEP 1: Enter username -->
            <p style="font-size:0.9rem;color:#6b7280;text-align:center;margin-bottom:20px;">Enter your admin username to begin.</p>
            <form method="POST" class="auth-form">
                <input type="hidden" name="step" value="1">
                <div class="form-group-auth">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" class="form-input-modern"
                           placeholder="e.g. begino.abegail@mdci.edu.ph" required autofocus>
                </div>
                <button type="submit" class="btn-auth"><span>Continue →</span></button>
            </form>

            <?php elseif ($step === 2): ?>
            <!-- STEP 2: Verify identity via full name -->
            <p style="font-size:0.9rem;color:#6b7280;text-align:center;margin-bottom:20px;">Enter your registered full name to verify your identity.</p>
            <form method="POST" class="auth-form">
                <input type="hidden" name="step" value="2">
                <div class="form-group-auth">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-input-modern"
                           placeholder="Enter your full name exactly" required autofocus>
                    <p class="hint-text">This must match the name on file for your admin account.</p>
                </div>
                <button type="submit" class="btn-auth"><span>Verify Identity →</span></button>
            </form>

            <?php elseif ($step === 3): ?>
            <!-- STEP 3: Set new password -->
            <p style="font-size:0.9rem;color:#6b7280;text-align:center;margin-bottom:20px;">Identity verified! Create your new password.</p>
            <form method="POST" class="auth-form">
                <input type="hidden" name="step" value="3">
                <div class="form-group-auth">
                    <label for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" class="form-input-modern"
                               placeholder="At least 6 characters" required minlength="6" autofocus>
                        <button type="button" class="toggle-pw" tabindex="-1" title="Show/hide password">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group-auth">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input-modern"
                               placeholder="Repeat new password" required minlength="6">
                        <button type="button" class="toggle-pw" tabindex="-1" title="Show/hide password">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-auth"><span>Reset Password</span></button>
            </form>

            <?php else: // done ?>
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:3rem;margin-bottom:12px;">✅</div>
                <p style="color:#374151;font-weight:600;margin-bottom:20px;">You can now sign in with your new password.</p>
                <a href="login.php" class="btn-auth" style="display:inline-block;text-decoration:none;">Go to Login</a>
            </div>
            <?php endif; ?>

        </div>
    </div>
<script>
// Password toggle buttons
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = this.previousElementSibling;
        input.type = input.type === 'password' ? 'text' : 'password';
    });
});
</script>
</body>
</html>
