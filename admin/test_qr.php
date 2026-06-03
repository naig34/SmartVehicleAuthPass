<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';
require_once '../config/helpers.php';

$stmt = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC LIMIT 10");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test QR Codes</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .test-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .vehicle-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .vehicle-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
            color: #1f2937;
        }

        .qr-section {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .qr-display img {
            border: 3px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }

        .qr-data {
            flex: 1;
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
        }

        .qr-data h4 {
            margin-bottom: 10px;
            color: #1f2937;
        }

        .qr-data pre {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
        }

        .test-button {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .regenerate-btn {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        }

        .back-btn {
            background: #6b7280;
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
<body>
    <div class="test-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: #1f2937;">QR Code Testing & Verification</h1>
            <a href="dashboard.php" class="test-button back-btn">Back to Dashboard</a>
        </div>

        <?php if (empty($vehicles)): ?>
            <div class="vehicle-card">
                <p style="text-align: center; color: #6b7280;">No vehicles registered yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="vehicle-card">
                    <div class="vehicle-header">
                        <div>
                            <h3 style="color: #1f2937; margin-bottom: 5px;">Vehicle #<?= $vehicle['id'] ?></h3>
                            <p style="color: #6b7280; font-size: 0.9rem;">Registered: <?= date('M d, Y', strtotime($vehicle['date_registered'])) ?></p>
                        </div>
                        <div>
                            <span class="status-badge <?= getStatusClass($vehicle['status']) ?>">
                                <?= htmlspecialchars($vehicle['status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="vehicle-info">
                        <div class="info-item">
                            <div class="info-label">Plate Number</div>
                            <div class="info-value"><?= htmlspecialchars($vehicle['plate_number']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Owner Type</div>
                            <div class="info-value"><?= htmlspecialchars($vehicle['owner_type']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Vehicle Type</div>
                            <div class="info-value"><?= htmlspecialchars($vehicle['type']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Registered Under</div>
                            <div class="info-value"><?= htmlspecialchars($vehicle['registered_under']) ?></div>
                        </div>
                    </div>

                    <div class="qr-section">
                        <div class="qr-display">
                            <?php if ($vehicle['qr_code_path'] && file_exists('../' . $vehicle['qr_code_path'])): ?>
                                <img src="../<?= htmlspecialchars($vehicle['qr_code_path']) ?>" alt="QR Code" style="width: 200px; height: 200px;">
                                <p style="text-align: center; margin-top: 10px; color: #10b981; font-weight: 600;">✓ QR Code exists</p>
                            <?php else: ?>
                                <div style="width: 200px; height: 200px; background: #fee2e2; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #991b1b;">
                                    <p>QR Code Missing</p>
                                </div>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="regenerate_qr" value="<?= $vehicle['id'] ?>">
                                    <button type="submit" class="test-button regenerate-btn">Regenerate QR</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="qr-data">
                            <h4>Expected QR Code Data:</h4>
                            <pre><?php
$qrData = json_encode([
    'vehicle_id' => $vehicle['plate_number'],
    'plate' => $vehicle['plate_number'],
    'owner' => $vehicle['registered_under'],
    'type' => $vehicle['type']
], JSON_PRETTY_PRINT);
echo htmlspecialchars($qrData);
?></pre>
                            <p style="margin-top: 15px; color: #6b7280; font-size: 0.9rem;">
                                <strong>How to test:</strong><br>
                                1. Open the guard dashboard on another device or window<br>
                                2. Scan this QR code with the scanner<br>
                                3. Or use the manual search with plate: <strong><?= htmlspecialchars($vehicle['plate_number']) ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
