# Smart Vehicle Authentication Pass System - Setup Instructions

## System Overview

This is a modernized Vehicle Authentication System with enhanced UI/UX, immediate QR scanning feedback, and profile picture support for all user types.

## New Features

### Enhanced UI/UX
- Modern, responsive design with gradient themes
- Smooth animations and transitions
- Profile picture display (with fallback to initial letters)
- Statistics dashboard for admin
- Improved QR code display and download functionality

### Guard Dashboard Improvements
- Real-time scanning indicator
- Immediate visual feedback with color-coded status badges
- Large, animated status display (Valid/Expired/Revoked)
- Detailed vehicle information in card layout
- Stop/Start scanner controls
- Better error handling

### Student/Teacher Dashboard Improvements
- Profile picture display
- Better organized information cards
- Enhanced QR code display with download button
- Responsive 3-column layout
- Themed card headers matching user role

### Admin Dashboard Improvements
- Statistics cards showing:
  - Total vehicles
  - Valid vehicles
  - Total users (students + teachers)
  - Security guards count
- Modern gradient cards
- Enhanced navigation menu with icons
- Better vehicle table with improved styling

## Installation Steps

### 1. Database Setup

First, ensure your MySQL/XAMPP is running. Then:

1. Access the system via your browser: `http://localhost/SmartVehicleAuthPass/`

2. Click "Initialize Database" on the home page, OR visit:
   ```
   http://localhost/SmartVehicleAuthPass/config/init_db.php
   ```

3. Update the database to add profile picture support:
   ```
   http://localhost/SmartVehicleAuthPass/config/db_update.php
   ```

### 2. Database Configuration

Update `config/db.php` with your database credentials if needed:

```php
$host = "127.0.0.1";
$dbname = "smart_vehicle_db";
$username = "root";
$password = "";
```

### 3. Create Required Directories

The system will automatically create these directories, but you can also manually create them:

```
SmartVehicleAuthPass/
├── uploads/
│   ├── profile_pictures/
│   ├── qrcodes/
│   └── vehicle_pictures/
```

Ensure these directories have write permissions (755 or 777).

### 4. First-Time Setup

1. Create an admin account:
   - Go to: `http://localhost/SmartVehicleAuthPass/admin/login.php`
   - Click "Create Admin Account"
   - Fill in the required details

2. Register security guards:
   - Guards can self-register at: `http://localhost/SmartVehicleAuthPass/guard/signup.php`

3. Register vehicles with student/teacher information:
   - Login as admin
   - Click "Register Vehicle"
   - Fill in owner and vehicle details
   - QR code will be automatically generated

## Using the System

### Admin Portal
- **URL**: `http://localhost/SmartVehicleAuthPass/admin/login.php`
- **Features**:
  - View dashboard with statistics
  - Register new vehicles
  - Manage security guards
  - View/Edit/Delete vehicles
  - Download QR codes
  - Change admin account

### Guard Portal
- **URL**: `http://localhost/SmartVehicleAuthPass/guard/login.php`
- **Features**:
  - Scan vehicle QR codes using camera
  - View immediate status feedback:
    - GREEN badge = Valid (Not Expired)
    - RED badge = Expired
    - ORANGE badge = Revoked
  - View complete vehicle details
  - Stop/Start scanner controls

### Teacher/Staff Portal
- **URL**: `http://localhost/SmartVehicleAuthPass/teacher/login.php`
- **Features**:
  - View profile with picture
  - View personal information
  - View vehicle details and status
  - Download vehicle QR code
  - Change password

### Student Portal
- **URL**: `http://localhost/SmartVehicleAuthPass/student/login.php`
- **Features**:
  - View profile with picture
  - View personal information
  - View vehicle details and status
  - Download vehicle QR code
  - Change password

## Color Themes

Each user role has a distinct color theme:

- **Admin**: Purple gradient (#667eea → #764ba2)
- **Guard**: Gray gradient (#6B7280 → #4B5563)
- **Teacher/Staff**: Green gradient (#10b981 → #059669)
- **Student**: Orange gradient (#f59e0b → #d97706)

## QR Scanning Instructions

### For Guards:

1. Login to the Guard portal
2. Click "Start Scanner"
3. Allow camera permissions when prompted
4. Point camera at vehicle QR code sticker
5. System will automatically:
   - Stop the scanner
   - Fetch vehicle details
   - Display large status badge with animation
   - Show complete vehicle information

### Status Indicators:

- **NOT EXPIRED** (Green): Vehicle registration is valid
- **EXPIRED** (Red): Vehicle registration has expired
- **REVOKED** (Orange): Vehicle access has been revoked by admin

## Vehicle Expiration Rules

- **Cars**: 1 year validity from registration date
- **Motorcycles**: 6 months validity from registration date
- System automatically checks and updates status

## Troubleshooting

### Camera Not Working
- Ensure browser has camera permissions
- Use HTTPS or localhost (required for camera access)
- Try a different browser (Chrome recommended)

### QR Code Not Generating
- Check internet connection (uses QR Server API)
- Ensure `uploads/qrcodes/` directory exists with write permissions
- Check PHP error logs

### Profile Pictures Not Showing
- Ensure `uploads/profile_pictures/` directory exists
- Check directory permissions (755 or 777)
- Verify database has `profile_picture` column

### Database Errors
- Run `config/db_update.php` to add missing columns
- Check MySQL service is running
- Verify database credentials in `config/db.php`

## Security Notes

- All passwords are hashed using PHP's `password_hash()`
- QR scanning requires guard authentication
- Session-based authentication for all portals
- Input sanitization with `htmlspecialchars()`
- Prepared statements to prevent SQL injection

## Browser Compatibility

- Chrome/Edge: Fully supported
- Firefox: Fully supported
- Safari: Supported (iOS 11+)
- Mobile browsers: Fully supported with responsive design

## Technical Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP 8.2+
- **Database**: MySQL/MariaDB
- **QR Generation**: QR Server API
- **QR Scanning**: html5-qrcode library
- **Animations**: CSS3 transitions and keyframes

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review PHP error logs
3. Check browser console for JavaScript errors
4. Verify all directories have proper permissions

## Future Enhancements

Potential features for future updates:
- Profile picture upload functionality
- Email notifications for expiring vehicles
- Export vehicle data to Excel/PDF
- Mobile app for guards
- SMS alerts for revoked vehicles
- Renewal workflow for expired vehicles
