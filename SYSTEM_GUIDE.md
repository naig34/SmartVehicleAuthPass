# Smart Vehicle Authentication Pass System - Complete Guide

## System Overview

A comprehensive web-based vehicle authentication system built with PHP, MySQL, HTML, CSS, and JavaScript. Features include QR code generation, camera-based scanning, and role-based dashboards for Admin, Teacher, Student, and Guard users.

## Features

### Dark Blue Theme
- Professional dark blue color scheme across all dashboards
- Smooth CSS animations for transitions, hover effects, and interactions
- Responsive design optimized for desktop and tablet devices
- No Bootstrap dependencies - pure custom CSS

### Admin Dashboard
- Manage users (Teachers, Students, Guards)
- Register and manage vehicles with image uploads
- View, add, edit, and delete records
- Generate QR codes for vehicles automatically
- Comprehensive vehicle statistics and reporting

### Teacher & Student Dashboards
- Display profile picture with animated container
- Show personal QR code for identification
- View registered vehicles with status indicators
- Profile information display with animated info cards
- Vehicle pass expiration tracking

### Guard Dashboard
- Real-time camera-based QR code scanning
- Instant vehicle status display (Valid/Expired/Revoked)
- Show vehicle picture and detailed information
- Animated success/failure feedback
- Easy-to-use scanner interface

## Technical Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP/WAMP/MAMP (recommended for local development)
- Web server (Apache/Nginx)

### Browser Requirements
- Modern browser with camera access support
- JavaScript enabled
- Recommended: Chrome, Firefox, Safari, Edge (latest versions)

## Installation Instructions

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP with Apache and MySQL components
3. Start Apache and MySQL from the XAMPP Control Panel

### Step 2: Set Up Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `smart_vehicle_db`
3. Run the initialization script:
   - Navigate to `SmartVehicleAuthPass/config/init_db.php` in your browser
   - This creates all necessary tables
4. Update database schema for profile pictures:
   - Navigate to `SmartVehicleAuthPass/config/update_db_structure.php`
   - This adds profile_picture and qr_code_path columns

### Step 3: Configure Database Connection
1. Open `SmartVehicleAuthPass/config/db.php`
2. Verify the following settings:
   ```php
   $host = "127.0.0.1";
   $dbname = "smart_vehicle_db";
   $username = "root";
   $password = ""; // Leave blank for default XAMPP
   ```

### Step 4: Set Up File Permissions
Create the following directories and ensure they're writable:
- `SmartVehicleAuthPass/uploads/`
- `SmartVehicleAuthPass/uploads/qr_codes/`
- `SmartVehicleAuthPass/uploads/vehicles/`
- `SmartVehicleAuthPass/uploads/profiles/`

### Step 5: Access the System
1. Copy the SmartVehicleAuthPass folder to your XAMPP htdocs directory
2. Open your browser and navigate to:
   - `http://localhost/SmartVehicleAuthPass/`

## User Roles and Access

### 1. Admin
**Access:** http://localhost/SmartVehicleAuthPass/admin/login.php

**First Time Setup:**
- Click "Create Admin Account" on the login page
- Fill in your details to create the first admin account
- Login with your credentials

**Capabilities:**
- Register new vehicles with QR codes
- Manage teachers, students, and guards
- Edit and delete vehicle records
- View comprehensive statistics
- Generate and print QR codes

### 2. Teacher/Staff
**Access:** http://localhost/SmartVehicleAuthPass/teacher/login.php

**Features:**
- View personal profile with picture
- Access personal QR code for identification
- View registered vehicles
- Check vehicle pass status and expiration dates

**Login Creation:**
- Teachers must be registered by the admin through the vehicle registration form
- Use employee ID and password to login

### 3. Student
**Access:** http://localhost/SmartVehicleAuthPass/student/login.php

**Features:**
- View personal profile with picture
- Access personal QR code for identification
- View registered vehicles
- Check vehicle pass status and expiration dates

**Login Creation:**
- Students must be registered by the admin through the vehicle registration form
- Use school ID and password to login

### 4. Guard
**Access:** http://localhost/SmartVehicleAuthPass/guard/login.php

**Signup:** http://localhost/SmartVehicleAuthPass/guard/signup.php

**Features:**
- Camera-based QR code scanning
- Instant vehicle verification
- View vehicle pictures and details
- Real-time status indicators with animations
- Scan history tracking

## Using the System

### For Administrators

#### Registering a Vehicle:
1. Login to admin dashboard
2. Click "Register New Vehicle"
3. Fill in all required information:
   - Owner type (Teacher/Student)
   - Owner details (will create user account if new)
   - Vehicle details (type, brand, color, plate number)
   - Upload vehicle picture
   - Set registration and expiration dates
4. System automatically generates QR code
5. QR code can be downloaded and printed

