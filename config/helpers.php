<?php
function generateQRCode($data, $filename) {
    $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/";
    $params = [
        'data' => $data,
        'size' => '300x300',
        'format' => 'png'
    ];

    $url = $qrApiUrl . '?' . http_build_query($params);

    // Suppress warnings in case of failure
    $qrContent = @file_get_contents($url);
    if ($qrContent === false) {
        return false;
    }

    $dir = __DIR__ . '/../uploads/qrcodes';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
    }

    $filepath = $dir . '/' . $filename;
    $written = @file_put_contents($filepath, $qrContent);
    if ($written === false) {
        return false;
    }

    // Return path relative to htdocs for <img>
    return 'uploads/qrcodes/' . $filename;
}

function calculateExpirationDate($vehicleType, $registrationDate) {
    $date = new DateTime($registrationDate);
    if ($vehicleType === 'Car') {
        $date->add(new DateInterval('P1Y'));
    } else {
        $date->add(new DateInterval('P6M'));
    }
    return $date->format('Y-m-d');
}

function updateVehicleStatus($pdo, $vehicleId) {
    $stmt = $pdo->prepare("SELECT date_expiration, status FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();

    if ($vehicle && $vehicle['status'] !== 'Revoked') {
        $today = new DateTime();
        $expDate = new DateTime($vehicle['date_expiration']);
        $status = ($today > $expDate) ? 'Expired' : 'Not Expired';

        $updateStmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
        $updateStmt->execute([$status, $vehicleId]);
        return $status;
    }
    return $vehicle['status'] ?? '';
}

function getStatusClass($status) {
    switch($status) {
        case 'Not Expired': return 'status-valid';
        case 'Expired': return 'status-expired';
        case 'Revoked': return 'status-revoked';
        default: return '';
    }
}
?>
