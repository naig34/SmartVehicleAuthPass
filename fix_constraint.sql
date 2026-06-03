-- Simple Database Fix
-- This removes ONLY the constraint causing the registration error

USE smart_vehicle_db;

-- Remove the constraint preventing Teacher/Staff vehicle registration
ALTER TABLE vehicles DROP FOREIGN KEY vehicles_ibfk_1;

-- Show confirmation
SELECT 'Database fixed! You can now register vehicles for both Students and Teachers/Staff' AS Status;