#### Managing Vehicles:
- View all vehicles in the dashboard table
- Click "Edit" to modify vehicle details
- Click "Delete" to remove a vehicle
- Filter by status, owner type, or expiration date

### For Teachers and Students

#### Accessing Your Dashboard:
1. Login with your credentials (provided during vehicle registration)
2. View your profile picture and QR code
3. Check your registered vehicles and their status
4. Download your QR code if needed

#### Profile Picture Upload:
- Contact admin to upload/update your profile picture
- Default placeholder image shown if no picture uploaded

### For Guards

#### Scanning Vehicles:
1. Login to guard dashboard
2. Click "Start Scanner" button
3. Allow camera permissions when prompted
4. Point camera at vehicle's QR code
5. System automatically:
   - Reads QR code
   - Fetches vehicle details from database
   - Displays vehicle picture, status, and information
   - Shows animated success (green) or expired (red) indicator
6. Click "Scan Another Vehicle" to continue

#### Understanding Status Indicators:
- **Green (Valid):** Vehicle pass is active and valid
- **Red (Expired):** Vehicle pass has expired
- **Orange (Revoked):** Vehicle pass has been revoked by admin

## CSS Animations

The system includes comprehensive CSS animations:

### Page Load Animations:
- Fade-in effects for content
- Slide-in effects for sidebars and cards
- Scale-in effects for profile pictures and stats

### Interactive Animations:
- Hover effects on cards and buttons
- Ripple effect on button clicks
- Smooth color transitions
- Transform effects on hover

### Status Animations:
- Pulsing effect for valid status badges
- Error pulse for expired statuses
- Loading spinner for data fetching
- Success/error shake animations

### Scanner Animations:
- Animated camera frame border
- Scanning line effect
- Success/failure feedback animations
- Smooth result display transitions

## Database Structure

### Tables:
- `admin` - Administrator accounts
- `guards` - Guard accounts
- `teachers_staff` - Teacher/Staff profiles with QR codes
- `students` - Student profiles with QR codes
- `vehicles` - Vehicle registrations with QR codes

### Key Fields:
- `profile_picture` - Path to user profile images
- `qr_code_path` - Path to generated QR codes
- `picture` - Path to vehicle images
- `status` - Vehicle pass status (Not Expired/Expired/Revoked)

## QR Code System

### QR Code Generation:
- Automatically generated when vehicle is registered
- Contains: Vehicle plate number, owner info, registration details
- Stored as PNG images in `/uploads/qr_codes/`
- Uses external API: https://api.qrserver.com/v1/create-qr-code/

### QR Code Scanning:
- Uses html5-qrcode library
- Works with device camera (front/back)
- Reads QR code data and validates format
- Fetches real-time vehicle information from database

## Troubleshooting

### Common Issues:

**Database Connection Failed:**
- Check XAMPP MySQL is running
- Verify database name is `smart_vehicle_db`
- Check credentials in config/db.php

**Camera Not Working:**
- Ensure browser has camera permissions
- Use HTTPS or localhost (required for camera access)
- Try different browser if issues persist

**QR Code Not Generating:**
- Check internet connection (uses external API)
- Verify uploads/qr_codes directory exists and is writable
- Check PHP file_get_contents is enabled

**Images Not Displaying:**
- Verify uploads directory permissions (755 or 777)
- Check file paths in database are correct
- Ensure uploaded images are in correct directory

**Profile Pictures Not Showing:**
- Run update_db_structure.php to add profile_picture columns
- Check uploads/profiles directory exists
- Verify image file extensions are supported (jpg, jpeg, png, gif)

## Security Considerations

- All user inputs are sanitized using htmlspecialchars()
- Prepared statements used for all database queries
- Session-based authentication for all dashboards
- Password hashing using PHP password_hash()
- File upload validation for images
- Guard signup requires employee ID

## Browser Compatibility

**Fully Supported:**
- Google Chrome 90+
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+

**Camera Features Require:**
- HTTPS connection (or localhost)
- Camera permissions granted
- Modern browser with WebRTC support

## Maintenance

### Regular Tasks:
- Check vehicle expiration dates (updated automatically)
- Backup database regularly
- Clean up old QR codes if needed
- Monitor uploads directory size

### Database Maintenance:
```sql
-- Update expired vehicles
UPDATE vehicles
SET status = 'Expired'
WHERE date_expiration < CURDATE()
AND status = 'Not Expired';
```

## Support and Credits

**System:** Smart Vehicle Authentication Pass System
**Version:** 1.0.0
**Built with:** PHP, MySQL, HTML, CSS, JavaScript
**QR Library:** html5-qrcode (unpkg.com/html5-qrcode)
**QR API:** QR Server API (api.qrserver.com)

## Future Enhancements

Potential features for future versions:
- Email notifications for expiring passes
- SMS alerts for guards
- Mobile app version
- Biometric authentication
- Advanced reporting and analytics
- Multi-language support
- Offline mode with sync
