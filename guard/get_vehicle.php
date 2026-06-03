<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../config/db.php';
require_once '../config/helpers.php';

/** @var PDO $pdo */
$plateNumber = $_GET['plate'] ?? '';

$stmt = $pdo->prepare("SELECT v.*, 
    CASE 
        WHEN v.owner_type = 'Teacher/Staff' THEN t.name
        ELSE s.name
    END as owner_name
    FROM vehicles v
    LEFT JOIN teachers_staff t ON v.owner_type = 'Teacher/Staff' AND v.owner_id = t.id
    LEFT JOIN students s ON v.owner_type = 'Student' AND v.owner_id = s.id
    WHERE v.plate_number = ?");
$stmt->execute([$plateNumber]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if ($vehicle) {
    $vehicle['status'] = updateVehicleStatus($pdo, $vehicle['id']);
    echo json_encode($vehicle);
} else {
    echo json_encode(['error' => 'Vehicle not found']);
}
?>
