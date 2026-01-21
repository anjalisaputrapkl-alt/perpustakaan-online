<?php
/**
 * Photo Upload Debug - Check if photo actually saved
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$is_admin || !$school_id) {
    die('❌ Admin access required');
}

// Get school
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $school_id]);
$school = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Photo Debug</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #0b3d61;
            margin-bottom: 15px;
        }

        .section {
            background: #f9fafb;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #0b3d61;
        }

        code {
            background: #efefef;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }

        img {
            max-width: 300px;
            margin: 10px 0;
            border: 2px solid #0b3d61;
        }
    </style>
</head>

<body>
    <div style="max-width: 900px; margin: 0 auto;">
        <h1>🔍 Photo Upload Debug</h1>

        <!-- 1. Database Info -->
        <div class="card">
            <h2>1️⃣ Database Info</h2>
            <div class="section">
                <strong>School ID:</strong> <?php echo $school['id']; ?><br>
                <strong>School Name:</strong> <?php echo $school['name']; ?><br>
                <strong>Photo Path in DB:</strong>
                <?php
                if ($school['photo_path']) {
                    echo "<code style='background: #dcfce7;'>" . htmlspecialchars($school['photo_path']) . "</code>";
                } else {
                    echo "<span class='error'>NULL (tidak ada)</span>";
                }
                ?>
            </div>
        </div>

        <!-- 2. File System Check -->
        <div class="card">
            <h2>2️⃣ File System Check</h2>
            <?php
            if ($school['photo_path']) {
                $path = $school['photo_path'];

                // Check different possible paths
                $possible_paths = [
                    __DIR__ . '/' . $path,
                    __DIR__ . '/' . str_replace('uploads/', '../uploads/', $path),
                    __DIR__ . '/uploads/school-photos/' . basename($path),
                ];

                foreach ($possible_paths as $check_path) {
                    $exists = file_exists($check_path);
                    $status = $exists ? "<span class='success'>✅ EXISTS</span>" : "<span class='error'>❌ NOT FOUND</span>";
                    echo "<div class='section'>";
                    echo "<strong>Path:</strong> <code>" . htmlspecialchars($check_path) . "</code><br>";
                    echo "<strong>Status:</strong> $status";
                    if ($exists) {
                        $size = filesize($check_path);
                        echo "<br><strong>File Size:</strong> " . round($size / 1024, 2) . " KB";
                    }
                    echo "</div>";
                }
            } else {
                echo "<div class='section'><span class='error'>No photo path in database</span></div>";
            }
            ?>
        </div>

        <!-- 3. Upload Directory Contents -->
        <div class="card">
            <h2>3️⃣ Upload Directory Contents</h2>
            <?php
            $upload_dir = __DIR__ . '/uploads/school-photos';
            if (is_dir($upload_dir)) {
                $files = scandir($upload_dir);
                $photo_files = array_filter($files, function ($f) {
                    return $f !== '.' && $f !== '..'; });

                if (count($photo_files) > 0) {
                    echo "<p><strong>Files found:</strong> " . count($photo_files) . "</p>";
                    echo "<div style='margin-top: 10px;'>";
                    foreach ($photo_files as $f) {
                        $size = filesize($upload_dir . '/' . $f);
                        echo "<div class='section'>";
                        echo "<code>$f</code> (" . round($size / 1024, 2) . " KB)";
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='section'><span class='error'>No files in upload directory</span></div>";
                }
            } else {
                echo "<div class='section'><span class='error'>Upload directory doesn't exist: " . $upload_dir . "</span></div>";
            }
            ?>
        </div>

        <!-- 4. HTML Preview Test -->
        <div class="card">
            <h2>4️⃣ HTML Preview Test</h2>
            <?php
            if ($school['photo_path']) {
                // Test different HTML image paths
                $img_src = '/' . $school['photo_path'];

                echo "<p><strong>Testing img src:</strong> <code>$img_src</code></p>";
                echo "<p><strong>Full URL:</strong> <code>" . htmlspecialchars($_SERVER['HTTP_HOST']) . $img_src . "</code></p>";

                echo "<p style='margin-top: 15px;'><strong>Preview:</strong></p>";
                echo "<img src='$img_src' alt='School Photo' onerror=\"this.style.border='3px solid red'; this.title='Image failed to load';\">";

                echo "<p style='margin-top: 15px; font-size: 12px; color: #666;'>";
                echo "Jika gambar tidak muncul:<br>";
                echo "1. Check Network tab di F12 - apakah request ke foto return 404?<br>";
                echo "2. Check apakah file benar-benar ada di folder uploads<br>";
                echo "3. Check apakah path di database sesuai dengan file yang ada";
                echo "</p>";
            } else {
                echo "<div class='section'><span class='error'>No photo in database - please upload one first</span></div>";
            }
            ?>
        </div>

        <!-- 5. Settings Form Test -->
        <div class="card">
            <h2>5️⃣ Upload New Photo Here</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="photo" accept="image/*" required>
                <button type="submit"
                    style="background: #0b3d61; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px;">Upload</button>
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
                require __DIR__ . '/../src/SchoolProfileModel.php';
                $model = new SchoolProfileModel($pdo);

                try {
                    // Validate
                    $model->validatePhotoFile($_FILES['photo']);

                    // Save
                    $filename = $model->savePhotoFile($_FILES['photo']);
                    $photo_path = 'public/uploads/school-photos/' . $filename;

                    // Update DB
                    $stmt = $pdo->prepare('UPDATE schools SET photo_path = :path WHERE id = :id');
                    $stmt->execute(['path' => $photo_path, 'id' => $school_id]);

                    echo "<div class='section' style='background: #dcfce7; color: green;'>";
                    echo "✅ Upload Success!<br>";
                    echo "<strong>Filename:</strong> $filename<br>";
                    echo "<strong>Path Saved:</strong> $photo_path<br>";
                    echo "<strong>Reload page to see update</strong>";
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<div class='section' style='background: #fee2e2; color: red;'>";
                    echo "❌ Error: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
            }
            ?>
        </div>
    </div>
</body>

</html>