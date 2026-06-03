# QR Code Scanner Fix & Troubleshooting Guide

## What Was Fixed

### 1. Enhanced QR Code Scanner (Guard Dashboard)
- **Added Manual Search**: Guards can now manually enter a plate number if scanning fails
- **Better Error Handling**: The scanner now checks multiple field names (`plate`, `plate_number`, `vehicle_id`)
- **Fallback to Plain Text**: If QR code is not JSON, it treats it as a plain plate number
- **Console Logging**: Added debug logs to see what data is scanned (check browser console with F12)

### 2. Testing Tools Created

#### Test QR Codes Page (`admin/test_qr.php`)
- View all registered vehicles and their QR codes
- See the expected QR code data format
- Verify QR codes exist and are accessible
- Access from Admin Dashboard → "Test QR Codes" button

#### Standalone Scanner Test (`test_scanner.html`)
- Scan any QR code and see the raw data
- Shows exactly what's encoded in the QR
- Validates JSON format
- Displays all fields found in the QR code

## How to Troubleshoot

### Step 1: Test with Manual Search First
1. Go to Guard Dashboard
2. Instead of scanning, use the manual search field
3. Enter a plate number from a registered vehicle (e.g., "ABC123")
4. If this works, the problem is with the QR code itself, not the database

### Step 2: Verify QR Codes
1. Login as Admin
2. Go to "Test QR Codes" in the quick actions
3. Check if QR codes exist for your vehicles
4. Compare the displayed data with what should be encoded

### Step 3: Test QR Code Data
1. Open `test_scanner.html` in your browser
2. Scan one of your vehicle QR codes
3. Check the output:
   - Does it show valid JSON?
   - Does it have the `plate` field?
   - What data is actually in the QR code?

### Step 4: Check Browser Console
1. Open Guard Dashboard
2. Press F12 to open Developer Tools
3. Go to the "Console" tab
4. Try scanning a QR code
5. Look for messages like:
   - "QR Code scanned: ..." (shows raw data)
   - "Parsed QR data: ..." (shows JSON object)
   - Any error messages

## Common Issues & Solutions

### Issue: "Invalid QR Code: No plate number found"

**Possible Causes:**
1. QR code was generated with wrong data format
2. QR code image is corrupted
3. External QR generation API failed

**Solutions:**
- Use the manual search as a workaround
- Register a new vehicle to generate a fresh QR code
- Check `admin/test_qr.php` to see if QR codes exist

### Issue: "Vehicle not found"

**Possible Causes:**
1. Plate number in database doesn't match QR code
2. Vehicle was deleted
3. Database connection issue

**Solutions:**
- Verify vehicle exists in admin dashboard
- Check plate number spelling/capitalization
- Use manual search to test database lookup

### Issue: QR Code Won't Scan

**Possible Causes:**
1. Camera permissions not granted
2. Poor lighting
3. QR code image quality
4. QR code too small or damaged

**Solutions:**
- Check browser camera permissions
- Ensure good lighting
- Use the manual search alternative
- Try the standalone test scanner (`test_scanner.html`)

## Expected QR Code Format

QR codes should contain JSON data like this:

```json
{
  "vehicle_id": "ABC123",
  "plate": "ABC123",
  "owner": "John Doe",
  "type": "Car"
}
```

The scanner looks for the `plate` field to fetch vehicle details.

## New Features

### Manual Search (Guard Dashboard)
- Located below the QR scanner
- Enter any plate number manually
- Press Enter or click "Search"
- Works exactly like QR scanning but without camera

### Enhanced Error Messages
- Shows what data was found in the QR code
- Displays helpful troubleshooting information
- Logs detailed debug info to browser console

### Test Tools
- **Admin → Test QR Codes**: View all QR codes and their data
- **test_scanner.html**: Standalone scanner for debugging
- **Debug Console**: Press F12 to see detailed scan logs

## Testing Checklist

- [ ] Can you manually search for a vehicle using its plate number?
- [ ] Do QR codes appear in the admin test page?
- [ ] Does the standalone scanner successfully read the QR codes?
- [ ] Does the browser console show any error messages?
- [ ] Are the QR code files accessible (images load in test page)?

## Next Steps

1. **Try manual search first** - This confirms the database and API work correctly
2. **Check the test QR page** - Verify QR codes exist and show correct data
3. **Use test scanner** - See exactly what's in your QR codes
4. **Report findings** - If still not working, note:
   - Does manual search work?
   - Do QR code images exist?
   - What does the test scanner show?
   - Any console error messages?

## Quick Fix

If QR scanning continues to fail but manual search works:

**Immediate Workaround:**
- Guards can use the manual search feature
- Enter plate numbers directly instead of scanning

**Permanent Fix:**
- Register new vehicles to generate fresh QR codes
- The new QR codes will use the improved format
- Old vehicles may need QR codes regenerated
