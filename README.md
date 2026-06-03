# Smart Vehicle Authentication Pass System

A full-featured vehicle authentication system with QR code generation and camera-based scanning.

## Quick Start

### Requirements
- XAMPP (Apache + MySQL)
- Modern web browser with camera support
- PHP 7.4+

### Installation (3 Steps)

1. **Setup Database**
   ```
   - Start XAMPP (Apache + MySQL)
   - Create database 'smart_vehicle_db' in phpMyAdmin
   - Visit: http://localhost/SmartVehicleAuthPass/config/init_db.php
   - Visit: http://localhost/SmartVehicleAuthPass/config/update_db_structure.php
   ```

2. **Configure Uploads Directory**
   ```
   Create these folders:
   - SmartVehicleAuthPass/uploads/qr_codes/
   - SmartVehicleAuthPass/uploads/vehicles/
   - SmartVehicleAuthPass/uploads/profiles/
   ```

3. **Access the System**
   ```
   Homepage: http://localhost/SmartVehicleAuthPass/
   ```

## User Access

### Admin
- **URL:** `/admin/login.php`
- **First time:** Click "Create Admin Account"
- **Features:** Manage users, register vehicles, generate QR codes

### Teacher
- **URL:** `/teacher/login.php`
- **Features:** View profile, personal QR code, vehicle status

### Student
- **URL:** `/student/login.php`
- **Features:** View profile, personal QR code, vehicle status

### Guard
- **URL:** `/guard/login.php` or `/guard/signup.php`
- **Features:** Scan QR codes, verify vehicles, view vehicle pictures

## Key Features

- Dark blue professional theme with CSS animations
- No Bootstrap - pure custom CSS
- Camera-based QR code scanning
- Automatic QR code generation
- Vehicle image upload and display
- Real-time status updates (Valid/Expired/Revoked)
- Animated transitions and hover effects
- Responsive design for desktop and tablet
- Session-based secure authentication

## Documentation

For detailed setup, usage, and troubleshooting, see [SYSTEM_GUIDE.md](SYSTEM_GUIDE.md)

## System Architecture

- **Backend:** PHP 7.4+ with MySQL
- **Frontend:** HTML5, CSS3 (Custom), JavaScript
- **QR Scanning:** html5-qrcode library
- **QR Generation:** QR Server API
- **No frameworks:** Pure PHP and vanilla JavaScript

## Screenshots

### Admin Dashboard
- Register vehicles with image upload
- Manage users and generate QR codes
- View comprehensive statistics

### Guard Dashboard
- Real-time camera QR scanning
- Instant vehicle status display
- Vehicle pictures and detailed info

### Teacher/Student Dashboard
- Personal QR code for identification
- Profile picture display
- Vehicle registration status

## Security

- Password hashing (PHP password_hash)
- SQL injection prevention (Prepared statements)
- XSS protection (htmlspecialchars)
- Session-based authentication
- File upload validation

## Support

For issues or questions, refer to the Troubleshooting section in SYSTEM_GUIDE.md

---

**Version:** 1.0.0
**Built with:** PHP, MySQL, HTML, CSS, JavaScript
