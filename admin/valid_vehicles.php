<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../config/db.php';
require_once '../config/helpers.php';

$stmt = $pdo->query("SELECT v.*,
    CASE WHEN v.owner_type = 'Teacher/Staff' THEN t.name ELSE s.name END as owner_name,
    CASE WHEN v.owner_type = 'Teacher/Staff' THEN t.profile_picture ELSE s.profile_picture END as owner_photo
    FROM vehicles v
    INNER JOIN (
        SELECT plate_number, MAX(id) as max_id
        FROM vehicles
        GROUP BY plate_number
    ) latest ON v.id = latest.max_id
    LEFT JOIN teachers_staff t ON v.owner_type = 'Teacher/Staff' AND t.id = v.owner_id
    LEFT JOIN students s ON v.owner_type = 'Student' AND s.id = v.owner_id
    ORDER BY v.id DESC");
$allVehicles = $stmt->fetchAll();
$vehicles = [];
foreach ($allVehicles as &$v) {
    $v['status'] = updateVehicleStatus($pdo, $v['id']);
    if ($v['status'] === 'Not Expired') $vehicles[] = $v;
}
$adminName = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valid Vehicles</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .page-header-info { background: linear-gradient(135deg, #10b981, #34d399); color: white; padding: 20px 28px; border-radius: 14px; margin-bottom: 24px; }
        .page-header-info h2 { font-size: 1.5rem; font-weight: 800; }
        .page-header-info p { opacity: 0.85; margin-top: 4px; }
        .back-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: white; color: #10b981; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; border: 2px solid #10b981; transition: all 0.2s; margin-bottom: 20px; }
        .back-btn:hover { background: #10b981; color: white; }
        .hamburger-btn { display: none; background: none; border: none; cursor: pointer; padding: 8px; border-radius: 8px; }
        .hamburger-btn svg { width: 24px; height: 24px; stroke: #374151; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 99; }
        .sidebar-overlay.active { display: block; }
        @media (max-width: 768px) {
            .hamburger-btn { display: flex; align-items: center; }
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.open { transform: translateX(0); }
            .top-nav-left h1 { font-size: 1.1rem; }
            .top-nav-left p { display: none; }
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
                <a href="manage_guards.php" class="menu-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Manage Guards</a>
                <a href="signup.php" class="menu-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Settings</a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-nav">
                <div class="top-nav-left" style="display:flex;align-items:center;gap:12px;">
                    <button class="hamburger-btn" onclick="toggleSidebar()"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                    <div><h1>Valid Vehicles</h1><p>All currently active vehicles</p></div>
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
                <a href="dashboard.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Dashboard</a>
                <div class="page-header-info">
                    <h2>Valid / Active Vehicles (<?= count($vehicles) ?>)</h2>
                    <p>These vehicles have a valid, non-expired registration in the system.</p>
                </div>
                <div class="data-card">
                    <div class="table-wrapper-modern">
                        <?php if (count($vehicles) > 0): ?>
                        <table class="table-modern">
                            <thead><tr><th>Photo</th><th>Plate No.</th><th>Owner</th><th>Owner Type</th><th>Vehicle Type</th><th>Brand</th><th>Color</th><th>Registered</th><th>Expiration</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($vehicles as $v): ?>
                                <tr>
                                    <td><?php if (!empty($v['owner_photo'])): ?><img src="../<?= htmlspecialchars($v['owner_photo']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:50%;"><?php else: ?><div style="width:50px;height:50px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" style="width:24px;height:24px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div><?php endif; ?></td>
                                    <td><strong><?= htmlspecialchars($v['plate_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($v['owner_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($v['owner_type']) ?></td>
                                    <td><?= htmlspecialchars($v['type']) ?></td>
                                    <td><?= htmlspecialchars($v['brand']) ?></td>
                                    <td><?= htmlspecialchars($v['color'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['date_registered']) ?></td>
                                    <td><?= htmlspecialchars($v['date_expiration']) ?></td>
                                    <td><div class="table-actions"><a href="view_qr.php?id=<?= $v['id'] ?>" class="btn-table btn-primary-table">QR</a><a href="edit_vehicle.php?id=<?= $v['id'] ?>" class="btn-table btn-warning-table">Edit</a></div></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?><div class="empty-state"><p>No valid vehicles found.</p></div><?php endif; ?>
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
