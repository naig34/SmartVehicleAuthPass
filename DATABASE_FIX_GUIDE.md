# Database Foreign Key Fix Guide

## The Problem

You're seeing this error when registering a vehicle:
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row:
a foreign key constraint fails (`smart_vehicle_db`.`vehicles`, CONSTRAINT `vehicles_ibfk_1`
FOREIGN KEY (`owner_id`) REFERENCES `students` (`id`) ON DELETE CASCADE)
```

### Why This Happens

Your database has a foreign key constraint that forces `vehicles.owner_id` to reference only the `students` table. However, your system needs to support:
- Students owning vehicles
- Teachers/Staff owning vehicles

The `owner_id` field can reference EITHER `students.id` OR `teachers_staff.id` depending on the `owner_type` field. This is called a "polymorphic relationship" and doesn't work well with strict foreign key constraints in MySQL.

## The Solution

Remove the strict foreign key constraint and let the application code handle the validation.

## How to Fix (Choose One Method)

### Method 1: Using phpMyAdmin

1. Open phpMyAdmin in your browser (usually `http://localhost/phpmyadmin`)
2. Select the `smart_vehicle_db` database from the left sidebar
3. Click on the `vehicles` table
4. Go to the "Structure" tab
5. Scroll down to "Relation view" or find the foreign keys section
6. Remove any foreign keys named `vehicles_ibfk_1` or `vehicles_ibfk_2`
7. Click "Save"

### Method 2: Using SQL File (Recommended)

1. Open phpMyAdmin
2. Select the `smart_vehicle_db` database
3. Click on the "SQL" tab at the top
4. Open the file `fix_foreign_keys.sql` (in the same folder as this guide)
5. Copy all the SQL commands from that file
6. Paste them into the SQL query box
7. Click "Go" to execute

**OR** if that gives errors:

1. Use `fix_foreign_keys_alternative.sql` instead
2. Follow the same steps
3. Ignore any "Can't DROP" errors - they just mean the constraint was already removed

### Method 3: Using MySQL Command Line

1. Open command prompt/terminal
2. Navigate to your project folder: `cd SmartVehicleAuthPass`
3. Run: `mysql -u root -p smart_vehicle_db < fix_foreign_keys.sql`
4. Press Enter (leave password blank if you didn't set one)

## How to Verify the Fix

After running the fix, try registering a vehicle again:

1. Go to Admin Dashboard
2. Click "Register Vehicle"
3. Fill out the form completely
4. Choose either Student or Teacher/Staff as owner
5. Click Register

If it works without the error, the fix was successful!

## What Changed

**Before:**
- Foreign key forced `owner_id` to only reference students
- Teachers/Staff couldn't register vehicles

**After:**
- No foreign key constraint on `owner_id`
- Application validates the owner exists based on `owner_type`
- Both Students and Teachers/Staff can register vehicles

## Still Having Issues?

If you still see the error after running the fix:

### Check 1: Verify Constraints Were Removed
```sql
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'smart_vehicle_db'
    AND TABLE_NAME = 'vehicles'
    AND REFERENCED_TABLE_NAME IS NOT NULL;
```

This should return 0 rows if the constraints were successfully removed.

### Check 2: Verify Owner Exists
Make sure the Student or Teacher/Staff you're registering the vehicle under actually exists in the database:

For Student:
```sql
SELECT * FROM students WHERE school_id = 'STUDENT_ID_HERE';
```

For Teacher/Staff:
```sql
SELECT * FROM teachers_staff WHERE employee_id = 'EMPLOYEE_ID_HERE';
```

### Check 3: Look at Application Logs
- Check if there are any PHP errors in your XAMPP error logs
- Enable error display by checking `error_reporting(E_ALL);` is set in register_vehicle.php

## Technical Details

The application code in `register_vehicle.php` handles the relationship correctly:

1. Creates the student/teacher record first
2. Gets the new ID using `lastInsertId()`
3. Stores that ID in `vehicles.owner_id`
4. Stores the type ('Student' or 'Teacher/Staff') in `vehicles.owner_type`

When fetching vehicles, the code uses:
```sql
LEFT JOIN teachers_staff ON owner_type = 'Teacher/Staff' AND owner_id = teachers_staff.id
LEFT JOIN students ON owner_type = 'Student' AND owner_id = students.id
```

This works perfectly without needing database-level foreign key constraints.

## Prevention

If you ever recreate the database or run migrations:
- Don't add foreign keys on `vehicles.owner_id`
- The polymorphic relationship must be handled at the application level
- This is a standard pattern for this type of relationship
