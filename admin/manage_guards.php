<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../config/db.php';

$guards = $pdo->query("SELECT * FROM guards ORDER BY id DESC")->fetchAll();
$adminName = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Guards</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .guard-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg,#8b5cf6,#a78bfa); display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1rem; }
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

        /* === DARK MODE === */
        html.dark-mode .back-btn, body.dark-mode .back-btn {
            background: #1e293b !important; color: #93c5fd !important; border-color: #334155 !important;
        }
        html.dark-mode .back-btn:hover, body.dark-mode .back-btn:hover {
            background: #3b82f6 !important; color: white !important; border-color: #3b82f6 !important;
        }
        html.dark-mode .hamburger-btn svg, body.dark-mode .hamburger-btn svg { stroke: #94a3b8 !important; }
        html.dark-mode .hamburger-btn:hover, body.dark-mode .hamburger-btn:hover { background: #334155 !important; }
    </style>
<script>
    // 1) Apply dark mode to <html> immediately (no flash on page load)
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }

    // 2) When body is ready, sync class to <body> and wire the toggle button
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('svaps_dark') === '1') {
            document.body.classList.add('dark-mode');
        }
        var btn = document.getElementById('darkModeToggle');
        var icon = document.getElementById('darkModeIcon');
        function setIcon(on) {
            if (!icon) return;
            icon.innerHTML = on
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2.34M18.36 5.64l-.71.71M6.34 17.66l-.71.71M18.36 18.36l-.71-.71M6.34 6.34l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />';
        }
        setIcon(localStorage.getItem('svaps_dark') === '1');
        if (btn) {
            btn.addEventListener('click', function() {
                var on = !document.body.classList.contains('dark-mode');
                localStorage.setItem('svaps_dark', on ? '1' : '0');
                document.documentElement.classList.toggle('dark-mode', on);
                document.body.classList.toggle('dark-mode', on);
                setIcon(on);
            });
        }
    });

    // 3) Listen for changes from OTHER open tabs
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
<body class="admin-theme-dash">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand"><h2>SVAPS Admin</h2><p>Vehicle Management</p></div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Dashboard</a>
                <a href="register_vehicle.php" class="menu-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>Register Vehicle</a>
                <a href="manage_guards.php" class="menu-item active"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage Guards</a>
                <a href="test_qr.php" class="menu-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>Test QR Codes</a>
                <a href="signup.php" class="menu-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Settings</a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-nav">
                <div class="top-nav-left" style="display:flex;align-items:center;gap:12px;">
                    <button class="hamburger-btn" onclick="toggleSidebar()"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                    <div><h1>Manage Guards</h1><p>Registered security personnel</p></div>
                </div>
                <div class="top-nav-right">
                    <div class="user-info">
                        <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                        <div class="user-details"><span>Admin</span><strong><?= htmlspecialchars($adminName) ?></strong></div>
                    </div>
                    <button id="darkModeToggle" title="Toggle Dark Mode" style="background:none;border:2px solid #e5e7eb;border-radius:10px;padding:7px 10px;cursor:pointer;display:flex;align-items:center;gap:6px;color:#6b7280;font-size:0.85rem;font-weight:600;transition:all 0.3s;">
                        <svg id="darkModeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px;height:18px;stroke-width:2;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span>Dark</span>
                    </button>
                    <a href="logout.php" class="btn-logout-modern">Logout</a>
                </div>
            </div>
            <div class="dashboard-content">
                <div class="data-card">
                    <div class="data-card-header">
                        <h3>Security Guards (<?= count($guards) ?>)</h3>
                    </div>
                    <div class="table-wrapper-modern">
                        <?php if (count($guards) > 0): ?>
                        <table class="table-modern">
                            <thead><tr><th>Avatar</th><th>Name</th><th>Employee ID</th><th>Sex</th></tr></thead>
                            <tbody>
                                <?php foreach ($guards as $g): ?>
                                <tr>
                                    <td><div class="guard-avatar"><?= strtoupper(substr($g['name'], 0, 1)) ?></div></td>
                                    <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($g['employee_id']) ?></td>
                                    <td><?= htmlspecialchars($g['sex'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state"><p>No guards registered yet.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>

        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('active'); }
        function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('active'); }
    </script>
</body>
</html>
