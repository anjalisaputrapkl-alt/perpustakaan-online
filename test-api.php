<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Register API</h2>";
echo "<pre>";

// Test if files exist
echo "1. Checking files...\n";
echo "EmailHelper: " . (file_exists('src/EmailHelper.php') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "db.php: " . (file_exists('src/db.php') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "register.php: " . (file_exists('public/api/register.php') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "\n";

// Test includes
echo "2. Testing includes...\n";
try {
    $pdo = require 'src/db.php';
    echo "✓ db.php loaded successfully\n";
    echo "PDO instance: " . get_class($pdo) . "\n";
} catch (Exception $e) {
    echo "✗ Failed to load db.php: " . $e->getMessage() . "\n";
}

echo "\n";

// Test EmailHelper
echo "3. Testing EmailHelper...\n";
try {
    require 'src/EmailHelper.php';
    echo "✓ EmailHelper.php loaded successfully\n";

    // Test functions
    if (function_exists('generateVerificationCode')) {
        $code = generateVerificationCode();
        echo "✓ generateVerificationCode() works: $code\n";
    } else {
        echo "✗ generateVerificationCode() not found\n";
    }

    if (function_exists('sendVerificationEmail')) {
        echo "✓ sendVerificationEmail() function exists\n";
    } else {
        echo "✗ sendVerificationEmail() not found\n";
    }
} catch (Exception $e) {
    echo "✗ Failed to load EmailHelper.php: " . $e->getMessage() . "\n";
}

echo "\n";

// Test database connection
echo "4. Testing database...\n";
try {
    $pdo = require 'src/db.php';

    // Check users table columns
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Users table columns:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }

    // Check specific verification columns
    echo "\nVerification columns check:\n";
    $required = ['verification_code', 'is_verified', 'verified_at'];
    foreach ($required as $col) {
        $has = in_array($col, $columns) ? "✓" : "✗";
        echo "  $has $col\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>