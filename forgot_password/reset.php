<?php
/**
 * forgot_password/reset.php
 * Step 2 – User arrives from the emailed link, sets a new password.
 */
session_start();
require_once '../config/db.php';

// Auto-create password_resets table if missing
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_resets` (
            `id`         INT(11)      NOT NULL AUTO_INCREMENT,
            `user_type`  ENUM('student','teacher') NOT NULL,
            `user_id`    INT(11)      NOT NULL,
            `token`      VARCHAR(64)  NOT NULL,
            `expires_at` DATETIME     NOT NULL,
            `used`       TINYINT(1)   NOT NULL DEFAULT 0,
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `idx_user` (`user_type`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (PDOException $e) {
    error_log('ForgotPassword setup error: ' . $e->getMessage());
}

$token   = trim($_GET['token'] ?? '');
$step    = 'invalid';  // 'invalid' | 'form' | 'success'
$error   = '';
$resetRow = null;
$userType = '';

// ── Validate token ─────────────────────────────────────────────────────────
if ($token !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM password_resets
         WHERE token = ? AND used = 0 AND expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([$token]);
    $resetRow = $stmt->fetch();

    if ($resetRow) {
        $step     = 'form';
        $userType = $resetRow['user_type'];
    }
}

// ── Handle new password submission ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'form') {
    $newPass     = $_POST['new_password']     ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
        $error = 'Password must contain at least one letter and one number.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        if ($userType === 'student') {
            $pdo->prepare("UPDATE students SET password = ? WHERE id = ?")
                ->execute([$hashed, $resetRow['user_id']]);
        } else {
            $pdo->prepare("UPDATE teachers_staff SET password = ? WHERE id = ?")
                ->execute([$hashed, $resetRow['user_id']]);
        }

        // Mark token as used
        $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")
            ->execute([$resetRow['id']]);

        // Also invalidate any other unused tokens for this user
        $pdo->prepare(
            "UPDATE password_resets SET used = 1
             WHERE user_type = ? AND user_id = ? AND used = 0"
        )->execute([$userType, $resetRow['user_id']]);

        $step = 'success';
    }
}

$isTeacher   = ($userType === 'teacher');
$themeClass  = $isTeacher ? 'teacher-theme-auth' : 'student-theme-auth';
$icon        = $isTeacher ? '👨‍🏫' : '🎓';
$loginHref   = $isTeacher ? '../teacher/login.php' : '../student/login.php';
$portalLabel = $isTeacher ? 'Teacher / Staff' : 'Student';

// Default theme for invalid state
if ($step === 'invalid') {
    $themeClass  = 'student-theme-auth';
    $loginHref   = '../index.php';
    $icon        = '🔑';
    $portalLabel = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
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
    <style>
        .pw-strength {
            height: 4px;
            border-radius: 4px;
            background: #e5e7eb;
            margin-top: 6px;
            overflow: hidden;
            transition: all .3s;
        }
        .pw-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: width .4s, background .4s;
        }
        .pw-strength-label {
            font-size: 11.5px;
            margin-top: 4px;
            font-weight: 600;
        }
        .pw-req {
            font-size: 12px;
            color: #6b7280;
            margin: 8px 0 0;
            list-style: none;
            padding: 0;
        }
        .pw-req li { margin-bottom: 3px; }
        .pw-req li.ok  { color: #059669; }
        .pw-req li.ok::before  { content: '✔ '; }
        .pw-req li:not(.ok)::before { content: '○ '; }
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 8px 24px rgba(var(--theme-color-rgb), 0.35);
        }
        .sent-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
            text-align: center;
            margin-bottom: 10px;
        }
        .sent-sub {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.65;
            margin-bottom: 28px;
        }
        .countdown-text {
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
            margin-top: 14px;
        }
        #countdown-num {
            font-weight: 700;
            color: var(--theme-color);
        }
    </style>
</head>
<body>

<div class="auth-container <?= $themeClass ?>">
    <div class="auth-card">

        <?php if ($step === 'invalid'): ?>
        <!-- ── INVALID / EXPIRED TOKEN ─────────────────────────────── -->
        <div class="auth-header">
            <div class="auth-icon" style="font-size:2rem;">⏰</div>
            <h1 class="auth-title">Link Expired</h1>
            <p class="auth-subtitle">This reset link is invalid or has expired.</p>
        </div>
        <div class="alert-modern alert-danger-modern" style="margin-bottom:24px;">
            <span>⚠️</span>
            <span>
                Password reset links are only valid for <strong>30 minutes</strong>.
                Please request a new one.
            </span>
        </div>
        <a href="../student/login.php" class="btn-auth" style="display:block;text-align:center;text-decoration:none;margin-bottom:12px;">
            Student Login
        </a>
        <a href="../teacher/login.php" class="btn-auth"
           style="display:block;text-align:center;text-decoration:none;background:linear-gradient(135deg,#059669,#10b981);">
            Teacher / Staff Login
        </a>

        <?php elseif ($step === 'success'): ?>
        <!-- ── SUCCESS ─────────────────────────────────────────────── -->
        <div class="success-icon">✅</div>
        <p class="sent-title">Password Updated!</p>
        <p class="sent-sub">
            Your password has been successfully reset.<br>
            You can now sign in with your new password.
        </p>
        <a href="<?= $loginHref ?>" class="btn-auth" style="display:block;text-align:center;text-decoration:none;" id="loginBtn">
            Go to Login
        </a>
        <p class="countdown-text">
            Redirecting in <span id="countdown-num">5</span>s…
        </p>
        <script>
            let n = 5;
            const el = document.getElementById('countdown-num');
            const iv = setInterval(function() {
                n--;
                el.textContent = n;
                if (n <= 0) { clearInterval(iv); location.href = '<?= $loginHref ?>'; }
            }, 1000);
        </script>

        <?php else: ?>
        <!-- ── RESET FORM ──────────────────────────────────────────── -->
        <div class="auth-header">
            <div class="auth-icon"><?= $icon ?></div>
            <h1 class="auth-title">New Password</h1>
            <p class="auth-subtitle"><?= $portalLabel ?> – Create a new password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-modern alert-danger-modern">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="resetForm">
            <input type="hidden" name="token_field" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group-auth">
                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-input-modern"
                        placeholder="Enter new password"
                        required
                        minlength="8"
                        autofocus
                        oninput="checkStrength(this.value)"
                    >
                    <button type="button" class="toggle-pw" tabindex="-1"
                            onclick="togglePw('new_password', this)"
                            title="Show/hide password">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                     -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
                <p class="pw-strength-label" id="pwLabel" style="color:#9ca3af;"></p>
                <ul class="pw-req" id="pwReqs">
                    <li id="req-len">At least 8 characters</li>
                    <li id="req-letter">At least one letter</li>
                    <li id="req-num">At least one number</li>
                </ul>
            </div>

            <div class="form-group-auth">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-input-modern"
                        placeholder="Re-enter new password"
                        required
                        oninput="checkMatch()"
                    >
                    <button type="button" class="toggle-pw" tabindex="-1"
                            onclick="togglePw('confirm_password', this)"
                            title="Show/hide password">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                     -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p id="matchMsg" style="font-size:12px;margin-top:4px;font-weight:600;"></p>
            </div>

            <button type="submit" class="btn-auth">
                <span>Reset Password</span>
            </button>
        </form>

        <script>
        function togglePw(id, btn) {
            const inp = document.getElementById(id);
            inp.type = inp.type === 'password' ? 'text' : 'password';
        }

        function checkStrength(val) {
            const bar   = document.getElementById('pwBar');
            const label = document.getElementById('pwLabel');
            const reqLen    = document.getElementById('req-len');
            const reqLetter = document.getElementById('req-letter');
            const reqNum    = document.getElementById('req-num');

            const hasLen    = val.length >= 8;
            const hasLetter = /[A-Za-z]/.test(val);
            const hasNum    = /[0-9]/.test(val);
            const hasSpec   = /[^A-Za-z0-9]/.test(val);

            reqLen.className    = hasLen    ? 'ok' : '';
            reqLetter.className = hasLetter ? 'ok' : '';
            reqNum.className    = hasNum    ? 'ok' : '';

            const score = [hasLen, hasLetter, hasNum, hasSpec, val.length >= 12].filter(Boolean).length;

            const levels = [
                { pct: '20%', color: '#ef4444', text: 'Very Weak'  },
                { pct: '40%', color: '#f97316', text: 'Weak'       },
                { pct: '60%', color: '#eab308', text: 'Fair'       },
                { pct: '80%', color: '#22c55e', text: 'Good'       },
                { pct: '100%',color: '#059669', text: 'Strong'     },
            ];
            const lv = levels[Math.max(0, score - 1)] || levels[0];
            bar.style.width      = val.length ? lv.pct   : '0%';
            bar.style.background = lv.color;
            label.textContent    = val.length ? lv.text  : '';
            label.style.color    = lv.color;
        }

        function checkMatch() {
            const pw  = document.getElementById('new_password').value;
            const cf  = document.getElementById('confirm_password').value;
            const msg = document.getElementById('matchMsg');
            if (!cf) { msg.textContent = ''; return; }
            if (pw === cf) {
                msg.textContent = '✔ Passwords match';
                msg.style.color = '#059669';
            } else {
                msg.textContent = '✖ Passwords do not match';
                msg.style.color = '#ef4444';
            }
        }
        </script>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
