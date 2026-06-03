<?php
/**
 * Database Foreign Key Fix Tool
 * This page helps you fix the foreign key constraint issue
 */

$fixed = false;
$error = '';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_database'])) {
    require_once 'config/db.php';

    try {
        $messages[] = "Starting database fix...";

        // Check existing constraints
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'smart_vehicle_db'
            AND TABLE_NAME = 'vehicles'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($constraints)) {
            $messages[] = "No foreign key constraints found on vehicles table.";
            $messages[] = "The database is already fixed!";
            $fixed = true;
        } else {
            $messages[] = "Found " . count($constraints) . " foreign key constraint(s) to remove:";
            foreach ($constraints as $constraint) {
                $messages[] = "- {$constraint['CONSTRAINT_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}";
            }

            // Remove each constraint
            $constraintsToRemove = ['vehicles_ibfk_1', 'vehicles_ibfk_2'];
            $removedCount = 0;

            foreach ($constraintsToRemove as $constraintName) {
                try {
                    $pdo->exec("ALTER TABLE vehicles DROP FOREIGN KEY `{$constraintName}`");
                    $messages[] = "✓ Successfully removed: {$constraintName}";
                    $removedCount++;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), "check that column/key exists") !== false) {
                        $messages[] = "- Skipped: {$constraintName} (doesn't exist)";
                    } else {
                        $messages[] = "- Warning for {$constraintName}: " . $e->getMessage();
                    }
                }
            }

            if ($removedCount > 0) {
                $messages[] = "\n✓ Successfully removed {$removedCount} constraint(s)!";
                $fixed = true;
            } else {
                $messages[] = "\nNo constraints needed to be removed.";
                $fixed = true;
            }
        }

        // Verify the fix
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'smart_vehicle_db'
            AND TABLE_NAME = 'vehicles'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($remaining)) {
            $messages[] = "\n✓ Verification successful: No foreign key constraints remain on vehicles table.";
            $messages[] = "✓ You can now register vehicles for both Students and Teachers/Staff!";
        } else {
            $messages[] = "\n⚠ Warning: Some foreign key constraints still exist.";
            $messages[] = "You may need to remove them manually in phpMyAdmin.";
        }

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database - Smart Vehicle Auth System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
        }

        .error-box h3 {
            color: #991b1b;
            margin-bottom: 10px;
        }

        .error-box code {
            display: block;
            background: #7f1d1d;
            color: #fecaca;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
        }

        .info-box h3 {
            color: #1e40af;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #1e3a8a;
            line-height: 1.6;
        }

        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
        }

        .success-box h3 {
            color: #065f46;
            margin-bottom: 10px;
        }

        .messages {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            max-height: 400px;
            overflow-y: auto;
        }

        .messages pre {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #1f2937;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .steps {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .steps h3 {
            color: #1f2937;
            margin-bottom: 15px;
        }

        .steps ol {
            margin-left: 20px;
            line-height: 1.8;
            color: #4b5563;
        }

        .steps li {
            margin-bottom: 10px;
        }
    </style>
<script>
    // Apply dark mode before paint to prevent flash
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }
    // Listen for dark mode changes from OTHER pages/tabs
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
    <div class="container">
        <h1>Database Fix Tool</h1>
        <p class="subtitle">Fix the foreign key constraint issue preventing vehicle registration</p>

        <?php if (!isset($_POST['fix_database'])): ?>
            <div class="error-box">
                <h3>Problem Detected</h3>
                <p>You're seeing this error when trying to register a vehicle:</p>
                <code>SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails</code>
            </div>

            <div class="info-box">
                <h3>What's Wrong?</h3>
                <p>
                    Your database has a strict foreign key constraint that only allows Students to register vehicles.
                    This prevents Teachers/Staff from registering vehicles, even though the system is designed to support both.
                </p>
            </div>

            <div class="steps">
                <h3>What This Tool Will Do:</h3>
                <ol>
                    <li>Check for problematic foreign key constraints on the vehicles table</li>
                    <li>Remove the constraints that are causing the error</li>
                    <li>Verify the fix was successful</li>
                    <li>Allow both Students and Teachers/Staff to register vehicles</li>
                </ol>
            </div>

            <form method="POST">
                <input type="hidden" name="fix_database" value="1">
                <button type="submit" class="btn btn-primary">Fix Database Now</button>
            </form>

        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-box">
                    <h3>Error</h3>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php elseif ($fixed): ?>
                <div class="success-box">
                    <h3>Database Fixed Successfully!</h3>
                    <p>The foreign key constraints have been removed. You can now register vehicles for both Students and Teachers/Staff.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($messages)): ?>
                <div class="messages">
                    <pre><?= htmlspecialchars(implode("\n", $messages)) ?></pre>
                </div>
            <?php endif; ?>

            <div class="button-group">
                <?php if ($fixed): ?>
                    <a href="admin/register_vehicle.php" class="btn btn-success">Register a Vehicle</a>
                    <a href="admin/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                <?php else: ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="fix_database" value="1">
                        <button type="submit" class="btn btn-primary">Try Again</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="info-box" style="margin-top: 30px;">
            <h3>Need More Help?</h3>
            <p>
                If this tool doesn't work, check the <strong>DATABASE_FIX_GUIDE.md</strong> file for manual instructions
                on how to fix this issue using phpMyAdmin or SQL commands.
            </p>
        </div>
    </div>
</body>
</html>
