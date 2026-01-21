<?php
/**
 * Database Migration Runner
 * Jalankan dari browser untuk execute migration
 */

// Prevent direct execution in console
if (php_sapi_name() === 'cli') {
    die("❌ Hanya bisa dijalankan dari browser.\n");
}

echo "<pre style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

try {
    // Connect to database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=perpustakaan_online',
        'root',
        ''
    );

    echo "✅ Connected to database\n\n";

    // 1. Check if column exists, if not add it
    $stmt = $pdo->query("SHOW COLUMNS FROM schools LIKE 'photo_path'");
    if ($stmt->rowCount() === 0) {
        echo "⏳ Adding photo_path column...\n";
        $pdo->exec("ALTER TABLE schools ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL AFTER website");
        echo "✅ photo_path column added\n\n";
    } else {
        echo "ℹ️  photo_path column already exists\n\n";
    }

    // 2. Check if year_founded exists and rename to founded_year if needed
    $stmt = $pdo->query("SHOW COLUMNS FROM schools LIKE 'year_founded'");
    if ($stmt->rowCount() > 0) {
        // Check if founded_year doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM schools LIKE 'founded_year'");
        if ($stmt->rowCount() === 0) {
            echo "⏳ Renaming year_founded to founded_year...\n";
            $pdo->exec("ALTER TABLE schools CHANGE COLUMN year_founded founded_year INT(11) DEFAULT NULL");
            echo "✅ Column renamed: year_founded → founded_year\n\n";
        } else {
            echo "ℹ️  founded_year already exists, dropping year_founded...\n";
            $pdo->exec("ALTER TABLE schools DROP COLUMN year_founded");
            echo "✅ year_founded dropped\n\n";
        }
    } else {
        $stmt = $pdo->query("SHOW COLUMNS FROM schools LIKE 'founded_year'");
        if ($stmt->rowCount() === 0) {
            echo "⏳ Adding founded_year column...\n";
            $pdo->exec("ALTER TABLE schools ADD COLUMN founded_year INT(11) DEFAULT NULL AFTER website");
            echo "✅ founded_year column added\n\n";
        } else {
            echo "ℹ️  founded_year column already exists\n\n";
        }
    }

    // 3. Show final schema
    echo "═══════════════════════════════════════════\n";
    echo "📋 Current Schools Table Schema:\n";
    echo "═══════════════════════════════════════════\n\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM schools");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        $type = $col['Type'];
        $null = $col['Null'] === 'YES' ? '(nullable)' : '(not null)';
        printf("%-20s %-30s %s\n", $col['Field'], $type, $null);
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
