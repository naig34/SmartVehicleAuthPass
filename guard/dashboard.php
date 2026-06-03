<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

$guard_id = $_SESSION['guard_id'];
$stmt = $pdo->prepare("SELECT * FROM guards WHERE id = ?");
$stmt->execute([$guard_id]);
$guard = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .scanner-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            max-width: 700px;
            margin: 0 auto;
        }

        .scanner-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .scanner-header h3 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .scanner-header p {
            color: #6b7280;
        }

        #reader {
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid #e5e7eb;
        }

        .scan-controls {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: center;
        }

        .btn-scan {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-scan-start {
            background: linear-gradient(135deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            color: white;
        }

        .btn-scan-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--theme-color-rgb), 0.4);
        }

        .btn-scan-stop {
            background: #ef4444;
            color: white;
        }

        .btn-scan-stop:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .vehicle-result {
            margin-top: 30px;
            padding: 30px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }

        .vehicle-result .status-indicator {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .status-indicator.valid {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .status-indicator.expired {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .status-indicator.revoked {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #f59e0b;
        }

        .vehicle-info-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .info-box {
            padding: 15px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .info-box strong {
            display: block;
            color: #6b7280;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .info-box span {
            color: #1f2937;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .vehicle-image {
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            display: block;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* === DARK MODE === */
        html.dark-mode .scanner-container, body.dark-mode .scanner-container {
            background: #1e293b !important; box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;
        }
        html.dark-mode .scanner-header h3, body.dark-mode .scanner-header h3 { color: #f1f5f9 !important; }
        html.dark-mode .scanner-header p, body.dark-mode .scanner-header p { color: #94a3b8 !important; }
        html.dark-mode #reader, body.dark-mode #reader { border-color: #334155 !important; }
        html.dark-mode .vehicle-result, body.dark-mode .vehicle-result {
            background: #0f172a !important; border-color: #334155 !important;
        }
        html.dark-mode .info-box, body.dark-mode .info-box {
            background: #1e293b !important; border-color: #334155 !important;
        }
        html.dark-mode .info-box strong, body.dark-mode .info-box strong { color: #64748b !important; }
        html.dark-mode .info-box span, body.dark-mode .info-box span { color: #e2e8f0 !important; }
        html.dark-mode input[type="text"], body.dark-mode input[type="text"] {
            background: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important;
        }
        html.dark-mode input[type="text"]::placeholder, body.dark-mode input[type="text"]::placeholder {
            color: #475569 !important;
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
<body class="guard-theme-dash">
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>SVAPS Guard</h2>
                <p>Vehicle Scanner</p>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    QR Scanner
                </a>
                <a href="change_password.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Change Password
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-nav">
                <div class="top-nav-left">
                    <h1>Vehicle Scanner</h1>
                    <p>Scan QR codes to verify vehicles</p>
                </div>
                <div class="top-nav-right">
                    <div class="user-info">
                        <div class="user-avatar"><?= strtoupper(substr($guard['name'], 0, 1)) ?></div>
                        <div class="user-details">
                            <span>Guard</span>
                            <strong><?= htmlspecialchars($guard['name']) ?></strong>
                        </div>
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
                <div class="scanner-container">
                    <div class="scanner-header">
                        <h3>Scan Vehicle QR Code</h3>
                        <p>Point your camera at the vehicle's QR code to verify</p>
                    </div>

                    <div id="reader"></div>

                    <div class="scan-controls">
                        <button id="startScan" class="btn-scan btn-scan-start">Start Scanner</button>
                        <button id="stopScan" class="btn-scan btn-scan-stop" style="display:none;">Stop Scanner</button>
                    </div>

                    <div style="margin-top: 30px; text-align: center;">
                        <p style="color: #6b7280; margin-bottom: 15px;">Or enter plate number manually:</p>
                        <div style="display: flex; gap: 10px; max-width: 500px; margin: 0 auto;">
                            <input type="text" id="manualPlate" placeholder="Enter plate number (e.g., ABC123)"
                                   style="flex: 1; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem;">
                            <button id="manualSearch" class="btn-scan btn-scan-start">Search</button>
                        </div>
                    </div>

                    <div id="result" style="display:none;"></div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>

        let html5QrCode;
        let isScanning = false;

        document.getElementById('startScan').addEventListener('click', function() {
            html5QrCode = new Html5Qrcode("reader");

            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                onScanSuccess
            ).then(() => {
                isScanning = true;
                document.getElementById('startScan').style.display = 'none';
                document.getElementById('stopScan').style.display = 'inline-block';
                document.getElementById('result').style.display = 'none';
            }).catch(err => {
                console.error(err);
                alert('Failed to start camera. Please ensure camera permissions are granted.');
            });
        });

        document.getElementById('stopScan').addEventListener('click', function() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    document.getElementById('startScan').style.display = 'inline-block';
                    document.getElementById('stopScan').style.display = 'none';
                }).catch(err => {
                    console.error(err);
                });
            }
        });

        function onScanSuccess(decodedText) {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    document.getElementById('startScan').style.display = 'inline-block';
                    document.getElementById('stopScan').style.display = 'none';

                    console.log('QR Code scanned:', decodedText);

                    try {
                        const data = JSON.parse(decodedText);
                        console.log('Parsed QR data:', data);

                        if (data.plate) {
                            fetchVehicleDetails(data.plate);
                        } else if (data.plate_number) {
                            fetchVehicleDetails(data.plate_number);
                        } else if (data.vehicle_id) {
                            fetchVehicleDetails(data.vehicle_id);
                        } else {
                            showError('Invalid QR Code: No plate number found. Data: ' + JSON.stringify(data));
                        }
                    } catch (e) {
                        console.error('QR Parse error:', e);
                        fetchVehicleDetails(decodedText.trim());
                    }
                });
            }
        }

        function showError(message) {
            document.getElementById('result').innerHTML = `
                <div class="vehicle-result">
                    <div class="status-indicator expired">
                        ${message}
                    </div>
                    <button class="btn-scan btn-scan-start" onclick="location.reload()">Try Again</button>
                </div>
            `;
            document.getElementById('result').style.display = 'block';
        }

        function fetchVehicleDetails(plateNumber) {
            document.getElementById('result').innerHTML = `
                <div class="vehicle-result">
                    <div style="text-align: center; padding: 20px;">
                        <p>Loading vehicle details...</p>
                    </div>
                </div>
            `;
            document.getElementById('result').style.display = 'block';

            fetch('get_vehicle.php?plate=' + encodeURIComponent(plateNumber))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showError(data.error);
                    } else {
                        displayVehicleDetails(data);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showError('Error fetching vehicle details. Please try again.');
                });
        }

        function displayVehicleDetails(data) {
            let statusClass = 'valid';
            if (data.status === 'Expired') {
                statusClass = 'expired';
            } else if (data.status === 'Revoked') {
                statusClass = 'revoked';
            }

            let vehicleImageHtml = '';
            if (data.picture) {
                vehicleImageHtml = `<img src="../${data.picture}" alt="Vehicle" class="vehicle-image">`;
            }

            let ownerInfo = data.owner_name ? data.owner_name : 'N/A';
            let ownerType = data.owner_type ? ` (${data.owner_type})` : '';

            document.getElementById('result').innerHTML = `
                <div class="vehicle-result">
                    <div class="status-indicator ${statusClass}">
                        ${data.status}
                    </div>

                    ${vehicleImageHtml}

                    <div class="vehicle-info-row">
                        <div class="info-box">
                            <strong>Plate Number</strong>
                            <span>${data.plate_number}</span>
                        </div>
                        <div class="info-box">
                            <strong>Owner</strong>
                            <span>${ownerInfo}${ownerType}</span>
                        </div>
                        <div class="info-box">
                            <strong>Type</strong>
                            <span>${data.type}</span>
                        </div>
                        <div class="info-box">
                            <strong>Brand</strong>
                            <span>${data.brand}</span>
                        </div>
                        <div class="info-box">
                            <strong>Color</strong>
                            <span>${data.color}</span>
                        </div>
                        <div class="info-box">
                            <strong>Registered Under</strong>
                            <span>${data.registered_under}</span>
                        </div>
                        <div class="info-box">
                            <strong>Registered Date</strong>
                            <span>${formatDate(data.date_registered)}</span>
                        </div>
                        <div class="info-box">
                            <strong>Expiration Date</strong>
                            <span>${formatDate(data.date_expiration)}</span>
                        </div>
                    </div>

                    <button class="btn-scan btn-scan-start" style="margin-top: 25px; width: 100%;" onclick="location.reload()">Scan Another Vehicle</button>
                </div>
            `;
            document.getElementById('result').style.display = 'block';
        }

        document.getElementById('manualSearch').addEventListener('click', function() {
            const plateNumber = document.getElementById('manualPlate').value.trim();
            if (plateNumber) {
                fetchVehicleDetails(plateNumber);
            } else {
                alert('Please enter a plate number');
            }
        });

        document.getElementById('manualPlate').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('manualSearch').click();
            }
        });

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    </script>
</body>
</html>
