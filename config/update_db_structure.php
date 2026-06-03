<?php
require_once 'db.php';

try {
    // Add profile_picture and qr_code_path columns to teachers_staff
    $pdo->exec("ALTER TABLE teachers_staff
                ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE teachers_staff
                ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) DEFAULT NULL");

    // Add profile_picture and qr_code_path columns to students
    $pdo->exec("ALTER TABLE students
                ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE students
                ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) DEFAULT NULL");

    echo "Database structure updated successfully!<br>";
    echo "Added profile_picture and qr_code_path columns to teachers_staff and students tables.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
