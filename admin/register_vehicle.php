<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
require_once '../config/helpers.php';
require_once '../config/cloudinary.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ownerType = $_POST['owner_type'] ?? '';
        $vehicleType = $_POST['vehicle_type'] ?? '';
        $registeredUnder = trim($_POST['registered_under'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $plateNumber = trim($_POST['plate_number'] ?? '');
        $dateRegistered = date('Y-m-d');
        $dateExpiration = calculateExpirationDate($vehicleType, $dateRegistered);

        if (!$ownerType || !$vehicleType || !$registeredUnder || !$plateNumber) {
            throw new Exception('Please fill in all required fields.');
        }

        // Validate that OR-CR / License image is uploaded
        if (!isset($_FILES['license_image']) || $_FILES['license_image']['error'] !== UPLOAD_ERR_OK || $_FILES['license_image']['size'] === 0) {
            throw new Exception('Driver\'s License / OR-CR image is required. Please upload the document before registering.');
        }

        // Validate file extension for license image
        $allowedExtensionsCheck = ['jpg', 'jpeg', 'png', 'gif'];
        $licenseExt = strtolower(pathinfo($_FILES['license_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($licenseExt, $allowedExtensionsCheck)) {
            throw new Exception('Invalid file type for Driver\'s License / OR-CR. Allowed types: JPG, JPEG, PNG, GIF.');
        }

        // Validate that owner photo is uploaded
        if (!isset($_FILES['owner_photo']) || $_FILES['owner_photo']['error'] !== UPLOAD_ERR_OK || $_FILES['owner_photo']['size'] === 0) {
            throw new Exception('Owner photo is required. Please upload a photo of the owner before registering.');
        }

        // Validate file extension for owner photo
        $ownerPhotoExt = strtolower(pathinfo($_FILES['owner_photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ownerPhotoExt, $allowedExtensionsCheck)) {
            throw new Exception('Invalid file type for Owner Photo. Allowed types: JPG, JPEG, PNG, GIF.');
        }

        if ($ownerType === 'Student') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $name = $firstName . ' ' . $lastName;
            $schoolId = trim($_POST['school_id'] ?? '');
            $yearSection = trim($_POST['year_section'] ?? '');
            $sex = $_POST['sex'] ?? '';
            $course = $_POST['course'] ?? '';
            $birthdate = $_POST['birthdate'] ?? '';
            $age = $_POST['age'] ?? '';

            // Auto-generate email from name: lastname.firstname@mdci.edu.ph
            $emailFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName));
            $emailLast  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));
            $baseEmail = $emailLast . '.' . $emailFirst . '@mdci.edu.ph';
            // Ensure uniqueness by appending a counter if needed
            $email = $baseEmail;
            $emailCounter = 1;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() === 0) break;
                $email = $emailLast . '.' . $emailFirst . $emailCounter . '@mdci.edu.ph';
                $emailCounter++;
            }

            $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ?");
            $stmt->execute([$schoolId]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('A student with this School ID already exists.');
            }

            $randomPassword = bin2hex(random_bytes(8));
            $password = password_hash($randomPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO students (name, school_id, email, year_section, sex, course, birthdate, age, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $schoolId, $email, $yearSection, $sex, $course, $birthdate, $age, $password]);
            $ownerId = $pdo->lastInsertId();
            $_SESSION['temp_student_password'] = $randomPassword;
            $_SESSION['temp_student_email'] = $email;

        } else {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $name = $firstName . ' ' . $lastName;
            $employeeId = trim($_POST['employee_id'] ?? '');
            $sex = $_POST['sex'] ?? '';
            $course = $_POST['course'] ?? '';
            $birthdate = $_POST['birthdate'] ?? '';
            $age = $_POST['age'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM teachers_staff WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('A teacher/staff with this Employee ID already exists.');
            }

            $randomPassword = bin2hex(random_bytes(8));
            $password = password_hash($randomPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO teachers_staff (name, employee_id, sex, course, birthdate, age, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $employeeId, $sex, $course, $birthdate, $age, $password]);
            $ownerId = $pdo->lastInsertId();
            $_SESSION['temp_teacher_password'] = $randomPassword;
        }

        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE plate_number = ?");
        $stmt->execute([$plateNumber]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('A vehicle with this plate number already exists.');
        }

        $uploadDir = __DIR__ . '/../uploads/vehicles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Handle license / OR-CR image upload
        $licenseImagePath = null;
        if (isset($_FILES['license_image']) && $_FILES['license_image']['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES['license_image']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExtension, $allowedExtensions)) {
                $result = uploadToCloudinary($_FILES['license_image']['tmp_name'], 'smartvehicle/vehicles');
                if ($result['success']) {
                    $licenseImagePath = $result['url'];
                }
            }
        }

        // Handle owner / student photo upload
        $ownerPhotoPath = null;
        if (isset($_FILES['owner_photo']) && $_FILES['owner_photo']['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES['owner_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExtension, $allowedExtensions)) {
                $result = uploadToCloudinary($_FILES['owner_photo']['tmp_name'], 'smartvehicle/vehicles');
                if ($result['success']) {
                    $ownerPhotoPath = $result['url'];
                }
            }
        }

        // Keep vehicle_image as the license image for backward compatibility
        $vehicleImagePath = $licenseImagePath;

        // Owner photo is stored only in the vehicle record, not in the user's profile_picture

        $qrData = json_encode([
            'vehicle_id' => $plateNumber,
            'plate' => $plateNumber,
            'owner' => $registeredUnder,
            'type' => $vehicleType
        ]);
        $qrFilename = $plateNumber . '_' . time() . '.png';
        $qrPath = generateQRCode($qrData, $qrFilename);
        if (!$qrPath) {
            throw new Exception('Failed to generate QR code. Check internet or folder permissions.');
        }

        // Use the correct physical columns instead of the virtual owner_id
        if ($ownerType === 'Student') {
            $studentOwnerId = $ownerId;
            $teacherOwnerId = null;
        } else {
            $studentOwnerId = null;
            $teacherOwnerId = $ownerId;
        }
        $stmt = $pdo->prepare("INSERT INTO vehicles (owner_type, student_owner_id, teacher_owner_id, type, registered_under, color, brand, plate_number, status, date_registered, date_expiration, qr_code_path, vehicle_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Not Expired', ?, ?, ?, ?)");
        $stmt->execute([$ownerType, $studentOwnerId, $teacherOwnerId, $vehicleType, $registeredUnder, $color, $brand, $plateNumber, $dateRegistered, $dateExpiration, $qrPath, $vehicleImagePath]);

        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$teachers = $pdo->query("SELECT * FROM teachers_staff")->fetchAll();
