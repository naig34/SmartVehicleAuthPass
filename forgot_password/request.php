<?php
/**
 * forgot_password/request.php
 * Step 1 – Enter email/ID  →  Step 2 – Enter 6-digit code  →  Step 3 – New password
 * Works like Google's "Verify it's you" flow.
 */
session_start();
require_once '../config/db.php';

// Sync PHP timezone with MySQL so expiry checks are consistent
try {
    $tzRow = $pdo->query("SELECT @@global.time_zone as tz, NOW() as now_db")->fetch();
    // Force both PHP and MySQL to use UTC for all operations
    $pdo->exec("SET time_zone = '+00:00'");
    date_default_timezone_set('UTC');
} catch (Exception $e) { /* ignore */ }
require_once '../config/mailer.php';

/* ── Auto-create tables ───────────────────────────────────────────────────── */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_resets` (
            `id`         INT(11)      NOT NULL AUTO_INCREMENT,
            `user_type`  ENUM('student','teacher') NOT NULL,
            `user_id`    INT(11)      NOT NULL,
            `token`      VARCHAR(64)  NOT NULL,
            `code`       CHAR(6)      NOT NULL DEFAULT '',
            `expires_at` DATETIME     NOT NULL,
            `used`       TINYINT(1)   NOT NULL DEFAULT 0,
            `attempts`   TINYINT(1)   NOT NULL DEFAULT 0,
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `idx_user` (`user_type`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    // Add code & attempts columns if upgrading from old schema
    foreach (['code CHAR(6) NOT NULL DEFAULT "" AFTER token',
              'attempts TINYINT(1) NOT NULL DEFAULT 0 AFTER used'] as $col) {
        $colName = explode(' ', $col)[0];
        $existing = $pdo->query("SHOW COLUMNS FROM `password_resets` LIKE '$colName'")->fetchAll();
        if (empty($existing)) $pdo->exec("ALTER TABLE `password_resets` ADD COLUMN $col");
    }
    // Add email to teachers_staff if missing
    $cols = $pdo->query("SHOW COLUMNS FROM `teachers_staff` LIKE 'email'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE `teachers_staff` ADD COLUMN `email` VARCHAR(100) DEFAULT NULL AFTER `employee_id`");
        $pdo->exec("ALTER TABLE `teachers_staff` ADD UNIQUE KEY `email` (`email`)");
    }
} catch (PDOException $e) { error_log('ForgotPassword setup: ' . $e->getMessage()); }

/* ── Helpers ──────────────────────────────────────────────────────────────── */
$type      = $_GET['type'] ?? ($_SESSION['fp_type'] ?? 'student');
if (!in_array($type, ['student','teacher'])) $type = 'student';
$_SESSION['fp_type'] = $type;

$isTeacher  = ($type === 'teacher');
$themeClass = $isTeacher ? 'teacher-theme-auth' : 'student-theme-auth';
$icon       = $isTeacher ? '👨‍🏫' : '🎓';
$portal     = $isTeacher ? 'Teacher / Staff' : 'Student';
$loginHref  = $isTeacher ? '../teacher/login.php' : '../student/login.php';

$step  = $_SESSION['fp_step']  ?? 'email';   // email | code | done
$error = '';
$info  = '';

/* ── POST handler ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── ACTION: send code ───────────────────────────────────────────────── */
    if ($action === 'send_code') {
        $email      = trim($_POST['email']      ?? '');
        $identifier = trim($_POST['identifier'] ?? '');

        if (empty($email)) { $error = 'Please enter your email address.'; }
        elseif ($isTeacher && empty($identifier)) { $error = 'Please enter your Employee ID.'; }
        else {
            $user = null;
            if ($type === 'student') {
                $stmt = $pdo->prepare("SELECT id, name, email FROM students WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
            } else {
                $stmt = $pdo->prepare("SELECT id, name, email FROM teachers_staff WHERE employee_id = ? AND email = ?");
                $stmt->execute([$identifier, $email]);
                $user = $stmt->fetch();
            }

            // Always show code screen (don't reveal if account exists)
            $_SESSION['fp_email'] = $email;

            if ($user) {
                // Invalidate old tokens
                $pdo->prepare("UPDATE password_resets SET used=1 WHERE user_type=? AND user_id=? AND used=0 AND expires_at > NOW()")
                    ->execute([$type, $user['id']]);

                // Generate 6-digit code + session token
                $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $token   = bin2hex(random_bytes(32));
                // Let MySQL set the expiry so there's no PHP/MySQL timezone mismatch
                $pdo->prepare("INSERT INTO password_resets (user_type,user_id,token,code,expires_at) VALUES (?,?,?,?, NOW() + INTERVAL 10 MINUTE)")
                    ->execute([$type, $user['id'], $token, $code]);

                $_SESSION['fp_token']   = $token;
                $_SESSION['fp_user_id'] = $user['id'];

                $sent = sendCodeEmail($user['email'], $user['name'], $code, $type);
                if (!$sent) { $error = 'EMAIL_SEND_FAILED'; }
            }

            if (empty($error)) { $_SESSION['fp_step'] = 'code'; $step = 'code'; }
        }
    }

    /* ── ACTION: verify code ─────────────────────────────────────────────── */
    elseif ($action === 'verify_code') {
        $entered = trim(implode('', $_POST['digit'] ?? []));
        $token   = $_SESSION['fp_token'] ?? '';

        if (strlen($entered) !== 6) { $error = 'Please enter all 6 digits.'; $step = 'code'; }
        elseif (!$token) { $error = 'Session expired. Please start over.'; $step = 'email'; unset($_SESSION['fp_step']); }
        else {
            $stmt = $pdo->prepare(
                "SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > (NOW() - INTERVAL 30 SECOND) LIMIT 1"
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = 'Your code has expired. Please request a new one.';
                $step  = 'code';
                unset($_SESSION['fp_token'], $_SESSION['fp_step']);
                $_SESSION['fp_step'] = 'code';   // stay on code screen w/ resend
            } elseif ($row['attempts'] >= 5) {
                $pdo->prepare("UPDATE password_resets SET used=1 WHERE id=?")->execute([$row['id']]);
                $error = 'Too many wrong attempts. Please request a new code.';
                unset($_SESSION['fp_token'], $_SESSION['fp_step']);
                $step = 'email';
            } elseif ($row['code'] !== $entered) {
                $pdo->prepare("UPDATE password_resets SET attempts=attempts+1 WHERE id=?")->execute([$row['id']]);
                $remaining = 4 - (int)$row['attempts'];
                $error = "Wrong code. $remaining attempt(s) left.";
                $step  = 'code';
            } else {
                // ✅ Correct code — move to reset step
                $_SESSION['fp_verified'] = true;
                $_SESSION['fp_step']     = 'reset';
                $step = 'reset';
            }
        }
    }

    /* ── ACTION: resend code ─────────────────────────────────────────────── */
    elseif ($action === 'resend_code') {
        unset($_SESSION['fp_token'], $_SESSION['fp_step'], $_SESSION['fp_verified']);
        $step = 'email';
        $_SESSION['fp_step'] = 'email';
        $info = 'Enter your email again to get a new code.';
    }

    /* ── ACTION: set new password ────────────────────────────────────────── */
    elseif ($action === 'set_password') {
        if (empty($_SESSION['fp_verified']) || empty($_SESSION['fp_token'])) {
            $step = 'email'; unset($_SESSION['fp_step']);
        } else {
            $newPass  = $_POST['new_password']     ?? '';
            $confPass = $_POST['confirm_password'] ?? '';

            if (strlen($newPass) < 8) {
                $error = 'Password must be at least 8 characters.'; $step = 'reset';
            } elseif ($newPass !== $confPass) {
                $error = 'Passwords do not match.'; $step = 'reset';
            } elseif (!preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
                $error = 'Password must include at least one letter and one number.'; $step = 'reset';
            } else {
                $token = $_SESSION['fp_token'];
                $stmt  = $pdo->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 LIMIT 1");
                $stmt->execute([$token]);
                $row = $stmt->fetch();

                if (!$row) { $error = 'Session expired. Please start over.'; $step = 'email'; }
                else {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $table  = ($row['user_type'] === 'student') ? 'students' : 'teachers_staff';
                    $pdo->prepare("UPDATE `$table` SET password=? WHERE id=?")->execute([$hashed, $row['user_id']]);
                    $pdo->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);
                    unset($_SESSION['fp_step'], $_SESSION['fp_token'], $_SESSION['fp_verified'],
                          $_SESSION['fp_user_id'], $_SESSION['fp_email'], $_SESSION['fp_type']);
                    $step = 'done';
                }
            }
        }
    }
} else {
    $step = $_SESSION['fp_step'] ?? 'email';
}

// Mask email for display  e.g. v*****s@mdci.edu.ph
function maskEmail(string $email): string {
    [$local, $domain] = explode('@', $email, 2);
    if (strlen($local) <= 2) return $local[0] . '***@' . $domain;
    return $local[0] . str_repeat('*', max(1, strlen($local)-2)) . $local[-1] . '@' . $domain;
}
$maskedEmail = isset($_SESSION['fp_email']) ? maskEmail($_SESSION['fp_email']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – <?= $portal ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script>if(localStorage.getItem('svaps_dark')==='1'){document.documentElement.classList.add('dark-mode');}</script>
    <style>
        /* ── Code input tiles ── */
        .code-tiles { display:flex; gap:10px; justify-content:center; margin:28px 0 6px; }
        .code-tile {
            width:52px; height:64px; border:2px solid #d1d5db; border-radius:14px;
            font-size:28px; font-weight:800; text-align:center; color:#111827;
            background:#f9fafb; outline:none; transition:border-color .2s, box-shadow .2s;
            caret-color: var(--theme-color);
        }
        .code-tile:focus {
            border-color: var(--theme-color);
            box-shadow: 0 0 0 4px rgba(var(--theme-color-rgb),.15);
            background:#fff;
        }
        .code-tile.filled { border-color: var(--theme-color); background:#fff; }
        .code-tile.error  { border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.15); }

        /* ── Misc ── */
        .fp-info { background:rgba(var(--theme-color-rgb),.07); border:1px solid rgba(var(--theme-color-rgb),.18);
                   border-radius:12px; padding:14px 16px; margin-bottom:22px; color:#374151; font-size:13.5px; line-height:1.6; }
        .fp-divider-text { text-align:center; color:#9ca3af; font-size:13px; margin:18px 0 6px; }
        .btn-secondary-auth { display:block; text-align:center; padding:12px;
            border:2px solid rgba(var(--theme-color-rgb),.3); border-radius:12px;
            color:var(--theme-color); font-weight:600; font-size:14px; text-decoration:none; transition:all .2s; }
        .btn-secondary-auth:hover { background:rgba(var(--theme-color-rgb),.06); border-color:var(--theme-color); }
        .btn-link { background:none; border:none; color:var(--theme-color); font-weight:600;
                    font-size:14px; cursor:pointer; padding:0; text-decoration:underline; }
        .email-badge { display:inline-block; background:rgba(var(--theme-color-rgb),.1);
                       border-radius:8px; padding:4px 12px; font-weight:700;
                       color:var(--theme-color); font-size:14px; margin:4px 0 18px; }
        .success-wrap { text-align:center; }
        .success-icon-big { font-size:56px; margin-bottom:14px; animation:popIn .4s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes popIn { from{transform:scale(0)} to{transform:scale(1)} }

        /* ── Password strength ── */
        .pw-strength { height:4px; border-radius:4px; background:#e5e7eb; margin-top:6px; overflow:hidden; }
        .pw-bar { height:100%; width:0%; border-radius:4px; transition:width .4s,background .4s; }
        .pw-req { font-size:12px; color:#6b7280; margin:8px 0 0; list-style:none; padding:0; }
        .pw-req li.ok { color:#059669; } .pw-req li.ok::before { content:'✔ '; }
        .pw-req li:not(.ok)::before { content:'○ '; }
        .pw-label { font-size:11.5px; margin-top:4px; font-weight:600; }

        /* ── Countdown ring ── */
        .timer-wrap { display:flex; flex-direction:column; align-items:center; margin:4px 0 20px; }
        .timer-ring { position:relative; width:56px; height:56px; }
        .timer-ring svg { transform:rotate(-90deg); }
        .timer-ring circle { fill:none; stroke-width:4; }
        .timer-track { stroke:#e5e7eb; }
        .timer-progress { stroke:var(--theme-color); stroke-linecap:round; transition:stroke-dashoffset 1s linear; }
        .timer-num { position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
                     font-size:15px; font-weight:800; color:var(--theme-color); }
        .timer-label { font-size:12px; color:#9ca3af; margin-top:6px; }
    </style>
</head>
<body>
<a href="<?= $loginHref ?>" class="back-home">← Back to Login</a>

<div class="auth-container <?= $themeClass ?>">
<div class="auth-card">

<?php /* ════════════════ STEP: EMAIL ════════════════ */ if ($step === 'email'): ?>

    <div class="auth-header">
        <div class="auth-icon"><?= $icon ?></div>
        <h1 class="auth-title">Forgot Password</h1>
        <p class="auth-subtitle"><?= $portal ?> – Verify it's you</p>
    </div>

    <?php if ($error === 'EMAIL_SEND_FAILED'): ?>
        <div class="alert-modern alert-danger-modern" style="flex-direction:column;align-items:flex-start;gap:8px;">
            <div style="display:flex;gap:8px;align-items:center;"><span>⚠️</span><strong>Email could not be sent</strong></div>
            <p style="margin:0;font-size:13px;line-height:1.6;color:#7f1d1d;">
                The system email is not yet configured. Ask your <strong>system administrator</strong>
                to set up the email in <code>config/mailer.php</code>.
            </p>
        </div>
    <?php elseif ($error): ?>
        <div class="alert-modern alert-danger-modern"><span>⚠️</span><span><?= htmlspecialchars($error) ?></span></div>
    <?php elseif ($info): ?>
        <div class="alert-modern" style="background:rgba(var(--theme-color-rgb),.08);border:1px solid rgba(var(--theme-color-rgb),.2);border-radius:12px;padding:14px 16px;display:flex;gap:10px;align-items:center;margin-bottom:18px;color:#374151;">
            <span>ℹ️</span><span><?= htmlspecialchars($info) ?></span>
        </div>
    <?php endif; ?>

    <div class="fp-info">
        <?php if ($isTeacher): ?>
            Enter your <strong>Employee ID</strong> and <strong>personal email</strong>.
            We'll send a <strong>6-digit verification code</strong> to that address.
        <?php else: ?>
            Enter your <strong>MDCI school email</strong>. We'll send a
            <strong>6-digit verification code</strong> to confirm it's you.
        <?php endif; ?>
    </div>

    <form method="POST" class="auth-form">
        <input type="hidden" name="action" value="send_code">
        <?php if ($isTeacher): ?>
        <div class="form-group-auth">
            <label for="identifier">Employee ID</label>
            <input type="text" id="identifier" name="identifier" class="form-input-modern"
                   placeholder="e.g. EM789" required autofocus
                   value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
        </div>
        <?php endif; ?>
        <div class="form-group-auth">
            <label for="email"><?= $isTeacher ? 'Personal Email Address' : 'School Email Address' ?></label>
            <input type="email" id="email" name="email" class="form-input-modern"
                   placeholder="<?= $isTeacher ? 'your.email@example.com' : 'yourname@mdci.edu.ph' ?>"
                   required <?= $isTeacher ? '' : 'autofocus' ?>
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-auth"><span>Send Verification Code</span></button>
    </form>
    <p class="fp-divider-text">Remember your password?</p>
    <a href="<?= $loginHref ?>" class="btn-secondary-auth">Back to Login</a>


<?php /* ════════════════ STEP: CODE ════════════════ */ elseif ($step === 'code'): ?>

    <div class="auth-header">
        <div class="auth-icon" style="font-size:2rem;">🔐</div>
        <h1 class="auth-title">Enter the Code</h1>
        <p class="auth-subtitle">Check your email for the 6-digit code</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern" id="errBox">
            <span>⚠️</span><span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <p style="text-align:center;color:#6b7280;font-size:13.5px;margin:0 0 4px;">Code sent to</p>
    <p style="text-align:center;margin:0 0 6px;"><span class="email-badge"><?= htmlspecialchars($maskedEmail) ?></span></p>

    <!-- Countdown timer -->
    <div class="timer-wrap">
        <div class="timer-ring">
            <svg width="56" height="56" viewBox="0 0 56 56">
                <circle class="timer-track" cx="28" cy="28" r="24"/>
                <circle class="timer-progress" id="timerCircle" cx="28" cy="28" r="24"
                        stroke-dasharray="150.8" stroke-dashoffset="0"/>
            </svg>
            <div class="timer-num" id="timerNum">10:00</div>
        </div>
        <p class="timer-label">Code expires in</p>
    </div>

    <form method="POST" class="auth-form" id="codeForm">
        <input type="hidden" name="action" value="verify_code">
        <div class="code-tiles" id="codeTiles">
            <?php for($i=0;$i<6;$i++): ?>
            <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1"
                   class="code-tile" name="digit[]" id="d<?= $i ?>"
                   autocomplete="off" <?= $i===0?'autofocus':'' ?>>
            <?php endfor; ?>
        </div>
        <p style="text-align:center;color:#9ca3af;font-size:12px;margin:0 0 20px;">
            Enter the 6-digit code from your email
        </p>
        <button type="submit" class="btn-auth" id="verifyBtn" disabled>
            <span>Verify Code</span>
        </button>
    </form>

    <p class="fp-divider-text">Didn't receive it or code expired?</p>
    <form method="POST" style="margin:0;">
        <input type="hidden" name="action" value="resend_code">
        <button type="submit" class="btn-secondary-auth">Resend Code</button>
    </form>

    <script>
    // ── Tile auto-advance ──────────────────────────────────────────────────
    const tiles   = document.querySelectorAll('.code-tile');
    const verifyBtn = document.getElementById('verifyBtn');
    const errBox  = document.getElementById('errBox');

    function checkFull() {
        const full = [...tiles].every(t => t.value.match(/[0-9]/));
        verifyBtn.disabled = !full;
    }

    tiles.forEach((tile, i) => {
        tile.addEventListener('input', e => {
            tile.value = tile.value.replace(/[^0-9]/g,'').slice(-1);
            if (errBox) { errBox.style.display='none'; tiles.forEach(t=>t.classList.remove('error')); }
            tile.classList.toggle('filled', tile.value !== '');
            if (tile.value && i < 5) tiles[i+1].focus();
            checkFull();
        });
        tile.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !tile.value && i > 0) {
                tiles[i-1].value = '';
                tiles[i-1].classList.remove('filled');
                tiles[i-1].focus();
                checkFull();
            }
            if (e.key === 'ArrowLeft'  && i > 0) tiles[i-1].focus();
            if (e.key === 'ArrowRight' && i < 5) tiles[i+1].focus();
        });
        tile.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'');
            [...paste.slice(0,6)].forEach((ch,j) => {
                if (tiles[i+j]) { tiles[i+j].value=ch; tiles[i+j].classList.add('filled'); }
            });
            const next = Math.min(i+paste.length, 5);
            tiles[next].focus();
            checkFull();
        });
    });

    <?php if ($error && strpos($error,'Wrong') !== false): ?>
    tiles.forEach(t => { t.classList.add('error'); t.value=''; t.classList.remove('filled'); });
    tiles[0].focus();
    <?php endif; ?>

    // ── Countdown timer (10 min) ───────────────────────────────────────────
    const TOTAL = 600;
    const circle = document.getElementById('timerCircle');
    const numEl  = document.getElementById('timerNum');
    const CIRC   = 150.8;
    let remaining = TOTAL;

    function tick() {
        if (remaining <= 0) {
            numEl.textContent = '0:00';
            circle.style.strokeDashoffset = CIRC;
            numEl.style.color = '#ef4444';
            circle.style.stroke = '#ef4444';
            return;
        }
        remaining--;
        const m = String(Math.floor(remaining/60)).padStart(2,'0');
        const s = String(remaining%60).padStart(2,'0');
        numEl.textContent = m+':'+s;
        circle.style.strokeDashoffset = CIRC * (1 - remaining/TOTAL);
        if (remaining <= 60) { numEl.style.color='#ef4444'; circle.style.stroke='#ef4444'; }
        else if (remaining <= 120) { numEl.style.color='#f59e0b'; circle.style.stroke='#f59e0b'; }
        setTimeout(tick, 1000);
    }
    tick();
    </script>


<?php /* ════════════════ STEP: RESET PASSWORD ════════════════ */ elseif ($step === 'reset'): ?>

    <div class="auth-header">
        <div class="auth-icon"><?= $icon ?></div>
        <h1 class="auth-title">New Password</h1>
        <p class="auth-subtitle">Create a strong new password</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern"><span>⚠️</span><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <input type="hidden" name="action" value="set_password">

        <div class="form-group-auth">
            <label for="new_password">New Password</label>
            <div class="password-wrapper">
                <input type="password" id="new_password" name="new_password" class="form-input-modern"
                       placeholder="Enter new password" required minlength="8" autofocus
                       oninput="checkStrength(this.value)">
                <button type="button" class="toggle-pw" tabindex="-1" onclick="togglePw('new_password')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <div class="pw-strength"><div class="pw-bar" id="pwBar"></div></div>
            <p class="pw-label" id="pwLabel" style="color:#9ca3af;"></p>
            <ul class="pw-req">
                <li id="rLen">At least 8 characters</li>
                <li id="rLet">At least one letter</li>
                <li id="rNum">At least one number</li>
            </ul>
        </div>

        <div class="form-group-auth">
            <label for="confirm_password">Confirm New Password</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" class="form-input-modern"
                       placeholder="Re-enter new password" required oninput="checkMatch()">
                <button type="button" class="toggle-pw" tabindex="-1" onclick="togglePw('confirm_password')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <p id="matchMsg" style="font-size:12px;margin-top:4px;font-weight:600;"></p>
        </div>

        <button type="submit" class="btn-auth"><span>Set New Password</span></button>
    </form>

    <script>
    function togglePw(id) { const i=document.getElementById(id); i.type=i.type==='password'?'text':'password'; }
    function checkStrength(v) {
        const bar=document.getElementById('pwBar'), lbl=document.getElementById('pwLabel');
        const l=v.length>=8, a=/[A-Za-z]/.test(v), n=/[0-9]/.test(v), s=/[^A-Za-z0-9]/.test(v), L=v.length>=12;
        document.getElementById('rLen').className=l?'ok':'';
        document.getElementById('rLet').className=a?'ok':'';
        document.getElementById('rNum').className=n?'ok':'';
        const sc=[l,a,n,s,L].filter(Boolean).length;
        const lvl=[{p:'20%',c:'#ef4444',t:'Very Weak'},{p:'40%',c:'#f97316',t:'Weak'},
                   {p:'60%',c:'#eab308',t:'Fair'},{p:'80%',c:'#22c55e',t:'Good'},{p:'100%',c:'#059669',t:'Strong'}];
        const lv=lvl[Math.max(0,sc-1)];
        bar.style.width=v.length?lv.p:'0%'; bar.style.background=lv.c;
        lbl.textContent=v.length?lv.t:''; lbl.style.color=lv.c;
    }
    function checkMatch() {
        const pw=document.getElementById('new_password').value;
        const cf=document.getElementById('confirm_password').value;
        const m=document.getElementById('matchMsg');
        if(!cf){m.textContent='';return;}
        m.textContent=pw===cf?'✔ Passwords match':'✖ Passwords do not match';
        m.style.color=pw===cf?'#059669':'#ef4444';
    }
    </script>


<?php /* ════════════════ STEP: DONE ════════════════ */ elseif ($step === 'done'): ?>

    <div class="success-wrap">
        <div class="success-icon-big">✅</div>
        <h1 class="auth-title" style="margin-bottom:10px;">Password Updated!</h1>
        <p style="color:#6b7280;font-size:14px;line-height:1.65;margin-bottom:28px;">
            Your password has been successfully reset.<br>
            You can now sign in with your new password.
        </p>
        <a href="<?= $loginHref ?>" class="btn-auth" id="loginBtn"
           style="display:block;text-align:center;text-decoration:none;">
            Go to Login
        </a>
        <p style="text-align:center;color:#9ca3af;font-size:13px;margin-top:14px;">
            Redirecting in <span id="cntNum" style="font-weight:700;color:var(--theme-color);">5</span>s…
        </p>
    </div>
    <script>
    let n=5;
    const el=document.getElementById('cntNum');
    const iv=setInterval(()=>{ n--; el.textContent=n; if(n<=0){clearInterval(iv);location.href='<?= $loginHref ?>';} },1000);
    </script>

<?php endif; ?>

</div><!-- .auth-card -->
</div><!-- .auth-container -->
</body>
</html>
