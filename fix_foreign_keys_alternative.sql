-- Alternative Fix for Foreign Key Constraints
-- Use this if the main fix_foreign_keys.sql gives errors about IF EXISTS

USE smart_vehicle_db;

-- Method 1: Try to remove vehicles_ibfk_1
-- If this fails with "check that column/key exists", that's fine - it means it's already removed
ALTER TABLE vehicles DROP FOREIGN KEY vehicles_ibfk_1;

-- Method 2: Try to remove vehicles_ibfk_2
-- If this fails with "check that column/key exists", that's fine - it means it's already removed
ALTER TABLE vehicles DROP FOREIGN KEY vehicles_ibfk_2;

-- Note: You may see errors like "Error Code: 1091. Can't DROP 'vehicles_ibfk_X'; check that column/key exists"
-- This is normal if the constraint was already removed or never existed.
-- As long as the constraint is gone, the vehicle registration will work.
