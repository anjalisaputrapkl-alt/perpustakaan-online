<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Status Check</h1>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace;'>";

// 1. Check database
echo "=== DATABASE ===\n";
try {
    $pdo = require 'src/db.php';
    echo "✓ Database connected\n";

    // Check users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Users table columns:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }

    echo "\nVerification columns:\n";
    $required = ['verification_code', 'is_verified', 'verified_at'];
    foreach ($required as $col) {
        $has = in_array($col, $columns) ? "✓" : "✗";
        echo "  $has $col\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// 2. Check EmailHelper
echo "\n=== EMAIL HELPER ===\n";
try {
    require 'src/EmailHelper.php';
    echo "✓ EmailHelper loaded\n";

    $functions = ['generateVerificationCode', 'sendVerificationEmail', 'isVerificationCodeExpired'];
    foreach ($functions as $func) {
        $exists = function_exists($func) ? "✓" : "✗";
        echo "  $exists $func()\n";
    }

    // Test code generation
    $code = generateVerificationCode();
    echo "\n✓ Test code generated: $code\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// 3. Check API files
echo "\n=== API FILES ===\n";
$apis = [
    'public/api/register.php',
    'public/api/verify-email.php',
    'public/api/login.php'
];

foreach ($apis as $file) {
    $exists = file_exists($file) ? "✓" : "✗";
    echo "  $exists $file\n";
}

// 4. Check logs directory
echo "\n=== LOGS ===\n";
$log_dir = 'logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
    echo "✓ Created logs directory\n";
} else {
    echo "✓ Logs directory exists\n";
}

if (file_exists('logs/emails.log')) {
    $size = filesize('logs/emails.log');
    echo "✓ emails.log exists ({$size} bytes)\n";
} else {
    echo "ℹ emails.log not yet created\n";
}

echo "\n=== READY ===\n";
echo "✓ System is ready for registration testing\n";
echo "</pre>";
?>