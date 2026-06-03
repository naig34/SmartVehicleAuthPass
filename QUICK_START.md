# Quick Start Guide

## Step-by-Step Setup (5 Minutes)

### Step 1: Update Database (REQUIRED)
Visit this URL to add profile picture support:
```
http://localhost/SmartVehicleAuthPass/config/db_update.php
```
You should see: "Database update completed successfully!"

### Step 2: Create Admin Account
1. Go to: `http://localhost/SmartVehicleAuthPass/admin/login.php`
2. Click "Create Admin Account"
3. Enter:
   - Name: Your name
   - Username: admin
   - Password: (choose a secure password)
4. Click "Create Account"

### Step 3: Test the System

#### Test Admin Dashboard
1. Login with your admin credentials
2. You should see:
   - 4 colorful statistics cards at the top
   - Quick Actions menu on the left
   - Empty vehicle table (no vehicles yet)

#### Register a Test Guard
1. Open in new tab: `http://localhost/SmartVehicleAuthPass/guard/signup.php`
2. Fill in:
   - Name: Guard One
   - Employee ID: G001
   - Sex: Male/Female
   - Password: guard123
3. Click "Sign Up"

#### Register a Test Vehicle
1. Back in Admin dashboard, click "Register Vehicle"
2. Select Owner Type: "Student"
3. Fill in Student details:
   - Name: John Doe
   - School ID: 2024001
   - Email: john@school.edu
   - Year & Section: 4-A
   - Sex: Male
   - Course: Computer Science
   - Birthdate: 2000-01-01
   - Age: 24
4. Fill in Vehicle details:
   - Type: Car
   - Registered Under: John Doe
   - Color: Blue
   - Brand: Toyota
   - Plate Number: ABC123
5. Click "Register Vehicle"
6. Note the generated password shown (to give to student)

#### Test Guard Scanning
1. Login to Guard portal: `http://localhost/SmartVehicleAuthPass/guard/login.php`
   - Employee ID: G001
   - Password: guard123
2. Click "Start Scanner"
3. Allow camera access
4. Point camera at the vehicle QR code (download it from admin dashboard first)
5. Watch the immediate feedback:
   - Scanner stops automatically
   - Large GREEN badge appears
   - Vehicle details displayed in cards

#### Test Student Portal
1. Login: `http://localhost/SmartVehicleAuthPass/student/login.php`
   - Email: john@school.edu
   - Password: (use the generated password from registration)
2. You should see:
   - Profile avatar with "J" initial
   - Personal information in middle column
   - Vehicle details and QR code on right
3. Click "Download QR Code" to save the QR sticker

---

## What You'll See

### Admin Dashboard
- **Statistics Cards**: Purple, Green, Orange, and Gray cards showing counts
- **Quick Actions**: Menu with icons for Register, Manage, Settings
- **Vehicle Table**: Scrollable table with all registered vehicles
- **Action Buttons**: QR, Edit, Delete for each vehicle

### Guard Dashboard
- **Camera Scanner**: Large bordered frame for scanning
- **Scanning Indicator**: Animated loading when actively scanning
- **Status Badge**: HUGE animated badge (GREEN/RED/ORANGE)
- **Vehicle Info Grid**: 6 information cards with vehicle details
- **Scan Another Button**: Quick reload to scan next vehicle

### Student/Teacher Dashboard
- **Profile Card**: Picture or gradient avatar with initials
- **3 Columns**: Profile, Personal Info, Vehicle Details
- **QR Code Card**: Downloadable QR code for printing
- **Status Badge**: Color-coded vehicle status
- **Change Password**: Simple form at bottom of profile column

---

## Common Tasks

### Download a Vehicle QR Code
**From Admin:**
1. Admin Dashboard → Click "QR" button next to vehicle
2. Right-click QR image → Save As

**From Student/Teacher:**
1. Login to your portal
2. Scroll to "Vehicle QR Code" card
3. Click "Download QR Code" button

### Check Vehicle Status
**Admin:** See status column in vehicle table (color-coded)
**Guard:** Scan QR code to see large status badge
**Student/Teacher:** See status in vehicle details card

### Change Password
**Admin:** Dashboard → Admin Settings
**Guard:** Not implemented (guards use signup page)
**Student/Teacher:** Dashboard → "Change Password" card

### Edit Vehicle Status
1. Admin Dashboard → Click "Edit" next to vehicle
2. Change status dropdown (Not Expired/Expired/Revoked)
3. Click "Update"

---

## Keyboard Shortcuts & Tips

### For Guards
- Allow camera permissions when prompted
- Use in good lighting for better scanning
- Hold phone/tablet steady over QR code
- QR code should fill most of the scanner frame
- Click "Stop Scanner" to conserve battery when not in use

### For Admin
- Use "QR" button for quick QR code access
- Status colors: Green=Valid, Red=Expired, Orange=Revoked
- Statistics update in real-time
- Table is sortable by clicking column headers (if implemented)

### For Students/Teachers
- Initial letter shows if no profile picture uploaded
- QR code can be printed as sticker for vehicle
- Download QR code before vehicle registration expires
- Change password regularly for security

---

## Troubleshooting Quick Fixes

### "Camera Not Working"
- **Fix**: Use Chrome browser and allow camera permissions
- **Alternative**: Try Firefox or Edge
- **Mobile**: Ensure app has camera permission in phone settings

### "QR Code Not Scanning"
- **Fix 1**: Clean camera lens
- **Fix 2**: Increase lighting
- **Fix 3**: Hold QR code flat (no creases)
- **Fix 4**: Reload page and try again

### "Profile Picture Not Showing"
- **Fix**: Check that `uploads/profile_pictures/` folder exists
- **Fallback**: System shows gradient avatar with initial letter
- **Note**: Profile upload feature can be added later

### "Statistics Not Showing"
- **Fix**: Ensure you ran `db_update.php`
- **Check**: Database connection is working
- **Refresh**: Try reloading the admin dashboard

---

## Next Steps

1. **Register Real Users**: Add actual students and teachers with vehicles
2. **Print QR Codes**: Download and print QR stickers for vehicles
3. **Train Guards**: Show guards how to use the scanning interface
4. **Distribute Credentials**: Give users their login credentials securely
5. **Test Workflow**: Run through complete vehicle check process
6. **Set Schedule**: Plan when to check for expired vehicles
7. **Backup Database**: Export database before going live

---

## Support & Documentation

- Full Setup: See `SETUP_INSTRUCTIONS.md`
- All Changes: See `IMPROVEMENTS_SUMMARY.md`
- Source Code: All files in project directory
- Database Config: `config/db.php`
- Style Customization: `assets/css/style.css`

---

## System URLs Quick Reference

```
Homepage:        http://localhost/SmartVehicleAuthPass/
Initialize DB:   http://localhost/SmartVehicleAuthPass/config/init_db.php
Update DB:       http://localhost/SmartVehicleAuthPass/config/db_update.php

Admin Login:     http://localhost/SmartVehicleAuthPass/admin/login.php
Guard Login:     http://localhost/SmartVehicleAuthPass/guard/login.php
Teacher Login:   http://localhost/SmartVehicleAuthPass/teacher/login.php
Student Login:   http://localhost/SmartVehicleAuthPass/student/login.php

Guard Signup:    http://localhost/SmartVehicleAuthPass/guard/signup.php
```

---

Your system is ready to use! Start by running the database update, then create your admin account.
