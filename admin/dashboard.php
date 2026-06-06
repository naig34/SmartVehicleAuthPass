<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';
require_once '../config/helpers.php';

$adminName = $_SESSION['admin_name'];

$stmt = $pdo->query("SELECT v.*,
    CASE
        WHEN v.owner_type = 'Teacher/Staff' THEN t.name
        ELSE s.name
    END as owner_name,
    CASE
        WHEN v.picture IS NOT NULL AND v.picture != '' THEN v.picture
        WHEN v.owner_type = 'Teacher/Staff' THEN t.profile_picture
        ELSE s.profile_picture
    END as owner_photo
    FROM vehicles v
    LEFT JOIN teachers_staff t ON v.owner_type = 'Teacher/Staff' AND t.id = COALESCE(v.student_owner_id, v.teacher_owner_id)
    LEFT JOIN students s ON v.owner_type = 'Student' AND s.id = COALESCE(v.student_owner_id, v.teacher_owner_id)
    GROUP BY v.plate_number
    ORDER BY v.id DESC");
$vehicles = $stmt->fetchAll();

foreach ($vehicles as &$vehicle) {
    $vehicle['status'] = updateVehicleStatus($pdo, $vehicle['id']);
}

$totalVehicles = count($vehicles);
$validVehicles = count(array_filter($vehicles, fn($v) => $v['status'] === 'Not Expired'));
$expiredVehicles = count(array_filter($vehicles, fn($v) => $v['status'] === 'Expired'));
$revokedVehicles = count(array_filter($vehicles, fn($v) => $v['status'] === 'Revoked'));

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers_staff")->fetchColumn();
$totalGuards = $pdo->query("SELECT COUNT(*) FROM guards")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Mobile hamburger */
        .hamburger-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .hamburger-btn:hover { background: #f3f4f6; }
        .hamburger-btn svg { width: 24px; height: 24px; stroke: #374151; }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 99;
        }
        .sidebar-overlay.active { display: block; }
        .stat-card-modern { cursor: pointer; }
        .stat-card-modern a-wrap { display: block; text-decoration: none; color: inherit; }
        @media (max-width: 768px) {
            .hamburger-btn { display: flex; align-items: center; }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.open { transform: translateX(0); }
            .top-nav-left h1 { font-size: 1.1rem; }
            .top-nav-left p { display: none; }
        }
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
            <div class="sidebar-brand">
                <h2>SVAPS Admin</h2>
                <p>Vehicle Management</p>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
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
                <a href="signup.php" class="menu-item">
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
                        <h1>Dashboard Overview</h1>
                        <p>Manage vehicles and system operations</p>
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
                <div class="stats-grid-modern">
                    <a href="total_vehicles.php" style="text-decoration:none;">
                    <div class="stat-card-modern blue">
                        <div class="stat-card-header">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number"><?= $totalVehicles ?></div>
                        <div class="stat-label">Total Vehicles</div>
                    </div>
                    </a>

                    <a href="valid_vehicles.php" style="text-decoration:none;">
                    <div class="stat-card-modern green">
                        <div class="stat-card-header">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number"><?= $validVehicles ?></div>
                        <div class="stat-label">Valid Vehicles</div>
                    </div>
                    </a>

                    <a href="total_users.php" style="text-decoration:none;">
                    <div class="stat-card-modern orange">
                        <div class="stat-card-header">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number"><?= $totalStudents + $totalTeachers ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    </a>

                    <a href="total_guards.php" style="text-decoration:none;">
                    <div class="stat-card-modern purple">
                        <div class="stat-card-header">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-number"><?= $totalGuards ?></div>
                        <div class="stat-label">Security Guards</div>
                    </div>
                    </a>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <h3>Registered Vehicles</h3>
                    </div>
                    <div class="table-wrapper-modern">
                        <?php
                        // Only show Not Expired and Expired — exclude Revoked
                        $displayVehicles = array_filter($vehicles, fn($v) => $v['status'] !== 'Revoked');
                        ?>
                        <?php if (count($displayVehicles) > 0): ?>
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Plate No.</th>
                                    <th>Owner</th>
                                    <th>Owner Type</th>
                                    <th>Vehicle Type</th>
                                    <th>Brand</th>
                                    <th>Color</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Expiration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($displayVehicles as $v): ?>
                                <?php $sc = $v['status'] === 'Expired' ? 'status-expired' : 'status-valid'; ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($v['owner_photo'])): ?>
                                            <?php $photoSrc = (strpos($v['owner_photo'], 'http') === 0) ? $v['owner_photo'] : '../' . $v['owner_photo']; ?>
                                            <img src="<?= htmlspecialchars($photoSrc) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#e5e7eb,#d1d5db);display:flex;align-items:center;justify-content:center;">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" style="width:24px;height:24px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($v['plate_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($v['owner_name'] ?? 'Unknown') ?></td>
                                    <td>
                                        <span style="padding:3px 10px;border-radius:20px;font-size:0.78rem;font-weight:600;<?= $v['owner_type']==='Student' ? 'background:#dbeafe;color:#1d4ed8;' : 'background:#dcfce7;color:#166534;' ?>">
                                            <?= htmlspecialchars($v['owner_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($v['type']) ?></td>
                                    <td><?= htmlspecialchars($v['brand'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['color'] ?? '-') ?></td>
                                    <td><span class="status-badge <?= $sc ?>"><?= htmlspecialchars($v['status']) ?></span></td>
                                    <td><?= htmlspecialchars($v['date_registered']) ?></td>
                                    <td><?= htmlspecialchars($v['date_expiration']) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="view_qr.php?id=<?= $v['id'] ?>" class="btn-table btn-primary-table">QR</a>
                                            <a href="edit_vehicle.php?id=<?= $v['id'] ?>" class="btn-table btn-warning-table">Edit</a>
                                            <a href="delete_vehicle.php?id=<?= $v['id'] ?>" class="btn-table btn-danger-table" onclick="return confirm('Delete vehicle <?= htmlspecialchars($v['plate_number']) ?>? This cannot be undone.');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3>No Vehicles Registered</h3>
                            <p>Start by registering your first vehicle</p>
                        </div>
                        <?php endif; ?>
                    </div>
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
    </script>
</body>
</html>