<?php
/**
 * Fix Existing Photo Paths in Database
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$is_admin || !$school_id) {
    die('❌ Admin access required');
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Fix Photo Paths</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 20px;
        }

        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #0b3d61;
        }

        code {
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
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

        button {
            background: #0b3d61;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div style="max-width: 800px; margin: 0 auto;">
        <h1>🔧 Fix Photo Paths</h1>

        <div class="box">
            <h2>Current Status</h2>
            <?php
            $stmt = $pdo->prepare('SELECT id, name, photo_path FROM schools WHERE photo_path IS NOT NULL');
            $stmt->execute();
            $schools = $stmt->fetchAll();

            if (count($schools) > 0) {
                echo "<p>Schools dengan photo: " . count($schools) . "</p>";
                foreach ($schools as $school) {
                    $path = $school['photo_path'];
                    $needs_fix = strpos($path, 'public/') !== 0;
                    $status = $needs_fix ? '⚠️ NEEDS FIX' : '✅ OK';
                    echo "<p><strong>{$school['name']}</strong> - $status<br>";
                    echo "<code>$path</code></p>";
                }
            } else {
                echo "<p>No schools with photos found</p>";
            }
            ?>
        </div>

        <div class="box">
            <h2>Fix All Paths</h2>
            <p>This will update all photo paths that don't start with 'public/' to add it.</p>

            <form method="POST">
                <button type="submit" name="action" value="fix_paths">Run Fix</button>
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'fix_paths') {
                $stmt = $pdo->prepare('SELECT id, photo_path FROM schools WHERE photo_path IS NOT NULL');
                $stmt->execute();
                $schools = $stmt->fetchAll();

                $updated = 0;
                foreach ($schools as $school) {
                    $path = $school['photo_path'];
                    if (strpos($path, 'public/') !== 0) {
                        // Needs fix
                        $new_path = 'public/' . $path;
                        $stmt = $pdo->prepare('UPDATE schools SET photo_path = :path WHERE id = :id');
                        $stmt->execute(['path' => $new_path, 'id' => $school['id']]);
                        $updated++;
                    }
                }

                echo "<div class='success'>✅ Fixed $updated photo paths</div>";
                echo "<div class='info'>Reload dashboard to see photos appear!</div>";
            }
            ?>
        </div>

        <div class="box">
            <h2>Next Steps</h2>
            <ol>
                <li>Click "Run Fix" button above to update all existing paths</li>
                <li>Upload a new photo from Settings to test</li>
                <li>Reload dashboard - photo should appear in sidebar</li>
                <li>Clear browser cache if still not showing: Ctrl+Shift+Delete</li>
            </ol>
        </div>
    </div>
</body>

</html>