<?php
require_once 'db.php';

/** @var PDO $pdo */
try {
    // Add profile_picture column to students table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'profile_picture'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        echo "Added profile_picture column to students table<br>";
    }

    // Add profile_picture column to teachers_staff table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM teachers_staff LIKE 'profile_picture'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE teachers_staff ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        echo "Added profile_picture column to teachers_staff table<br>";
    }

    // Add vehicle_image column to vehicles table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'vehicle_image'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN vehicle_image VARCHAR(255) DEFAULT NULL");
        echo "Added vehicle_image column to vehicles table<br>";
    }

    // Create uploads directories if they don't exist
    $dirs = [
        __DIR__ . '/../uploads/profile_pictures',
        __DIR__ . '/../uploads/qrcodes',
        __DIR__ . '/../uploads/vehicles'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: " . basename($dir) . "<br>";
        }
    }

    echo "<br><strong>Database update completed successfully!</strong><br>";
    echo "<a href='../index.php'>Return to Home</a>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
