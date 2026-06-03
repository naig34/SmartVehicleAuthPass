<?php
/**
 * Fix Foreign Key Constraints
 * This script removes the problematic foreign key constraint that prevents
 * Teacher/Staff from registering vehicles, since owner_id can reference
 * either students OR teachers_staff table depending on owner_type.
 */

require_once 'db.php';

try {
    echo "Starting database fix...\n\n";

    // Check existing constraints
    echo "Checking existing foreign keys...\n";
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'smart_vehicle_db'
        AND TABLE_NAME = 'vehicles'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($constraints as $constraint) {
        echo "Found: {$constraint['CONSTRAINT_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}\n";
    }
    echo "\n";

    // Drop existing foreign key constraints on vehicles table
    echo "Removing problematic foreign key constraints...\n";

    $constraintsToRemove = ['vehicles_ibfk_1', 'vehicles_ibfk_2'];

    foreach ($constraintsToRemove as $constraintName) {
        try {
            $pdo->exec("ALTER TABLE vehicles DROP FOREIGN KEY `{$constraintName}`");
            echo "✓ Removed: {$constraintName}\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "check that column/key exists") !== false) {
                echo "- Skipped: {$constraintName} (doesn't exist)\n";
            } else {
                echo "- Warning for {$constraintName}: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n";
    echo "Foreign key constraints have been removed.\n";
    echo "The application will now handle owner_id validation in code.\n\n";

    // Verify the fix
    echo "Verifying vehicles table structure...\n";
    $stmt = $pdo->query("DESCRIBE vehicles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasOwnerId = false;
    $hasOwnerType = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'owner_id') {
            $hasOwnerId = true;
            echo "✓ owner_id column exists\n";
        }
        if ($column['Field'] === 'owner_type') {
            $hasOwnerType = true;
            echo "✓ owner_type column exists\n";
        }
    }

    if ($hasOwnerId && $hasOwnerType) {
        echo "\n✓ Database structure is correct!\n";
        echo "\nYou can now register vehicles for both Students and Teachers/Staff.\n";
    } else {
        echo "\n✗ Warning: Missing required columns\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
