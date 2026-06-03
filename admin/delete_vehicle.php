<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

/** @var PDO $pdo */
$vehicleId = intval($_GET['id'] ?? 0);

if (!$vehicleId) {
    header('Location: dashboard.php');
    exit;
}

try {
    // ── 1. Fetch the vehicle so we know the owner type and ID ──────────────
    $stmt = $pdo->prepare("SELECT owner_type, owner_id FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        // Vehicle not found – just go back
        header('Location: dashboard.php?error=not_found');
        exit;
    }

    $ownerType = $vehicle['owner_type'];
    $ownerId   = $vehicle['owner_id'];

    // ── 2. Delete the vehicle first (removes the FK reference) ────────────
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);

    // ── 3. Delete the owner account ───────────────────────────────────────
    //  Only delete if that owner has NO other vehicles still registered.
    //  This protects owners who have more than one vehicle in the system.
    if ($ownerType === 'Student') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM vehicles
             WHERE owner_type = 'Student' AND owner_id = ?"
        );
        $stmt->execute([$ownerId]);
        $remaining = (int) $stmt->fetchColumn();

        if ($remaining === 0) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$ownerId]);
        }

    } elseif ($ownerType === 'Teacher/Staff') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM vehicles
             WHERE owner_type = 'Teacher/Staff' AND owner_id = ?"
        );
        $stmt->execute([$ownerId]);
        $remaining = (int) $stmt->fetchColumn();

        if ($remaining === 0) {
            $stmt = $pdo->prepare("DELETE FROM teachers_staff WHERE id = ?");
            $stmt->execute([$ownerId]);
        }
    }

    header('Location: dashboard.php?deleted=1');
    exit;

} catch (Exception $e) {
    // On any DB error, redirect with an error flag
    header('Location: dashboard.php?error=delete_failed');
    exit;
}
?>
