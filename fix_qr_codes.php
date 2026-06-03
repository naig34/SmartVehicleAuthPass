<?php
require_once 'config/db.php';
require_once 'config/helpers.php';

echo "<h2>QR Code Regeneration Script</h2>";
echo "<p>This will regenerate all QR codes with the correct format...</p><br>";

$stmt = $pdo->query("SELECT * FROM vehicles");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
$errors = 0;

foreach ($vehicles as $vehicle) {
    try {
        $plateNumber = $vehicle['plate_number'];

        $qrData = json_encode([
            'vehicle_id' => $plateNumber,
            'plate' => $plateNumber,
            'owner' => $vehicle['registered_under'],
            'type' => $vehicle['type']
        ]);

        $qrFilename = $plateNumber . '_' . time() . '_fixed.png';
        $qrPath = generateQRCode($qrData, $qrFilename);

        if ($qrPath) {
            if ($vehicle['qr_code_path'] && file_exists($vehicle['qr_code_path'])) {
                unlink($vehicle['qr_code_path']);
            }

            $updateStmt = $pdo->prepare("UPDATE vehicles SET qr_code_path = ? WHERE id = ?");
            $updateStmt->execute([$qrPath, $vehicle['id']]);

            echo "✓ Fixed QR code for vehicle: <strong>$plateNumber</strong><br>";
            $fixed++;
        } else {
            echo "✗ Failed to generate QR code for: <strong>$plateNumber</strong><br>";
            $errors++;
        }

    } catch (Exception $e) {
        echo "✗ Error processing vehicle {$vehicle['plate_number']}: " . $e->getMessage() . "<br>";
        $errors++;
    }
}

echo "<br><h3>Summary</h3>";
echo "<p>Fixed: <strong style='color: green;'>$fixed</strong> QR codes</p>";
echo "<p>Errors: <strong style='color: red;'>$errors</strong></p>";

if ($fixed > 0) {
    echo "<br><p style='color: green; font-weight: bold;'>✓ All QR codes have been regenerated successfully!</p>";
    echo "<p>You can now scan the updated QR codes. Go back to the admin dashboard to view them.</p>";
}
?>
