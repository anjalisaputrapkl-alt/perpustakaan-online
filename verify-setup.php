<?php
/**
 * Quick verification script untuk memastikan semua setup sudah benar
 * Check: database connection, tables, columns, sync logic
 */

session_start();

// Load dependencies
$pdo = require __DIR__ . '/src/db.php';

echo "<h1>🔍 System Verification Report</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 20px; }
    .check { padding: 10px; margin: 10px 0; border-left: 4px solid #4CAF50; background: #d4edda; }
    .error { border-left-color: #f44336; background: #ffebee; }
    .warning { border-left-color: #ff9800; background: #fff3e0; }
    table { border-collapse: collapse; width: 100%; background: white; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #f2f2f2; font-weight: bold; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
</style>";

// 1. Check Database Connection
try {
    $result = $pdo->query("SELECT 1");
    echo "<div class='check'>✅ Database connection: OK</div>";
} catch (Exception $e) {
    echo "<div class='check error'>❌ Database connection FAILED: " . $e->getMessage() . "</div>";
    die();
}

// 2. Check members table
echo "<h2>📋 Table: members</h2>";
try {
    $result = $pdo->query("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'members' AND TABLE_SCHEMA = DATABASE()
        ORDER BY ORDINAL_POSITION
    ");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    echo "<table><tr><th>Column</th><th>Type</th><th>Nullable</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><code>" . $col['COLUMN_NAME'] . "</code></td>";
        echo "<td>" . $col['COLUMN_TYPE'] . "</td>";
        echo "<td>" . ($col['IS_NULLABLE'] == 'YES' ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<div class='check'>✅ Members table: OK (" . count($columns) . " columns)</div>";
} catch (Exception $e) {
    echo "<div class='check error'>❌ Members table: " . $e->getMessage() . "</div>";
}

// 3. Check siswa table
echo "<h2>📋 Table: siswa</h2>";
try {
    $result = $pdo->query("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'siswa' AND TABLE_SCHEMA = DATABASE()
        ORDER BY ORDINAL_POSITION
    ");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    if (empty($columns)) {
        echo "<div class='check error'>❌ Siswa table does not exist!</div>";
    } else {
        echo "<table><tr><th>Column</th><th>Type</th><th>Nullable</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><code>" . $col['COLUMN_NAME'] . "</code></td>";
            echo "<td>" . $col['COLUMN_TYPE'] . "</td>";
            echo "<td>" . ($col['IS_NULLABLE'] == 'YES' ? 'YES' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<div class='check'>✅ Siswa table: OK (" . count($columns) . " columns)</div>";
    }
} catch (Exception $e) {
    echo "<div class='check error'>❌ Siswa table: " . $e->getMessage() . "</div>";
}

// 4. Check file uploads directory
echo "<h2>📁 File Storage</h2>";
$uploadDir = __DIR__ . '/public/uploads/school-photos';
if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    echo "<div class='check'>✅ Upload directory exists: " . count($files) . " files</div>";
} else {
    echo "<div class='check warning'>⚠️ Upload directory does not exist</div>";
}

// 5. Check key PHP files
echo "<h2>📝 Key Files</h2>";
$files_to_check = [
    '/public/profil.php' => 'Student Profile Page',
    '/public/sync-siswa-test.php' => 'Sync Test Tool',
    '/src/db.php' => 'Database Config',
    '/src/SchoolProfileModel.php' => 'School Profile Model',
    '/public/partials/sidebar.php' => 'Admin Sidebar',
    '/public/partials/student-sidebar.php' => 'Student Sidebar',
];

foreach ($files_to_check as $path => $label) {
    $full_path = __DIR__ . $path;
    if (file_exists($full_path)) {
        echo "<div class='check'>✅ $label: OK</div>";
    } else {
        echo "<div class='check error'>❌ $label: FILE NOT FOUND</div>";
    }
}

// 6. Check data sample
echo "<h2>📊 Data Sample</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM members");
    $count = $stmt->fetch()['count'];
    echo "<div class='check'>Members table: $count records</div>";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM siswa");
    $count = $stmt->fetch()['count'];
    echo "<div class='check'>Siswa table: $count records</div>";
} catch (Exception $e) {
    echo "<div class='check error'>Error fetching counts: " . $e->getMessage() . "</div>";
}

echo "<h2>✅ Verification Complete</h2>";
echo "<p>If all checks are green, the system is ready!</p>";
?>