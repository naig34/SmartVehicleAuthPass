<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

$student_id = $_SESSION['student_id'];

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

function generateQRCode($data, $filename) {
    $qr_data = urlencode($data);
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $qr_data;

    if (!file_exists(__DIR__ . '/../uploads/qr_codes/')) {
        mkdir(__DIR__ . '/../uploads/qr_codes/', 0777, true);
    }

    $qr_content = file_get_contents($qr_url);
    if ($qr_content) {
        $qr_path = __DIR__ . '/../uploads/qr_codes/' . $filename;
        file_put_contents($qr_path, $qr_content);
        return 'uploads/qr_codes/' . $filename;
    }
    return null;
}

$qr_code_generated = false;
if (empty($student['qr_code_path'])) {
    $qr_data = json_encode([
        'type' => 'student',
        'id' => $student['id'],
        'name' => $student['name'],
        'school_id' => $student['school_id']
    ]);

    $qr_filename = 'student_' . $student['id'] . '_' . time() . '.png';
    $relative_qr_path = generateQRCode($qr_data, $qr_filename);

    if ($relative_qr_path) {
        $stmt = $pdo->prepare("UPDATE students SET qr_code_path = ? WHERE id = ?");
        $stmt->execute([$relative_qr_path, $student['id']]);
        $student['qr_code_path'] = $relative_qr_path;
        $qr_code_generated = true;
    }
}

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE owner_type = 'Student' AND (owner_id = ? OR student_owner_id = ?)");
$stmt->execute([$student_id, $student_id]);
$vehicles = $stmt->fetchAll();

