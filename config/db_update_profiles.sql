-- Database Update for Profile Pictures and QR Codes
-- Run this in phpMyAdmin or MySQL command line

USE smart_vehicle_db;

-- Add profile_picture column to teachers_staff table
ALTER TABLE teachers_staff
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) DEFAULT NULL;

-- Add profile_picture column to students table
ALTER TABLE students
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) DEFAULT NULL;

-- Verify the columns were added
SHOW COLUMNS FROM teachers_staff;
SHOW COLUMNS FROM students;
