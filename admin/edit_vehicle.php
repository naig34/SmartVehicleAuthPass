<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

/** @var PDO $pdo */
$vehicleId = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
    $stmt->execute([$status, $vehicleId]);
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
    <style>
        /* === DARK MODE for edit_vehicle === */
        html.dark-mode body, body.dark-mode { background: #0f172a !important; color: #e2e8f0 !important; }
        html.dark-mode .card, body.dark-mode .card {
            background: #1e293b !important; border-color: #334155 !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4) !important;
        }
        html.dark-mode .card-body, body.dark-mode .card-body { background: #1e293b !important; }
        html.dark-mode .form-label, body.dark-mode .form-label { color: #cbd5e1 !important; }
        html.dark-mode .form-control, body.dark-mode .form-control,
        html.dark-mode .form-select, body.dark-mode .form-select {
            background: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important;
        }
        html.dark-mode .form-control:focus, body.dark-mode .form-control:focus,
        html.dark-mode .form-select:focus, body.dark-mode .form-select:focus {
            background: #1e293b !important; border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.25) !important; color: #e2e8f0 !important;
        }
        html.dark-mode .form-control:disabled, body.dark-mode .form-control:disabled {
            background: #334155 !important; color: #94a3b8 !important;
        }
        html.dark-mode select option, body.dark-mode select option {
            background: #1e293b !important; color: #e2e8f0 !important;
        }
        html.dark-mode .btn-secondary, body.dark-mode .btn-secondary {
            background: #334155 !important; border-color: #475569 !important; color: #cbd5e1 !important;
        }
        html.dark-mode #darkModeToggle, body.dark-mode #darkModeToggle {
            border-color: #334155 !important; color: #94a3b8 !important; background: #1e293b !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header admin-theme text-white">
                <h4 class="mb-0">Edit Vehicle Status</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Plate Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($vehicle['plate_number']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Not Expired" <?= $vehicle['status'] == 'Not Expired' ? 'selected' : '' ?>>Not Expired</option>
                            <option value="Expired" <?= $vehicle['status'] == 'Expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
