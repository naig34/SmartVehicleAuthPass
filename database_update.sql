-- Database Update SQL File
-- Add vehicle_image column to vehicles table
-- Run this in phpMyAdmin or MySQL command line

USE smart_vehicle_db;

-- Add vehicle_image column if it doesn't exist
ALTER TABLE vehicles
ADD COLUMN IF NOT EXISTS vehicle_image VARCHAR(255) DEFAULT NULL;

-- Verify the column was added
SHOW COLUMNS FROM vehicles LIKE 'vehicle_image';
