<?php
/**
 * Detailed Photo Debug - Check file_exists logic
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$is_admin || !$school_id) {
    die('❌ Admin access required');
}

$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $school_id]);
$school = $stmt->fetch();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Detailed Photo Debug</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
        }

        .box {
            background: #334155;
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid #60a5fa;
        }

        h2 {
            color: #60a5fa;
            margin: 20px 0 10px;
        }

        code {
            background: #0f172a;
            padding: 8px;
            border-radius: 4px;
            display: block;
            margin: 10px 0;
        }

        .ok {
            color: #34d399;
        }

        .error {
            color: #f87171;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #475569;
        }

        th {
            background: #0f172a;
        }
    </style>
</head>

<body>
    <h1>🔬 DETAILED PHOTO DEBUG</h1>

    <div class="box">
        <h2>1️⃣ Current State</h2>
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>School ID</td>
                <td><?php echo $school['id']; ?></td>
            </tr>
            <tr>
                <td>School Name</td>
                <td><?php echo $school['name']; ?></td>
            </tr>
            <tr>
                <td>Photo Path in DB</td>
                <td><code><?php echo $school['photo_path'] ?: '(NULL)'; ?></code></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>2️⃣ Sidebar File Check Simulation</h2>
        <?php
        // Simulate exact sidebar.php logic
        $school_photo = $school['photo_path'] ?? null;

        echo "<p>Variable: <code>\$school_photo = " . ($school_photo ? "'$school_photo'" : "null") . "</code></p>";

        if ($school_photo) {
            $check_path = __DIR__ . '/../' . $school_photo;
            $exists = file_exists($check_path);

            echo "<p>File check path: <code>$check_path</code></p>";
            echo "<p>file_exists result: <span class='" . ($exists ? 'ok' : 'error') . "'>" . ($exists ? '✅ TRUE' : '❌ FALSE') . "</span></p>";

            if ($exists) {
                $size = filesize($check_path);
                echo "<p>File size: <code>" . round($size / 1024, 2) . " KB</code></p>";

                // Check what sidebar will render
                echo "<p style='margin-top: 20px;'><strong>Sidebar will render:</strong></p>";
                echo "<code>&lt;img src=\"/../" . htmlspecialchars($school_photo) . "\" ...&gt;</code>";
            }
        } else {
            echo "<p class='error'>❌ \$school_photo is NULL - placeholder will show</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>3️⃣ All Files in Upload Directory</h2>
        <?php
        $upload_dir = __DIR__ . '/../uploads/school-photos';
        echo "<p>Directory: <code>$upload_dir</code></p>";

        if (is_dir($upload_dir)) {
            $files = array_diff(scandir($upload_dir), ['.', '..']);
            if (count($files) > 0) {
                echo "<p>Files: " . count($files) . "</p>";
                foreach ($files as $f) {
                    $path = $upload_dir . '/' . $f;
                    $size = filesize($path);
                    $mtime = filemtime($path);
                    $time = date('Y-m-d H:i:s', $mtime);
                    echo "<code>$f</code> - " . round($size / 1024, 2) . " KB - Modified: $time<br>";
                }
            } else {
                echo "<p class='error'>No files in upload directory!</p>";
            }
        }
        ?>
    </div>

    <div class="box">
        <h2>4️⃣ Test Upload & Verify</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="photo" accept="image/*" required>
            <button type="submit"
                style="background: #0b3d61; color: white; padding: 10px 20px; border: none; border-radius: 6px; margin-top: 10px;">Upload
                & Update DB</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
            require __DIR__ . '/../src/SchoolProfileModel.php';
            $model = new SchoolProfileModel($pdo);

            try {
                // Validate
                $model->validatePhotoFile($_FILES['photo']);

                // Save file
                $filename = $model->savePhotoFile($_FILES['photo']);

                // Important: Path should be relative to public folder
                $photo_path = 'public/uploads/school-photos/' . $filename;

                // Update DB directly
                $stmt = $pdo->prepare('UPDATE schools SET photo_path = :path WHERE id = :id');
                $result = $stmt->execute(['path' => $photo_path, 'id' => $school_id]);

                if ($result) {
                    echo "<div style='background: #dcfce7; color: #065f46; padding: 15px; border-radius: 6px; margin-top: 10px;'>";
                    echo "✅ SUCCESS!<br>";
                    echo "<strong>Filename:</strong> $filename<br>";
                    echo "<strong>Path Updated in DB:</strong> <code>$photo_path</code><br>";
                    echo "<strong>Now reload sidebar to verify</strong>";
                    echo "</div>";
                } else {
                    throw new Exception('Database update failed');
                }
            } catch (Exception $e) {
                echo "<div style='background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-top: 10px;'>";
                echo "❌ Error: " . htmlspecialchars($e->getMessage());
                echo "</div>";
            }
        }
        ?>
    </div>

    <div class="box">
        <h2>❓ Why Photo Not Showing?</h2>
        <p>
            1. Check if photo_path in DB is NULL or empty<br>
            2. Check if photo_path value matches actual file in uploads folder<br>
            3. Check if file_exists() returns TRUE in step 2️⃣<br>
            4. If file exists but still shows placeholder, check browser console for JS errors<br>
            5. Try Ctrl+Shift+Delete to clear browser cache
        </p>
    </div>
</body>

</html>