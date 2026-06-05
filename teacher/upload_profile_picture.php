<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';
require_once '../config/cloudinary.php';

$student_id = $_SESSION['student_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;

        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size exceeds 5MB limit.';
        } else {
            // Upload to Cloudinary instead of local server
            $result = uploadToCloudinary($file['tmp_name'], 'smartvehicle/profiles');

            if ($result['success']) {
                $cloudinaryUrl = $result['url'];
                $stmt = $pdo->prepare("UPDATE students SET profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$cloudinaryUrl, $student_id])) {
                    $success = 'Profile picture updated successfully!';
                    $student['profile_picture'] = $cloudinaryUrl;
                } else {
                    $error = 'Failed to update profile picture in database.';
                }
            } else {
                $error = 'Failed to upload image: ' . $result['error'];
            }
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Profile Picture</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .upload-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .header p {
            color: #6b7280;
            font-size: 1rem;
        }

        .current-picture {
            text-align: center;
            margin-bottom: 30px;
        }

        .current-picture img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #3b82f6;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .current-picture p {
            margin-top: 15px;
            color: #6b7280;
            font-weight: 600;
        }

        .upload-section {
            margin-bottom: 25px;
        }

        .file-input-wrapper {
            position: relative;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border: 2px dashed #d1d5db;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-input-icon svg {
            width: 30px;
            height: 30px;
            color: white;
        }

        .file-input-text {
            color: #1f2937;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .file-input-subtext {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .requirements {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .requirements h3 {
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .requirements li {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
        }

        .requirements li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }

        @media (max-width: 640px) {
            .upload-container {
                padding: 30px 20px;
            }

            .button-group {
                flex-direction: column;
            }
        }

        /* === DARK MODE === */
        html.dark-mode body, body.dark-mode {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        }
        html.dark-mode .upload-container, body.dark-mode .upload-container {
            background: #1e293b !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6) !important;
        }
        html.dark-mode .header h1, body.dark-mode .header h1 { color: #f1f5f9 !important; }
        html.dark-mode .header p, body.dark-mode .header p { color: #94a3b8 !important; }
        html.dark-mode .current-picture p, body.dark-mode .current-picture p { color: #94a3b8 !important; }
        html.dark-mode .file-input-wrapper, body.dark-mode .file-input-wrapper {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border-color: #334155 !important;
        }
        html.dark-mode .file-input-wrapper:hover, body.dark-mode .file-input-wrapper:hover {
            border-color: #3b82f6 !important;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%) !important;
        }
        html.dark-mode .file-input-text, body.dark-mode .file-input-text { color: #e2e8f0 !important; }
        html.dark-mode .file-input-subtext, body.dark-mode .file-input-subtext { color: #64748b !important; }
        html.dark-mode .alert-success, body.dark-mode .alert-success {
            background: #064e3b !important; color: #a7f3d0 !important; border-color: #10b981 !important;
        }
        html.dark-mode .alert-error, body.dark-mode .alert-error {
            background: #450a0a !important; color: #fca5a5 !important; border-color: #ef4444 !important;
        }
        html.dark-mode .btn-secondary, body.dark-mode .btn-secondary {
            background: #334155 !important; color: #cbd5e1 !important;
        }
        html.dark-mode .btn-secondary:hover, body.dark-mode .btn-secondary:hover {
            background: #475569 !important;
        }
        html.dark-mode .requirements, body.dark-mode .requirements { background: #0f172a !important; }
        html.dark-mode .requirements h3, body.dark-mode .requirements h3 { color: #f1f5f9 !important; }
        html.dark-mode .requirements li, body.dark-mode .requirements li { color: #94a3b8 !important; }
    </style>
<script>
    // Apply dark mode before paint to prevent flash
    if (localStorage.getItem('svaps_dark') === '1') {
        document.documentElement.classList.add('dark-mode');
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('svaps_dark') === '1') {
            document.body.classList.add('dark-mode');
        }
    });
    // Listen for dark mode changes from OTHER pages/tabs
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
<body>
    <div class="upload-container">
        <div class="header">
            <h1>Upload Profile Picture</h1>
            <p>Update your profile picture to personalize your dashboard</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="current-picture">
            <?php if (!empty($student['profile_picture'])): ?>
                <?php $picSrc = (strpos($student['profile_picture'], 'http') === 0) ? $student['profile_picture'] : '../' . $student['profile_picture']; ?>
                <img src="<?= htmlspecialchars($picSrc) ?>" alt="Current Profile Picture">
                <p>Current Profile Picture</p>
            <?php else: ?>
                <img src="https://images.pexels.com/photos/1212984/pexels-photo-1212984.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Default Profile">
                <p>Default Profile Picture</p>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="upload-section">
                <div class="file-input-wrapper">
                    <input type="file" name="profile_picture" accept="image/*" required>
                    <div class="file-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="file-input-text">Click to upload or drag and drop</div>
                    <div class="file-input-subtext">JPG, PNG, or GIF (MAX. 5MB)</div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">Upload Picture</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>

        <div class="requirements">
            <h3>Image Requirements</h3>
            <ul>
                <li>Supported formats: JPG, PNG, GIF</li>
                <li>Maximum file size: 5MB</li>
                <li>Recommended: Square images for best results</li>
                <li>Minimum resolution: 400x400 pixels</li>
            </ul>
        </div>
    </div>
</body>
</html>