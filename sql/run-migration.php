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

    // 3. Email Verification columns migration
    echo "═══════════════════════════════════════════\n";
    echo "📧 Email Verification System Migration\n";
    echo "═══════════════════════════════════════════\n\n";

    // Check verification_code column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
    if ($stmt->rowCount() === 0) {
        echo "⏳ Adding verification_code column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) NULL AFTER password");
        echo "✅ verification_code column added\n";
    } else {
        echo "ℹ️  verification_code column already exists\n";
    }

    // Check is_verified column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($stmt->rowCount() === 0) {
        echo "⏳ Adding is_verified column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER verification_code");
        echo "✅ is_verified column added\n";
    } else {
        echo "ℹ️  is_verified column already exists\n";
    }

    // Check verified_at column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verified_at'");
    if ($stmt->rowCount() === 0) {
        echo "⏳ Adding verified_at column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN verified_at TIMESTAMP NULL AFTER is_verified");
        echo "✅ verified_at column added\n";
    } else {
        echo "ℹ️  verified_at column already exists\n";
    }

    // Add index for verification_code
    $indexes = $pdo->query("SHOW INDEX FROM users WHERE Column_name='verification_code'")->fetchAll();
    if (empty($indexes)) {
        echo "⏳ Adding index for verification_code...\n";
        $pdo->exec("ALTER TABLE users ADD INDEX idx_verification_code (verification_code)");
        echo "✅ Index added for verification_code\n";
    } else {
        echo "ℹ️  Index for verification_code already exists\n";
    }

    // Add index for is_verified
    $indexes = $pdo->query("SHOW INDEX FROM users WHERE Column_name='is_verified'")->fetchAll();
    if (empty($indexes)) {
        echo "⏳ Adding index for is_verified...\n";
        $pdo->exec("ALTER TABLE users ADD INDEX idx_is_verified (is_verified)");
        echo "✅ Index added for is_verified\n";
    } else {
        echo "ℹ️  Index for is_verified already exists\n";
    }

    echo "\n";

    // 4. Show final schema
    echo "═══════════════════════════════════════════\n";
    echo "📋 Current Users Table Schema:\n";
    echo "═══════════════════════════════════════════\n\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        $type = $col['Type'];
        $null = $col['Null'] === 'YES' ? '(nullable)' : '(not null)';
        printf("%-20s %-30s %s\n", $col['Field'], $type, $null);
    }

    echo "\n✅ All migrations completed successfully!\n";
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>