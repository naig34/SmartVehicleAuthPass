<?php
/**
 * admin/signup.php  →  repurposed as "Settings – Reset Guard Password"
 * Admin can search for a guard and set a new password on their behalf.
 */
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../config/db.php';

$adminName = $_SESSION['admin_name'];
$error     = '';
$success   = '';
$guard     = null;

// Fetch all guards for the dropdown
$guards = $pdo->query("SELECT id, name, employee_id FROM guards ORDER BY name ASC")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardId    = (int)($_POST['guard_id'] ?? 0);
    $newPw      = $_POST['new_password']     ?? '';
    $confirmPw  = $_POST['confirm_password'] ?? '';

    if (!$guardId) {
        $error = 'Please select a security guard.';
    } elseif (strlen($newPw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPw !== $confirmPw) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM guards WHERE id = ?");
        $stmt->execute([$guardId]);
        $guard = $stmt->fetch();
        if ($guard) {
            $hashed = password_hash($newPw, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE guards SET password = ? WHERE id = ?")->execute([$hashed, $guardId]);
            $success = 'Password for <strong>' . htmlspecialchars($guard['name']) . '</strong> has been reset successfully!';
        } else {
            $error = 'Guard not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – Reset Guard Password</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .hamburger-btn { display:none;background:none;border:none;cursor:pointer;padding:8px;border-radius:8px; }
        .hamburger-btn svg { width:24px;height:24px;stroke:#374151; }
        .sidebar-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99; }
        .sidebar-overlay.active { display:block; }
        @media (max-width:768px) {
            .hamburger-btn { display:flex;align-items:center; }
            .sidebar { transform:translateX(-100%);transition:transform 0.3s ease; }
            .sidebar.open { transform:translateX(0); }
            .top-nav-left h1 { font-size:1rem; }
            .top-nav-left p { display:none; }
            .user-details { display:none; }
        }
        /* Settings card */
        .settings-card {
            background:#fff;
            border-radius:16px;
            padding:36px 40px;
            max-width:520px;
            margin:0 auto;
            box-shadow:0 4px 24px rgba(0,0,0,0.07);
            border:1px solid #e5e7eb;
        }
        .settings-card h2 {
            font-size:1.3rem;
            font-weight:700;
            color:#111827;
            margin-bottom:6px;
        }
        .settings-card .sub {
            font-size:0.875rem;
            color:#6b7280;
            margin-bottom:28px;
            line-height:1.5;
        }
        .form-label { display:block;font-size:0.875rem;font-weight:600;color:#374151;margin-bottom:6px; }
        .form-select, .form-input {
            width:100%;
            padding:11px 14px;
            border:1.5px solid #d1d5db;
            border-radius:10px;
            font-size:0.95rem;
            color:#111827;
            background:#f9fafb;
            outline:none;
            transition:border-color .2s,box-shadow .2s;
            box-sizing:border-box;
            margin-bottom:18px;
        }
        .form-select:focus, .form-input:focus {
            border-color:#3b82f6;
            box-shadow:0 0 0 3px rgba(59,130,246,.15);
            background:#fff;
        }
        .pw-wrap { position:relative; margin-bottom:18px; }
        .pw-wrap .form-input { margin-bottom:0; }
        .pw-toggle {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none;border:none;cursor:pointer;padding:4px;color:#9ca3af;
        }
        .pw-toggle:hover { color:#374151; }
        .pw-toggle svg { width:18px;height:18px; }
        .btn-reset {
            width:100%;padding:13px;border:none;border-radius:12px;
            background:linear-gradient(135deg,#1d4ed8,#2563eb);
            color:#fff;font-size:1rem;font-weight:700;cursor:pointer;
            transition:opacity .2s,transform .1s;
            margin-top:4px;
        }
        .btn-reset:hover { opacity:.9; transform:translateY(-1px); }
        .alert-success {
            background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;
            border-radius:10px;padding:14px 16px;font-size:0.9rem;margin-bottom:20px;
        }
        .alert-error {
            background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;
            border-radius:10px;padding:14px 16px;font-size:0.9rem;margin-bottom:20px;
        }
        .divider { border:none;border-top:1px solid #e5e7eb;margin:24px 0; }
        .info-box {
            background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;
            padding:14px 16px;font-size:0.85rem;color:#1e40af;line-height:1.5;
        }
        .info-box strong { display:block;margin-bottom:4px; }
        /* Dark mode */
        html.dark-mode .settings-card, body.dark-mode .settings-card {
            background:#1e293b !important; border-color:#334155 !important;
        }
        html.dark-mode .settings-card h2, body.dark-mode .settings-card h2 { color:#f1f5f9 !important; }
        html.dark-mode .settings-card .sub, body.dark-mode .settings-card .sub { color:#94a3b8 !important; }
        html.dark-mode .form-label, body.dark-mode .form-label { color:#cbd5e1 !important; }
        html.dark-mode .form-select, html.dark-mode .form-input,
        body.dark-mode .form-select, body.dark-mode .form-input {
            background:#0f172a !important; border-color:#475569 !important; color:#f1f5f9 !important;
        }
        html.dark-mode .info-box, body.dark-mode .info-box {
            background:#1e3a8a22 !important; border-color:#3b82f6 !important; color:#93c5fd !important;
        }
        html.dark-mode .hamburger-btn svg, body.dark-mode .hamburger-btn svg { stroke:#94a3b8 !important; }
    </style>
<script>
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('svaps_dark') === '1') document.body.classList.add('dark-mode');
        var btn  = document.getElementById('darkModeToggle');
        var icon = document.getElementById('darkModeIcon');
        function setIcon(on) {
            if (!icon) return;
            icon.innerHTML = on
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2.34M18.36 5.64l-.71.71M6.34 17.66l-.71.71M18.36 18.36l-.71-.71M6.34 6.34l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />';
        }
        setIcon(localStorage.getItem('svaps_dark') === '1');
        if (btn) btn.addEventListener('click', function() {
            var on = !document.body.classList.contains('dark-mode');
            localStorage.setItem('svaps_dark', on ? '1' : '0');
            document.documentElement.classList.toggle('dark-mode', on);
            document.body.classList.toggle('dark-mode', on);
            setIcon(on);
        });
    });
    window.addEventListener('storage', function(e) {
        if (e.key === 'svaps_dark') {
            var on = e.newValue === '1';
            document.documentElement.classList.toggle('dark-mode', on);
            document.body && document.body.classList.toggle('dark-mode', on);
        }
    });
</script>
</head>
<body class="admin-theme-dash">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h2>SVAPS Admin</h2>
                <p>Vehicle Management</p>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <a href="register_vehicle.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Register Vehicle
                </a>
                <a href="manage_guards.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Manage Guards
                </a>
                <a href="test_qr.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Test QR Codes
                </a>
                <a href="signup.php" class="menu-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-nav">
                <div class="top-nav-left" style="display:flex;align-items:center;gap:12px;">
                    <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h1>Settings</h1>
                        <p>Reset security guard passwords</p>
                    </div>
                </div>
                <div class="top-nav-right">
                    <button id="darkModeToggle" title="Toggle Dark Mode" style="background:none;border:2px solid #e5e7eb;border-radius:10px;padding:7px 10px;cursor:pointer;display:flex;align-items:center;gap:6px;color:#6b7280;font-size:0.85rem;font-weight:600;transition:all 0.3s;">
                        <svg id="darkModeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px;height:18px;stroke-width:2;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span class="dark-btn-label">Dark</span>
                    </button>
                    <div class="user-info">
                        <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                        <div class="user-details">
                            <span>Admin</span>
                            <strong><?= htmlspecialchars($adminName) ?></strong>
                        </div>
                    </div>
                    <a href="logout.php" class="btn-logout-modern">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="settings-card">
                    <h2>🔒 Reset Guard Password</h2>
                    <p class="sub">
                        If a security guard has forgotten their password, select their name below and set a new one.
                        Ask them to change it after logging in.
                    </p>

                    <?php if ($success): ?>
                        <div class="alert-success">✅ <?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (empty($guards)): ?>
                        <div class="alert-error">No security guards found in the system. Please add guards first via <a href="manage_guards.php">Manage Guards</a>.</div>
                    <?php else: ?>
                    <form method="POST">
                        <label class="form-label" for="guard_id">Select Security Guard</label>
                        <select name="guard_id" id="guard_id" class="form-select" required>
                            <option value="">— Choose a guard —</option>
                            <?php foreach ($guards as $g): ?>
                                <option value="<?= $g['id'] ?>"
                                    <?= (isset($_POST['guard_id']) && $_POST['guard_id'] == $g['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['name']) ?> (ID: <?= htmlspecialchars($g['employee_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="form-label" for="new_password">New Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="new_password" name="new_password"
                                   class="form-input" placeholder="At least 6 characters" required minlength="6">
                            <button type="button" class="pw-toggle" onclick="togglePw('new_password')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>

                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="form-input" placeholder="Repeat new password" required minlength="6">
                            <button type="button" class="pw-toggle" onclick="togglePw('confirm_password')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>

                        <button type="submit" class="btn-reset">Reset Guard Password</button>
                    </form>

                    <hr class="divider">
                    <div class="info-box">
                        <strong>📋 How this works</strong>
                        If a security guard forgets their password, they should approach the administrator.
                        Select the guard's name, set a temporary password, and let them know.
                        They can log in and use their guard dashboard normally.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
function togglePw(id) {
    var el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