$students = $pdo->query("SELECT * FROM students")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Vehicle</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .register-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            transition: width 0.4s ease;
            z-index: 1;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #9ca3af;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: linear-gradient(135deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            border-color: var(--theme-color);
            color: white;
            box-shadow: 0 4px 15px rgba(var(--theme-color-rgb), 0.4);
        }

        .step.completed .step-circle {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 600;
        }

        .step.active .step-label {
            color: var(--theme-color);
        }

        .form-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }

        .form-card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(var(--theme-color-rgb), 0.3);
        }

        .form-card-icon svg {
            width: 26px;
            height: 26px;
            color: white;
            stroke-width: 2;
        }

        .form-card-title h3 {
            font-size: 1.4rem;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .form-card-title p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-grid.single {
            grid-template-columns: 1fr;
        }

        .form-field {
            display: flex;
            flex-direction: column;
        }

        .form-field label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-field label .required {
            color: #ef4444;
            margin-left: 3px;
        }

        .form-input {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
            color: #1f2937;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--theme-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(var(--theme-color-rgb), 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .image-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .image-upload-area:hover {
            border-color: var(--theme-color);
            background: rgba(var(--theme-color-rgb), 0.05);
        }

        .image-upload-area.has-image {
            border-style: solid;
            border-color: #10b981;
        }

        .upload-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-icon svg {
            width: 30px;
            height: 30px;
            color: white;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-modern {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--theme-color) 0%, var(--theme-color-light) 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(var(--theme-color-rgb), 0.4);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(var(--theme-color-rgb), 0.5);
        }

        .btn-secondary-modern {
            background: #f3f4f6;
            color: #4b5563;
        }

        .btn-secondary-modern:hover {
            background: #e5e7eb;
        }

        .alert-modern {
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            animation: slideInAlert 0.4s ease-out;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .alert-success-card {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }

        .alert-danger-card {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        .alert-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .alert-content h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .alert-content p {
            color: #4b5563;
            margin-bottom: 10px;
        }

        .password-display {
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 1.1rem;
            margin: 10px 0;
            border: 2px solid #10b981;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-card {
                padding: 25px;
            }

            .progress-steps {
                flex-direction: column;
                gap: 15px;
            }

            .progress-steps::before {
                display: none;
            }
        }

        /* ===== DARK MODE ===== */
        body.dark-mode { background: #0f172a !important; color: #e2e8f0; }
        body.dark-mode .sidebar { background: #1e293b; box-shadow: 2px 0 10px rgba(0,0,0,0.4); }
        body.dark-mode .sidebar-brand { border-bottom-color: #334155; }
        body.dark-mode .sidebar-brand h2 { color: #f1f5f9; }
        body.dark-mode .sidebar-brand p { color: #94a3b8; }
        body.dark-mode .menu-item { color: #94a3b8; }
        body.dark-mode .menu-item:hover { background: #334155; }
        body.dark-mode .menu-item.active { background: rgba(var(--theme-color-rgb), 0.2); color: var(--theme-color-light); }
        body.dark-mode .top-nav { background: #1e293b; box-shadow: 0 2px 10px rgba(0,0,0,0.4); }
        body.dark-mode .top-nav-left h1 { color: #f1f5f9; }
        body.dark-mode .top-nav-left p { color: #94a3b8; }
        body.dark-mode .form-card { background: #1e293b; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        body.dark-mode .form-card-title h3 { color: #f1f5f9; }
        body.dark-mode .form-card-title p { color: #94a3b8; }
        body.dark-mode .form-card-header { border-bottom-color: #334155; }
        body.dark-mode .form-field label { color: #cbd5e1; }
        body.dark-mode .form-input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        body.dark-mode .form-input:focus { background: #1e293b; border-color: var(--theme-color-light); box-shadow: 0 0 0 4px rgba(var(--theme-color-rgb), 0.2); }
        body.dark-mode .form-input::placeholder { color: #475569; }

        body.dark-mode .image-upload-area { background: #0f172a; border-color: #334155; }
        body.dark-mode .image-upload-area h4 { color: #e2e8f0 !important; }
        body.dark-mode .image-upload-area p { color: #64748b !important; }
        body.dark-mode .progress-steps::before { background: #334155; }
        body.dark-mode .step-circle { background: #1e293b; border-color: #334155; color: #64748b; }
        body.dark-mode .step-label { color: #94a3b8; }
        body.dark-mode .btn-secondary-modern { background: #334155; color: #cbd5e1; }
        body.dark-mode .btn-secondary-modern:hover { background: #475569; }
        body.dark-mode #darkModeToggle { border-color: #334155; color: #94a3b8; background: #1e293b; }
        body.dark-mode .alert-success-card { background: #064e3b; border-left-color: #10b981; }
        body.dark-mode .alert-success-card .alert-content h4 { color: #d1fae5; }
        body.dark-mode .alert-success-card .alert-content p { color: #a7f3d0; }
        body.dark-mode .alert-danger-card { background: #450a0a; border-left-color: #ef4444; }
        body.dark-mode .alert-danger-card .alert-content h4 { color: #fecaca; }
        body.dark-mode .alert-danger-card .alert-content p { color: #fca5a5; }
        body.dark-mode .password-display { background: #0f172a; border-color: #10b981; color: #d1fae5; }
    </style>
<script>
    // 1) Apply dark mode to <html> immediately (no flash on page load)
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }

    // 2) When body is ready, sync class to <body> and wire the toggle button
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('svaps_dark') === '1') {
            document.body.classList.add('dark-mode');
        }
        var btn = document.getElementById('darkModeToggle');
        var icon = document.getElementById('darkModeIcon');
        function setIcon(on) {
            if (!icon) return;
            icon.innerHTML = on
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2.34M18.36 5.64l-.71.71M6.34 17.66l-.71.71M18.36 18.36l-.71-.71M6.34 6.34l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />';
        }
        setIcon(localStorage.getItem('svaps_dark') === '1');
        if (btn) {
            btn.addEventListener('click', function() {
                var on = !document.body.classList.contains('dark-mode');
                localStorage.setItem('svaps_dark', on ? '1' : '0');
                document.documentElement.classList.toggle('dark-mode', on);
                document.body.classList.toggle('dark-mode', on);
                setIcon(on);
            });
        }
    });

    // 3) Listen for changes from OTHER open tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'svaps_dark') {
            var on = e.newValue === '1';
            document.documentElement.classList.toggle('dark-mode', on);
            document.body && document.body.classList.toggle('dark-mode', on);
            var icon = document.getElementById('darkModeIcon');
            if (icon) {
                icon.innerHTML = on
                    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2.34M18.36 5.64l-.71.71M6.34 17.66l-.71.71M18.36 18.36l-.71-.71M6.34 6.34l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />'
                    : '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />';
            }
        }
    });
</script>
</head>
<body class="admin-theme-dash">
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>SVAPS Admin</h2>
                <p>Vehicle Management</p>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <a href="register_vehicle.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Register Vehicle
                </a>
                <a href="manage_guards.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Manage Guards
                </a> <a href="test_qr.php" class="menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            Test QR Codes
                        </a>
                <a href="signup.php" class="menu-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-nav">
                <div class="top-nav-left">
                    <h1>Register New Vehicle</h1>
                    <p>Add a new vehicle and owner to the system</p>
                </div>
                <div class="top-nav-right">
                    <button id="darkModeToggle" title="Toggle Dark Mode" style="background:none;border:2px solid #e5e7eb;border-radius:10px;padding:7px 10px;cursor:pointer;display:flex;align-items:center;gap:6px;color:#6b7280;font-size:0.85rem;font-weight:600;transition:all 0.3s;">
                        <svg id="darkModeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px;height:18px;stroke-width:2;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span class="dark-btn-label">Dark</span>
                    </button>
                    <a href="dashboard.php" class="btn-secondary-modern">Back to Dashboard</a>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="register-wrapper">
                    <?php if ($success): ?>
                        <div class="alert-modern alert-success-card">
                            <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #10b981;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="alert-content">
                                <h4>Vehicle Registered Successfully!</h4>
                                <p>The vehicle has been registered and QR code generated.</p>
                                <?php if (isset($_SESSION['temp_student_password'])): ?>
                                    <strong>Generated Email:</strong>
                                    <div class="password-display"><?= htmlspecialchars($_SESSION['temp_student_email'] ?? '') ?></div>
                                    <strong>Student Password:</strong>
                                    <div class="password-display"><?= htmlspecialchars($_SESSION['temp_student_password']) ?></div>
                                    <p style="font-size: 0.9rem;">Please save these credentials and provide them to the student.</p>
                                    <?php unset($_SESSION['temp_student_password']); unset($_SESSION['temp_student_email']); ?>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['temp_teacher_password'])): ?>
                                    <strong>Teacher/Staff Password:</strong>
                                    <div class="password-display"><?= htmlspecialchars($_SESSION['temp_teacher_password']) ?></div>
                                    <p style="font-size: 0.9rem;">Please save this password and provide it to the teacher/staff.</p>
                                    <?php unset($_SESSION['temp_teacher_password']); ?>
                                <?php endif; ?>
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <a href="dashboard.php" class="btn-modern btn-primary-modern">View Vehicles</a>
                                    <a href="register_vehicle.php" class="btn-modern btn-secondary-modern">Register Another</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert-modern alert-danger-card">
                            <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #ef4444;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="alert-content">
                                <h4>Error</h4>
                                <p><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="progress-steps">
                        <div class="progress-line" id="progressLine"></div>
                        <div class="step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Owner Type</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Owner Info</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Vehicle Details</div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="vehicleForm" autocomplete="off">
                        <div class="form-card">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="form-card-title">
                                    <h3>Select Owner Type</h3>
                                    <p>Choose whether the vehicle owner is a student or teacher/staff</p>
                                </div>
                            </div>

                            <div class="form-grid single">
                                <div class="form-field">
                                    <label>Owner Type <span class="required">*</span></label>
                                    <select name="owner_type" class="form-input" id="ownerType" required>
                                        <option value="">Select Owner Type</option>
                                        <option value="Student">Student</option>
                                        <option value="Teacher/Staff">Teacher/Staff</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="studentFields" style="display:none;">
                            <div class="form-card">
                                <div class="form-card-header">
                                    <div class="form-card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <div class="form-card-title">
                                        <h3>Student Information</h3>
                                        <p>Enter student details</p>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>First Name <span class="required">*</span></label>
                                        <input type="text" name="first_name" id="studentFirstName" class="form-input" placeholder="Juan" autocomplete="off">
                                    </div>
                                    <div class="form-field">
                                        <label>Last Name <span class="required">*</span></label>
                                        <input type="text" name="last_name" id="studentLastName" class="form-input" placeholder="Dela Cruz" autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-grid single">
                                    <div class="form-field">
                                        <label>School ID <span class="required">*</span></label>
                                        <input type="text" name="school_id" class="form-input" placeholder="2024-00001" autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Year & Section</label>
                                        <select name="year_section" class="form-input" autocomplete="off">
                                            <option value="">Select Year</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Course</label>
                                        <select name="course" class="form-input" autocomplete="off">
                                            <option value="">Select Course</option>
                                            <option value="DIPLOMA IN SOFTWARE ENGINEERING">DIPLOMA IN SOFTWARE ENGINEERING</option>
                                            <option value="DIPLOMA IN DIGITAL ARTS TECHNOLOGY">DIPLOMA IN DIGITAL ARTS TECHNOLOGY</option>
                                            <option value="DIPLOMA IN TOURISM TECHNOLOGY">DIPLOMA IN TOURISM TECHNOLOGY</option>
                                            <option value="BACHELOR SCIENCE IN INFORMATION TECHNOLOGY">BACHELOR SCIENCE IN INFORMATION TECHNOLOGY</option>
                                            <option value="COLLEGE OF BUSINESS ADMINISTRATION">COLLEGE OF BUSINESS ADMINISTRATION</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Sex</label>
                                        <select name="sex" class="form-input" autocomplete="off">
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Birthdate</label>
                                        <input type="date" name="birthdate" class="form-input" autocomplete="off">
                                    </div>
                                    <div class="form-field">
                                        <label>Age</label>
                                        <input type="number" name="age" class="form-input" placeholder="20" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="teacherFields" style="display:none;">
                            <div class="form-card">
                                <div class="form-card-header">
                                    <div class="form-card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="form-card-title">
                                        <h3>Teacher/Staff Information</h3>
                                        <p>Enter teacher or staff details</p>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>First Name <span class="required">*</span></label>
                                        <input type="text" name="first_name" id="teacherFirstName" class="form-input" placeholder="Maria" autocomplete="off">
                                    </div>
                                    <div class="form-field">
                                        <label>Last Name <span class="required">*</span></label>
                                        <input type="text" name="last_name" id="teacherLastName" class="form-input" placeholder="Santos" autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Employee ID <span class="required">*</span></label>
                                        <input type="text" name="employee_id" class="form-input" placeholder="EMP-2024-001" autocomplete="off">
                                    </div>
                                    <div class="form-field">
                                        <label>Department/Course</label>
                                        <select name="course" class="form-input" autocomplete="off">
                                            <option value="">Select Department/Course</option>
                                            <option value="DIPLOMA IN SOFTWARE ENGINEERING">DIPLOMA IN SOFTWARE ENGINEERING</option>
                                            <option value="DIPLOMA IN DIGITAL ARTS TECHNOLOGY">DIPLOMA IN DIGITAL ARTS TECHNOLOGY</option>
                                            <option value="DIPLOMA IN TOURISM TECHNOLOGY">DIPLOMA IN TOURISM TECHNOLOGY</option>
                                            <option value="BACHELOR SCIENCE IN INFORMATION TECHNOLOGY">BACHELOR SCIENCE IN INFORMATION TECHNOLOGY</option>
                                            <option value="COLLEGE OF BUSINESS ADMINISTRATION">COLLEGE OF BUSINESS ADMINISTRATION</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Sex</label>
                                        <select name="sex" class="form-input" autocomplete="off">
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Birthdate</label>
                                        <input type="date" name="birthdate" class="form-input" autocomplete="off">
                                    </div>
                                    <div class="form-field">
                                        <label>Age</label>
                                        <input type="number" name="age" class="form-input" placeholder="30" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-card">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0h-.01M15 17a2 2 0 104 0m-4 0h-.01" />
                                    </svg>
                                </div>
                                <div class="form-card-title">
                                    <h3>Vehicle Information</h3>
                                    <p>Enter vehicle details and upload image</p>
                                </div>
                            </div>

                            <div class="form-grid single">
                                <div class="form-field">
                                    <label>Registered Under <span class="required">*</span></label>
                                    <input type="text" name="registered_under" class="form-input" required placeholder="Owner's full name" autocomplete="off">
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Vehicle Type <span class="required">*</span></label>
                                    <select name="vehicle_type" class="form-input" required autocomplete="off">
                                        <option value="">Select Type</option>
                                        <option value="Car">Car</option>
                                        <option value="Motorcycle">Motorcycle</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Plate Number <span class="required">*</span></label>
                                    <input type="text" name="plate_number" class="form-input" required placeholder="ABC-1234" autocomplete="off">
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Brand</label>
                                    <input type="text" name="brand" class="form-input" placeholder="Toyota, Honda, etc." autocomplete="off">
                                </div>
                                <div class="form-field">
                                    <label>Color</label>
                                    <input type="text" name="color" class="form-input" placeholder="White, Black, etc." autocomplete="off">
                                </div>
                            </div>

                            <div class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
                                <!-- License Image Upload -->
                                <div class="form-field">
                                    <label>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" /></svg>
                                        Driver's License / OR-CR <span class="required">*</span>
                                    </label>
                                    <div class="image-upload-area" id="licenseUploadArea">
                                        <input type="file" name="license_image" id="licenseImage" accept="image/*" style="display: none;">
                                        <div class="upload-icon" style="width:48px;height:48px;margin-bottom:10px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:24px;height:24px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" />
                                            </svg>
                                        </div>
                                        <h4 style="margin-bottom: 4px; color: #1f2937; font-size:0.95rem;">Upload License / OR-CR</h4>
                                        <p style="color: #6b7280; font-size: 0.8rem;">Click to browse or drag and drop</p>
                                        <img id="licensePreview" class="preview-image" style="display: none;">
                                    </div>
                                </div>

                                <!-- Owner/Student Photo Upload -->
                                <div class="form-field">
                                    <label>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Owner Photo <span class="required">*</span>
                                    </label>
                                    <div class="image-upload-area" id="ownerPhotoUploadArea">
                                        <input type="file" name="owner_photo" id="ownerPhoto" accept="image/*" style="display: none;">
                                        <div class="upload-icon" style="width:48px;height:48px;margin-bottom:10px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:24px;height:24px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h4 style="margin-bottom: 4px; color: #1f2937; font-size:0.95rem;">Upload Photo</h4>
                                        <p style="color: #6b7280; font-size: 0.8rem;">Click to browse or drag and drop</p>
                                        <img id="ownerPhotoPreview" class="preview-image" style="display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="dashboard.php" class="btn-modern btn-secondary-modern">Cancel</a>
                            <button type="submit" class="btn-modern btn-primary-modern">Register Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ============ DARK MODE ============
            var darkToggleBtn = document.getElementById('darkModeToggle');
            var darkIcon = document.getElementById('darkModeIcon');

            function applyDark(enabled) {
                document.body.classList.toggle('dark-mode', enabled);
                if (darkIcon) {
                    darkIcon.innerHTML = enabled
                        ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2.34M18.36 5.64l-.71.71M6.34 17.66l-.71.71M18.36 18.36l-.71-.71M6.34 6.34l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />'
                        : '<path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />';
                }
            }

            applyDark(localStorage.getItem('svaps_dark') === '1');

            if (darkToggleBtn) {
                darkToggleBtn.addEventListener('click', function () {
                    var isDark = document.body.classList.contains('dark-mode');
                    localStorage.setItem('svaps_dark', isDark ? '0' : '1');
                    applyDark(!isDark);
                });
            }

            // ============ OWNER TYPE TOGGLE ============
            var ownerTypeSelect = document.getElementById('ownerType');
            var studentFields = document.getElementById('studentFields');
            var teacherFields = document.getElementById('teacherFields');
            var steps = document.querySelectorAll('.step');
            var progressLine = document.getElementById('progressLine');

            ownerTypeSelect.addEventListener('change', function () {
                if (this.value === 'Student') {
                    studentFields.style.display = 'block';
                    teacherFields.style.display = 'none';
                    enableFields(studentFields);
                    disableFields(teacherFields);
                    updateProgress(2);
                } else if (this.value === 'Teacher/Staff') {
                    studentFields.style.display = 'none';
                    teacherFields.style.display = 'block';
                    disableFields(studentFields);
                    enableFields(teacherFields);
                    updateProgress(2);
                } else {
                    studentFields.style.display = 'none';
                    teacherFields.style.display = 'none';
                    disableFields(studentFields);
                    disableFields(teacherFields);
                    updateProgress(1);
                }
            });

            function enableFields(container) {
                container.querySelectorAll('input, select').forEach(function (el) { el.disabled = false; });
            }

            function disableFields(container) {
                container.querySelectorAll('input, select').forEach(function (el) { el.disabled = true; });
            }

            function updateProgress(currentStep) {
                steps.forEach(function (step, index) {
                    var stepNum = index + 1;
                    if (stepNum < currentStep) {
                        step.classList.add('completed');
                        step.classList.remove('active');
                    } else if (stepNum === currentStep) {
                        step.classList.add('active');
                        step.classList.remove('completed');
                    } else {
                        step.classList.remove('active', 'completed');
                    }
                });
                var progress = ((currentStep - 1) / (steps.length - 1)) * 100;
                progressLine.style.width = progress + '%';
            }

            // ============ LICENSE IMAGE UPLOAD ============
            var uploadArea = document.getElementById('licenseUploadArea');
            var vehicleImage = document.getElementById('licenseImage');
            var imagePreview = document.getElementById('licensePreview');

            uploadArea.addEventListener('click', function () { vehicleImage.click(); });

            vehicleImage.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        uploadArea.classList.add('has-image');
                        updateProgress(3);
                    };
                    reader.readAsDataURL(file);
                }
            });

            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--theme-color)';
            });
            uploadArea.addEventListener('dragleave', function () {
                uploadArea.style.borderColor = '#d1d5db';
            });
            uploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#d1d5db';
                var file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    vehicleImage.files = e.dataTransfer.files;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        uploadArea.classList.add('has-image');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // ============ OWNER PHOTO UPLOAD ============
            var ownerPhotoUploadArea = document.getElementById('ownerPhotoUploadArea');
            var ownerPhotoInput = document.getElementById('ownerPhoto');
            var ownerPhotoPreview = document.getElementById('ownerPhotoPreview');

            ownerPhotoUploadArea.addEventListener('click', function () { ownerPhotoInput.click(); });

            ownerPhotoInput.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        ownerPhotoPreview.src = e.target.result;
                        ownerPhotoPreview.style.display = 'block';
                        ownerPhotoUploadArea.classList.add('has-image');
                    };
                    reader.readAsDataURL(file);
                }
            });

            ownerPhotoUploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                ownerPhotoUploadArea.style.borderColor = 'var(--theme-color)';
            });
            ownerPhotoUploadArea.addEventListener('dragleave', function () {
                ownerPhotoUploadArea.style.borderColor = '#d1d5db';
            });
            ownerPhotoUploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                ownerPhotoUploadArea.style.borderColor = '#d1d5db';
                var file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    ownerPhotoInput.files = e.dataTransfer.files;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        ownerPhotoPreview.src = e.target.result;
                        ownerPhotoPreview.style.display = 'block';
                        ownerPhotoUploadArea.classList.add('has-image');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // ============ FORM SUBMIT VALIDATION ============
            var vehicleForm = document.getElementById('vehicleForm');
            vehicleForm.addEventListener('submit', function (e) {
                var errors = [];

                // Check owner type
                var ownerTypeVal = document.getElementById('ownerType').value;
                if (!ownerTypeVal) {
                    errors.push('Please select an Owner Type.');
                }

                // Check license / OR-CR upload
                var licenseFile = document.getElementById('licenseImage');
                if (!licenseFile.files || licenseFile.files.length === 0) {
                    errors.push("Driver's License / OR-CR image is required.");
                    document.getElementById('licenseUploadArea').style.borderColor = '#ef4444';
                    document.getElementById('licenseUploadArea').style.background = 'rgba(239,68,68,0.05)';
                } else {
                    document.getElementById('licenseUploadArea').style.borderColor = '';
                    document.getElementById('licenseUploadArea').style.background = '';
                }

                // Check owner photo upload
                var ownerPhotoFile = document.getElementById('ownerPhoto');
                if (!ownerPhotoFile.files || ownerPhotoFile.files.length === 0) {
                    errors.push('Owner Photo is required.');
                    document.getElementById('ownerPhotoUploadArea').style.borderColor = '#ef4444';
                    document.getElementById('ownerPhotoUploadArea').style.background = 'rgba(239,68,68,0.05)';
                } else {
                    document.getElementById('ownerPhotoUploadArea').style.borderColor = '';
                    document.getElementById('ownerPhotoUploadArea').style.background = '';
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    var existing = document.getElementById('clientValidationError');
                    if (existing) existing.remove();

                    var alertDiv = document.createElement('div');
                    alertDiv.id = 'clientValidationError';
                    alertDiv.className = 'alert-modern alert-danger-card';
                    alertDiv.style.marginBottom = '20px';
                    alertDiv.innerHTML =
                        '<svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#ef4444;flex-shrink:0;">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
                        '<div class="alert-content"><h4>Please fix the following before submitting:</h4><ul style="margin:8px 0 0 16px;padding:0;">' +
                        errors.map(function(err){ return '<li style="margin-bottom:4px;">'+err+'</li>'; }).join('') +
                        '</ul></div>';

                    vehicleForm.parentNode.insertBefore(alertDiv, vehicleForm);
                    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Clear red border when user picks a file
            document.getElementById('licenseImage').addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    document.getElementById('licenseUploadArea').style.borderColor = '';
                    document.getElementById('licenseUploadArea').style.background = '';
                }
            });
            document.getElementById('ownerPhoto').addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    document.getElementById('ownerPhotoUploadArea').style.borderColor = '';
                    document.getElementById('ownerPhotoUploadArea').style.background = '';
                }
            });

        }); // end DOMContentLoaded
    </script>
</body>
</html>