$today = date('Y-m-d');
foreach ($vehicles as &$vehicle) {
    if ($vehicle['date_expiration'] < $today && $vehicle['status'] !== 'Revoked') {
        $stmt = $pdo->prepare("UPDATE vehicles SET status = 'Expired' WHERE id = ?");
        $stmt->execute([$vehicle['id']]);
        $vehicle['status'] = 'Expired';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --student-primary: #3b82f6;
            --student-primary-light: #60a5fa;
            --student-primary-rgb: 59, 130, 246;
        }

        .student-dashboard {
            --theme-color: var(--student-primary);
            --theme-color-light: var(--student-primary-light);
            --theme-color-rgb: var(--student-primary-rgb);
        }

        .hero-section {
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-primary-light) 100%);
            padding: 40px;
            border-radius: 20px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(59, 130, 246, 0.3);
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
        }

        .hero-text h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .hero-text p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .hero-stats {
            display: flex;
            gap: 30px;
        }

        .hero-stat {
            text-align: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 20px 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .dashboard-grid-custom {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .profile-card-modern {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .profile-avatar-large {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 5px solid var(--student-primary);
            padding: 5px;
            margin: 0 auto 20px;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-name-large {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .profile-id-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-primary-light) 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .info-chips {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 20px 0;
        }

        .info-chip {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 12px;
            text-align: left;
        }

        .info-chip-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }

        .info-chip-value {
            font-size: 1rem;
            color: #1f2937;
            font-weight: 600;
        }

        .qr-card-modern {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .qr-card-modern h4 {
            font-size: 1.1rem;
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .qr-code-display {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .qr-code-display img {
            width: 100%;
            max-width: 220px;
            height: auto;
            border-radius: 8px;
        }

        .vehicle-cards-grid {
            display: grid;
            gap: 20px;
        }

        .vehicle-card-modern {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .vehicle-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .vehicle-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .vehicle-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-primary-light) 100%);
            color: white;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .vehicle-status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .vehicle-plate {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            letter-spacing: 2px;
        }

        .vehicle-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .vehicle-detail-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
        }

        .vehicle-detail-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }

        .vehicle-detail-value {
            font-size: 1.05rem;
            color: #1f2937;
            font-weight: 600;
        }

        .empty-state-modern {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-icon svg {
            width: 40px;
            height: 40px;
            color: #9ca3af;
        }

        .logout-btn-modern {
            position: fixed;
            top: 25px;
            right: 25px;
            background: white;
            color: var(--student-primary);
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .logout-btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .profile-upload-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-primary-light) 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .profile-upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        @media (max-width: 1024px) {
            .dashboard-grid-custom {
                grid-template-columns: 1fr;
            }

            .hero-content {
                flex-direction: column;
                text-align: center;
            }

            .hero-stats {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 1.8rem;
            }

            .vehicle-details-grid {
                grid-template-columns: 1fr;
            }
        }

        /* === DARK MODE === */
        html.dark-mode body, body.dark-mode,
        html.dark-mode .student-dashboard, body.dark-mode .student-dashboard,
        html.dark-mode .teacher-dashboard, body.dark-mode .teacher-dashboard {
            background: #0f172a !important;
            color: #e2e8f0 !important;
        }
        html.dark-mode .logout-btn-modern, body.dark-mode .logout-btn-modern {
            background: #1e293b !important; color: #93c5fd !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;
        }
        html.dark-mode #darkModeToggle, body.dark-mode #darkModeToggle {
            border-color: #334155 !important; color: #94a3b8 !important; background: #1e293b !important;
        }
        html.dark-mode .profile-card-modern, body.dark-mode .profile-card-modern {
            background: #1e293b !important; box-shadow: 0 4px 20px rgba(0,0,0,0.5) !important;
        }
        html.dark-mode .profile-name-large, body.dark-mode .profile-name-large { color: #f1f5f9 !important; }
        html.dark-mode .info-chip, body.dark-mode .info-chip { background: #0f172a !important; }
        html.dark-mode .info-chip-label, body.dark-mode .info-chip-label { color: #64748b !important; }
        html.dark-mode .info-chip-value, body.dark-mode .info-chip-value { color: #e2e8f0 !important; }
        html.dark-mode .qr-card-modern, body.dark-mode .qr-card-modern { background: #0f172a !important; }
        html.dark-mode .qr-card-modern h4, body.dark-mode .qr-card-modern h4 { color: #f1f5f9 !important; }
        html.dark-mode .qr-code-display, body.dark-mode .qr-code-display {
            background: #1e293b !important; box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;
        }
        html.dark-mode .vehicle-card-modern, body.dark-mode .vehicle-card-modern {
            background: #1e293b !important; box-shadow: 0 4px 20px rgba(0,0,0,0.5) !important;
        }
        html.dark-mode .vehicle-plate, body.dark-mode .vehicle-plate { color: #f1f5f9 !important; }
        html.dark-mode .vehicle-detail-item, body.dark-mode .vehicle-detail-item { background: #0f172a !important; }
        html.dark-mode .vehicle-detail-label, body.dark-mode .vehicle-detail-label { color: #64748b !important; }
        html.dark-mode .vehicle-detail-value, body.dark-mode .vehicle-detail-value { color: #e2e8f0 !important; }
        html.dark-mode .empty-state-modern, body.dark-mode .empty-state-modern {
            background: #1e293b !important; box-shadow: 0 4px 20px rgba(0,0,0,0.5) !important;
        }
        html.dark-mode .empty-state-modern h3, body.dark-mode .empty-state-modern h3 { color: #f1f5f9 !important; }
        html.dark-mode .empty-state-modern p, body.dark-mode .empty-state-modern p { color: #94a3b8 !important; }
        html.dark-mode .empty-icon, body.dark-mode .empty-icon {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
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
<body class="student-dashboard">
    <button id="darkModeToggle" title="Toggle Dark Mode" style="background:none;border:2px solid #e5e7eb;border-radius:10px;padding:7px 10px;cursor:pointer;display:flex;align-items:center;gap:6px;color:#6b7280;font-size:0.85rem;font-weight:600;transition:all 0.3s;margin-right:8px;">
        <svg id="darkModeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px;height:18px;stroke-width:2;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <span>Dark</span>
    </button>
    <a href="logout.php" class="logout-btn-modern">Logout</a>

    <div style="max-width: 1400px; margin: 0 auto;">
        <div class="hero-section">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Welcome back, <?= htmlspecialchars($student['name']) ?></h1>
                    <p>Here's your vehicle registration overview</p>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= count($vehicles) ?></span>
                        <span class="hero-stat-label">Vehicles</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= count(array_filter($vehicles, fn($v) => $v['status'] === 'Not Expired')) ?></span>
                        <span class="hero-stat-label">Active</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($qr_code_generated): ?>
            <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                <strong style="color: #065f46;">Success!</strong>
                <p style="color: #047857; margin-top: 5px;">Your QR code has been generated successfully.</p>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid-custom">
            <div>
                <div class="profile-card-modern">
                    <div class="profile-avatar-large">
                        <?php if (!empty($student['profile_picture'])): ?>
                            <img src="../<?= htmlspecialchars($student['profile_picture']) ?>" alt="Profile Picture">
                        <?php else: ?>
                            <img src="https://images.pexels.com/photos/1212984/pexels-photo-1212984.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Default Profile">
                        <?php endif; ?>
                    </div>

                    <div class="profile-name-large"><?= htmlspecialchars($student['name']) ?></div>
                    <div class="profile-id-badge">ID: <?= htmlspecialchars($student['school_id']) ?></div>

                    <a href="upload_profile_picture.php" class="profile-upload-btn">Change Profile Picture</a>


                    <div class="info-chips">
                        <?php if (!empty($student['email'])): ?>
                        <div class="info-chip">
                            <span class="info-chip-label">Email</span>
                            <span class="info-chip-value"><?= htmlspecialchars($student['email']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($student['course'])): ?>
                        <div class="info-chip">
                            <span class="info-chip-label">Course</span>
                            <span class="info-chip-value"><?= htmlspecialchars($student['course']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($student['year_section'])): ?>
                        <div class="info-chip">
                            <span class="info-chip-label">Year & Section</span>
                            <span class="info-chip-value"><?= htmlspecialchars($student['year_section']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($student['sex'])): ?>
                        <div class="info-chip">
                            <span class="info-chip-label">Gender</span>
                            <span class="info-chip-value"><?= htmlspecialchars($student['sex']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($student['age'])): ?>
                        <div class="info-chip">
                            <span class="info-chip-label">Age</span>
                            <span class="info-chip-value"><?= htmlspecialchars($student['age']) ?> years old</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="qr-card-modern">
                        <h4>Your QR Code</h4>
                        <div class="qr-code-display">
                            <?php if (!empty($student['qr_code_path'])): ?>
                                <img src="../<?= htmlspecialchars($student['qr_code_path']) ?>" alt="QR Code">
                                <p style="margin-top: 15px; color: #6b7280; font-size: 0.9rem;">Use this for identification</p>
                                <a href="../<?= htmlspecialchars($student['qr_code_path']) ?>" download="my_qr_code.png" style="display:inline-block;margin-top:12px;padding:8px 20px;background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);color:#fff;border-radius:8px;font-size:0.85rem;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(37,99,235,0.25);transition:opacity .2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">⬇ Download QR Code</a>
                            <?php else: ?>
                                <p style="color: #6b7280;">QR Code not available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h2 style="font-size: 1.8rem; color: #1f2937; margin-bottom: 20px; font-weight: 700;">My Vehicles</h2>

                <div class="vehicle-cards-grid">
                    <?php if (count($vehicles) > 0): ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <div class="vehicle-card-modern">

                            <?php if (!empty($vehicle['vehicle_image']) || !empty($student['profile_picture'])): ?>
                            <div style="margin-bottom:22px;">
                                <div style="font-size:0.8rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Uploaded Documents</div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <?php if (!empty($vehicle['vehicle_image'])): ?>
                                    <div>
                                        <div style="font-size:0.78rem;font-weight:600;color:#9ca3af;margin-bottom:6px;text-align:center;">OR-CR / License</div>
                                        <a href="../<?= htmlspecialchars($vehicle['vehicle_image']) ?>" target="_blank" title="View full size" style="display:block;">
                                            <img src="../<?= htmlspecialchars($vehicle['vehicle_image']) ?>" alt="OR-CR / License"
                                                style="width:100%;height:110px;object-fit:cover;border-radius:10px;border:2px solid #e5e7eb;transition:opacity .2s;"
                                                onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($student['profile_picture'])): ?>
                                    <div>
                                        <div style="font-size:0.78rem;font-weight:600;color:#9ca3af;margin-bottom:6px;text-align:center;">Owner's License</div>
                                        <a href="../<?= htmlspecialchars($student['profile_picture']) ?>" target="_blank" title="View full size" style="display:block;">
                                            <img src="../<?= htmlspecialchars($student['profile_picture']) ?>" alt="Owner's License"
                                                style="width:100%;height:110px;object-fit:cover;border-radius:10px;border:2px solid #e5e7eb;transition:opacity .2s;"
                                                onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>


                            <div class="vehicle-card-header">
                                <div class="vehicle-type-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0h-.01M15 17a2 2 0 104 0m-4 0h-.01" />
                                    </svg>
                                    <?= htmlspecialchars($vehicle['type']) ?>
                                </div>
                                <div class="vehicle-status-badge status-<?= $vehicle['status'] === 'Not Expired' ? 'active' : 'expired' ?>">
                                    <?= htmlspecialchars($vehicle['status']) ?>
                                </div>
                            </div>

                            <div class="vehicle-plate"><?= htmlspecialchars($vehicle['plate_number']) ?></div>

                            <div class="vehicle-details-grid">
                                <div class="vehicle-detail-item">
                                    <span class="vehicle-detail-label">Brand</span>
                                    <span class="vehicle-detail-value"><?= htmlspecialchars($vehicle['brand']) ?></span>
                                </div>
                                <div class="vehicle-detail-item">
                                    <span class="vehicle-detail-label">Color</span>
                                    <span class="vehicle-detail-value"><?= htmlspecialchars($vehicle['color']) ?></span>
                                </div>
                                <div class="vehicle-detail-item">
                                    <span class="vehicle-detail-label">Registered</span>
                                    <span class="vehicle-detail-value"><?= date('M d, Y', strtotime($vehicle['date_registered'])) ?></span>
                                </div>
                                <div class="vehicle-detail-item">
                                    <span class="vehicle-detail-label">Expires</span>
                                    <span class="vehicle-detail-value"><?= date('M d, Y', strtotime($vehicle['date_expiration'])) ?></span>
                                </div>
                            </div>

                            <?php if (!empty($vehicle['qr_code_path'])): ?>
                            <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                                <h4 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 15px; font-weight: 600; text-align: center;">Vehicle QR Code</h4>
                                <div style="background: white; padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                    <img src="../<?= htmlspecialchars($vehicle['qr_code_path']) ?>" alt="Vehicle QR Code" style="max-width: 200px; height: auto; border-radius: 8px;">
                                    <p style="margin-top: 12px; color: #6b7280; font-size: 0.85rem;">Scan at checkpoint</p>
                                    <a href="../<?= htmlspecialchars($vehicle['qr_code_path']) ?>" download="vehicle_qr_<?= htmlspecialchars($vehicle['plate_number']) ?>.png" style="display:inline-block;margin-top:10px;padding:8px 20px;background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);color:#fff;border-radius:8px;font-size:0.85rem;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(37,99,235,0.25);transition:opacity .2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">⬇ Download QR Code</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state-modern">
                            <div class="empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0h-.01M15 17a2 2 0 104 0m-4 0h-.01" />
                                </svg>
                            </div>
                            <h3 style="color: #1f2937; font-size: 1.4rem; margin-bottom: 10px;">No Vehicles Registered</h3>
                            <p style="color: #6b7280; font-size: 1.05rem;">Contact the admin to register your vehicle and get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
