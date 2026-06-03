<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

$vehicleId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT v.*,
    CASE
        WHEN v.owner_type = 'Teacher/Staff' THEN t.name
        ELSE s.name
    END as owner_name
    FROM vehicles v
    LEFT JOIN teachers_staff t ON v.owner_type = 'Teacher/Staff' AND v.owner_id = t.id
    LEFT JOIN students s ON v.owner_type = 'Student' AND v.owner_id = s.id
    WHERE v.id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View QR Code</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .qr-view-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
        }

        .qr-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .qr-card-header {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .qr-card-header h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .qr-card-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .vehicle-info-section {
            background: #f9fafb;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
        }

        .info-value {
            font-weight: 700;
            color: #1f2937;
        }

        .qr-code-section {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .qr-code-section h3 {
            font-size: 1.3rem;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .qr-code-display {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .qr-code-display img {
            max-width: 300px;
            height: auto;
            border-radius: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn-modern {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary-modern {
            background: #f3f4f6;
            color: #4b5563;
        }

        .btn-secondary-modern:hover {
            background: #e5e7eb;
        }

        .status-badge-large {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 10px;
        }

        .status-valid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }

        /* === DARK MODE === */
        html.dark-mode body, body.dark-mode { background: #0f172a !important; }
        html.dark-mode .qr-view-container, body.dark-mode .qr-view-container { background: #0f172a !important; }
        html.dark-mode .qr-card, body.dark-mode .qr-card {
            background: #1e293b !important; box-shadow: 0 20px 60px rgba(0,0,0,0.6) !important;
        }
        html.dark-mode .qr-card-header, body.dark-mode .qr-card-header { border-bottom-color: #334155 !important; }
        html.dark-mode .qr-card-header h2, body.dark-mode .qr-card-header h2 { color: #f1f5f9 !important; }
        html.dark-mode .qr-card-header p, body.dark-mode .qr-card-header p { color: #94a3b8 !important; }
        html.dark-mode .vehicle-info-section, body.dark-mode .vehicle-info-section { background: #0f172a !important; }
        html.dark-mode .info-row, body.dark-mode .info-row { border-bottom-color: #334155 !important; }
        html.dark-mode .info-label, body.dark-mode .info-label { color: #64748b !important; }
        html.dark-mode .info-value, body.dark-mode .info-value { color: #e2e8f0 !important; }
        html.dark-mode .qr-code-section h3, body.dark-mode .qr-code-section h3 { color: #f1f5f9 !important; }
        html.dark-mode .qr-code-display, body.dark-mode .qr-code-display {
            background: white !important; /* keep white for QR readability */
        }
        html.dark-mode .btn-secondary-modern, body.dark-mode .btn-secondary-modern {
            background: #334155 !important; color: #cbd5e1 !important;
        }
        html.dark-mode .btn-secondary-modern:hover, body.dark-mode .btn-secondary-modern:hover {
            background: #475569 !important;
        }
        html.dark-mode #darkModeToggle, body.dark-mode #darkModeToggle {
            border-color: #334155 !important; color: #94a3b8 !important; background: #1e293b !important;
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
<body>
    <div class="qr-view-container">
        <div class="qr-card">
            <div class="qr-card-header">
                <h2>Vehicle QR Code</h2>
                <p><?= htmlspecialchars($vehicle['plate_number']) ?></p>
            </div>

            <div class="vehicle-info-section">
                <div class="info-row">
                    <span class="info-label">Owner</span>
                    <span class="info-value"><?= htmlspecialchars($vehicle['owner_name'] ?? 'Unknown') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Vehicle Type</span>
                    <span class="info-value"><?= htmlspecialchars($vehicle['type']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Brand</span>
                    <span class="info-value"><?= htmlspecialchars($vehicle['brand']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Color</span>
                    <span class="info-value"><?= htmlspecialchars($vehicle['color']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Registration Date</span>
                    <span class="info-value"><?= date('M d, Y', strtotime($vehicle['date_registered'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Expiration Date</span>
                    <span class="info-value"><?= date('M d, Y', strtotime($vehicle['date_expiration'])) ?></span>
                </div>
            </div>

            <div style="text-align: center; margin-bottom: 25px;">
                <span class="status-badge-large status-<?= $vehicle['status'] === 'Not Expired' ? 'valid' : 'expired' ?>">
                    <?= htmlspecialchars($vehicle['status']) ?>
                </span>
            </div>

            <div class="qr-code-section">
                <h3>QR Code</h3>
                <?php if ($vehicle['qr_code_path']): ?>
                    <div class="qr-code-display">
                        <img src="../<?= htmlspecialchars($vehicle['qr_code_path']) ?>" alt="QR Code">
                    </div>
                    <p style="margin-top: 20px; color: #6b7280; font-size: 0.95rem;">
                        Scan this code at security checkpoints
                    </p>
                <?php else: ?>
                    <p style="color: #ef4444; font-weight: 600;">QR Code not available</p>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="dashboard.php" class="btn-modern btn-secondary-modern">Back to Dashboard</a>
                <?php if ($vehicle['qr_code_path']): ?>
                    <button onclick="window.print()" class="btn-modern btn-primary-modern">Print QR Code</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style media="print">
        body * {
            visibility: hidden;
        }
        .qr-code-section, .qr-code-section * {
            visibility: visible;
        }
        .qr-code-section {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        .action-buttons {
            display: none;
        }
    </style>
</body>
</html>
