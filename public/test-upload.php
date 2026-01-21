<?php
/**
 * Photo Upload & Settings Diagnostic
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$is_admin || !$school_id) {
    die('❌ Admin access required');
}

// Load SchoolProfileModel
require __DIR__ . '/../src/SchoolProfileModel.php';
$model = new SchoolProfileModel($pdo);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload & Settings Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #0b3d61;
            margin-bottom: 20px;
        }

        h2 {
            color: #1f2937;
            margin: 20px 0 10px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }

        .info {
            background: #dbeafe;
            color: #0c4a6e;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }

        input[type="file"] {
            padding: 10px;
        }

        button {
            background: #0b3d61;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #082f4d;
        }

        code {
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New';
        }

        .form-group {
            margin: 15px 0;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🖼️ School Photo Upload & Settings Test</h1>

        <!-- 1. Current Status -->
        <div class="card">
            <h2>1️⃣ Current School Profile Status</h2>
            <?php
            $school = $model->getSchoolProfile($school_id);
            if ($school) {
                echo "<p><strong>School ID:</strong> {$school['id']}</p>";
                echo "<p><strong>Name:</strong> {$school['name']}</p>";
                echo "<p><strong>Photo Path:</strong> " . ($school['photo_path'] ? "<code>{$school['photo_path']}</code>" : "<span style='color: red;'>Empty</span>") . "</p>";

                if ($school['photo_path']) {
                    $file_path = __DIR__ . '/' . $school['photo_path'];
                    $file_exists = file_exists($file_path);
                    echo "<p><strong>Photo File Status:</strong> " . ($file_exists ? "<span style='color: green;'>✅ File exists</span>" : "<span style='color: red;'>❌ File not found: $file_path</span>") . "</p>";
                }
            }
            ?>
        </div>

        <!-- 2. Upload Directory Check -->
        <div class="card">
            <h2>2️⃣ Upload Directory Status</h2>
            <?php
            $upload_dir = __DIR__ . '/uploads/school-photos';
            $exists = is_dir($upload_dir);
            $writable = is_writable($upload_dir);

            echo "<p><strong>Path:</strong> <code>$upload_dir</code></p>";
            echo "<p><strong>Exists:</strong> " . ($exists ? "✅ Yes" : "❌ No") . "</p>";
            echo "<p><strong>Writable:</strong> " . ($writable ? "✅ Yes" : "❌ No") . "</p>";

            if ($exists) {
                $files = scandir($upload_dir);
                $photo_files = array_filter($files, function ($f) {
                    return strpos($f, 'school_') === 0; });
                echo "<p><strong>Files in directory:</strong> " . count($photo_files) . "</p>";
                if (count($photo_files) > 0) {
                    echo "<ul>";
                    foreach ($photo_files as $f) {
                        $size = filesize($upload_dir . '/' . $f);
                        echo "<li><code>$f</code> (" . round($size / 1024, 2) . " KB)</li>";
                    }
                    echo "</ul>";
                }
            }
            ?>
        </div>

        <!-- 3. Test Upload Form -->
        <div class="card">
            <h2>3️⃣ Test Photo Upload</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="photo">Select Photo (JPG/PNG/WEBP, max 5MB):</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <button type="submit" name="action" value="test_upload">Upload Test Photo</button>
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'test_upload') {
                try {
                    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('File upload failed: ' . $_FILES['photo']['error']);
                    }

                    echo "<div class='info'>📤 Processing upload...</div>";

                    // Use model's validation
                    $model->validatePhotoFile($_FILES['photo']);
                    echo "<div class='success'>✅ File validation passed</div>";

                    // Save the file
                    $photo_path = $model->savePhotoFile($_FILES['photo']);
                    echo "<div class='success'>✅ File saved: <code>$photo_path</code></div>";

                    // Update database
                    $model->updateSchoolProfile($school_id, ['photo_path' => $photo_path]);
                    echo "<div class='success'>✅ Database updated</div>";

                    // Show preview
                    echo "<p style='margin-top: 20px;'><strong>Preview:</strong></p>";
                    echo "<img src='/$photo_path' style='max-width: 200px; border-radius: 50%; border: 3px solid #0b3d61;'>";

                } catch (Exception $e) {
                    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            ?>
        </div>

        <!-- 4. API Endpoint Test -->
        <div class="card">
            <h2>4️⃣ API Endpoint Test</h2>
            <p>API endpoint untuk photo upload:</p>
            <code
                style="display: block; padding: 10px;">POST /perpustakaan-online/public/api/school-profile.php?action=upload_photo</code>

            <p style="margin-top: 15px;">Files required:</p>
            <ul>
                <li><code>public/api/school-profile.php</code> - API handler</li>
                <li><code>src/SchoolProfileModel.php</code> - Model class</li>
            </ul>

            <?php
            $api_file = __DIR__ . '/api/school-profile.php';
            $model_file = __DIR__ . '/../src/SchoolProfileModel.php';

            echo "<p style='margin-top: 15px;'><strong>File Status:</strong></p>";
            echo "<p>API file: " . (file_exists($api_file) ? "✅ Exists" : "❌ Missing") . "</p>";
            echo "<p>Model file: " . (file_exists($model_file) ? "✅ Exists" : "❌ Missing") . "</p>";
            ?>
        </div>

        <!-- 5. Settings Page Check -->
        <div class="card">
            <h2>5️⃣ Settings Page Status</h2>
            <?php
            $settings_file = __DIR__ . '/settings.php';
            $sidebar_file = __DIR__ . '/partials/sidebar.php';

            echo "<p>Settings page: " . (file_exists($settings_file) ? "✅ Exists" : "❌ Missing") . "</p>";
            echo "<p>Sidebar partial: " . (file_exists($sidebar_file) ? "✅ Exists" : "❌ Missing") . "</p>";
            ?>
            <p style="margin-top: 15px;">
                <strong>Next Steps:</strong>
            <ol>
                <li>Test upload foto di form di atas</li>
                <li>Jika berhasil, cek sidebar di dashboard</li>
                <li>Jika masih ada error, buka F12 console dan share error-nya</li>
            </ol>
            </p>
        </div>

        <div class="card" style="background: #fffbeb; border: 2px solid #fbbf24;">
            <h2>❓ Troubleshooting</h2>
            <p><strong>Jika upload error:</strong></p>
            <ul>
                <li>Check file size - max 5MB</li>
                <li>Check file type - JPG, PNG, atau WEBP only</li>
                <li>Check upload directory permissions</li>
                <li>Check free disk space</li>
            </ul>
        </div>
    </div>
</body>

</html>