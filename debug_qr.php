<?php
require_once 'config/db.php';

echo "<h2>Vehicle Data Debug</h2>";
echo "<p>Checking database for vehicles and their QR codes...</p>";

try {
    $stmt = $pdo->query("SELECT id, plate_number, owner_type, type, registered_under, qr_code_path FROM vehicles ORDER BY id DESC LIMIT 5");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Recent Vehicles in Database:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Plate Number</th><th>Owner Type</th><th>Type</th><th>Registered Under</th><th>QR Path</th><th>QR Data</th></tr>";

    foreach ($vehicles as $vehicle) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($vehicle['id']) . "</td>";
        echo "<td>" . htmlspecialchars($vehicle['plate_number']) . "</td>";
        echo "<td>" . htmlspecialchars($vehicle['owner_type']) . "</td>";
        echo "<td>" . htmlspecialchars($vehicle['type']) . "</td>";
        echo "<td>" . htmlspecialchars($vehicle['registered_under']) . "</td>";
        echo "<td>" . htmlspecialchars($vehicle['qr_code_path']) . "</td>";

        $qrPath = $vehicle['qr_code_path'];
        if ($qrPath && file_exists($qrPath)) {
            echo "<td><img src='" . htmlspecialchars($qrPath) . "' style='width: 100px; height: 100px;'><br>";
            echo "QR File exists</td>";
        } else {
            echo "<td>QR File not found</td>";
        }

        echo "</tr>";
    }

    echo "</table>";

    echo "<h3>Expected QR Code Format:</h3>";
    echo "<pre>";
    echo json_encode([
        'vehicle_id' => 'ABC123',
        'plate' => 'ABC123',
        'owner' => 'John Doe',
        'type' => 'Car'
    ], JSON_PRETTY_PRINT);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
