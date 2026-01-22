<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Capture any errors/warnings
ob_start();

try {
    // Test database connection
    $pdo = require __DIR__ . '/src/db.php';

    // Test EmailHelper
    require __DIR__ . '/src/EmailHelper.php';

    // Simulate a registration
    $school_name = "Test School";
    $admin_name = "Test Admin";
    $admin_email = "test@sch.id";
    $admin_password = "password123";

    // Check database connection
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users');
    $stmt->execute();
    $count = $stmt->fetchColumn();

    // Generate code
    $code = generateVerificationCode();

    // Check if functions exist
    $functions = [
        'generateVerificationCode' => function_exists('generateVerificationCode'),
        'sendVerificationEmail' => function_exists('sendVerificationEmail'),
        'isVerificationCodeExpired' => function_exists('isVerificationCodeExpired')
    ];

    // Check database columns
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    $has_is_verified = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
    $has_code = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verified_at'");
    $has_verified_at = $stmt->rowCount() > 0;

    $output = ob_get_clean();

    echo json_encode([
        'success' => true,
        'database' => [
            'connected' => true,
            'users_count' => $count,
            'columns' => [
                'is_verified' => $has_is_verified,
                'verification_code' => $has_code,
                'verified_at' => $has_verified_at
            ]
        ],
        'functions' => $functions,
        'test_code_generated' => $code,
        'php_errors' => $output
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $output = ob_get_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'php_output' => $output
    ], JSON_PRETTY_PRINT);
}
?>