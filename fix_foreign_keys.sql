-- Fix Foreign Key Constraints
-- This removes the problematic foreign key that prevents Teacher/Staff vehicle registration
-- since owner_id can reference either students OR teachers_staff table depending on owner_type

USE smart_vehicle_db;

-- Show existing foreign keys before removal
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'smart_vehicle_db'
    AND TABLE_NAME = 'vehicles'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Remove foreign key constraints that cause the error
-- These constraints are too strict for our use case where owner_id
-- can reference different tables based on owner_type

ALTER TABLE vehicles DROP FOREIGN KEY IF EXISTS vehicles_ibfk_1;
ALTER TABLE vehicles DROP FOREIGN KEY IF EXISTS vehicles_ibfk_2;

-- Note: The application handles owner_id validation in code based on owner_type
-- This is the correct approach for polymorphic relationships

-- Verify the fix
DESCRIBE vehicles;

-- Show success message
SELECT 'Foreign key constraints removed successfully!' AS Status;
SELECT 'You can now register vehicles for both Students and Teachers/Staff' AS Info;
