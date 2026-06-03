# Smart Vehicle Authentication Pass System Using QR Code

## Overview
A full-stack web application for managing and authenticating vehicles using QR codes in an educational institution setting. The system supports four user roles: Admin, Guard, Teacher/Staff, and Student.

## Technology Stack
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP 8.2
- **Database**: PostgreSQL
- **QR Generation**: QR Server API
- **QR Scanning**: html5-qrcode library

## Project Structure
```
├── admin/           # Admin portal (login, dashboard, vehicle management)
├── guard/           # Guard portal (login, QR scanning)
├── teacher/         # Teacher/Staff portal (login, dashboard)
├── student/         # Student portal (login, dashboard)
├── config/          # Database configuration and helpers
├── assets/          # CSS, JS, and images
├── uploads/         # Vehicle pictures and QR codes
└── index.php        # Home page
```

## Features

### Admin
- Single admin account management
- Register vehicles for Teachers/Staff and Students
- View, edit, and delete vehicles
- View QR codes
- Manage guards
- Automatic expiration tracking (Car: 1 year, Motorcycle: 6 months)

### Guard
- Sign up and login
- Scan vehicle QR codes
- View vehicle details with color-coded status:
  - Green: Not Expired
  - Red: Expired
  - Orange: Revoked

### Teacher/Staff & Student
- Login to personal dashboard
- View personal and vehicle details
- Check vehicle status and expiration
- Change password
- View vehicle QR code

## Getting Started

1. **Initialize Database**
   - Click "Initialize Database" on the home page
   - Or visit: `/config/init_db.php`

2. **Create Admin Account**
   - Go to Admin Login → Create Admin Account
   - Fill in the required details

3. **Register Guards**
   - Guards can sign up via the Guard Login page

4. **Register Vehicles**
   - Admin can register vehicles for Teachers/Staff and Students
   - System automatically generates QR codes and random secure passwords
   - The generated password will be displayed to admin after registration

## Security Features
- **Random Password Generation**: New Teachers/Staff and Students receive randomly generated 16-character passwords
- **Password Display**: Admin sees the generated password after vehicle registration to share with the user
- **Session-Based Security**: All endpoints require proper authentication
- **Guard Authentication**: QR scanning requires guard login to prevent unauthorized access
- **Generic Error Messages**: Database errors don't expose schema details

## Vehicle Expiration Rules
- **Car**: 1 year validity from registration date
- **Motorcycle**: 6 months validity from registration date
- **Revoked**: Requires admin intervention for renewal

## Status Color Coding
- **Green (Not Expired)**: Vehicle is valid
- **Red (Expired)**: Vehicle registration has expired
- **Orange (Revoked)**: Vehicle has been revoked by admin

## Recent Changes
- Initial project setup completed (Nov 8, 2025)
- Implemented all four user portals
- Added QR code generation and scanning
- Integrated PostgreSQL database
- Responsive Bootstrap 5 UI with role-specific color themes
- Security improvements: Random password generation, guard authentication, secure error handling

## Database Schema
- **admin**: Admin account
- **guards**: Security guard accounts
- **teachers_staff**: Teacher and staff members
- **students**: Student accounts
- **vehicles**: Vehicle registrations with QR codes

## User Preferences
- Simple, clean, and functional design
- Basic CRUD operations without complexity
- Color-coded status for easy identification
