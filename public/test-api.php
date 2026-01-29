<?php
/**
 * Simple API endpoint tester
 */

if (php_sapi_name() === 'cli') {
    echo "API Endpoint Test\n";
    echo "==================\n\n";

    // Check if approve-borrow.php exists
    $files = [
        'api/approve-borrow.php',
        'api/reject-borrow.php',
        'api/submit-borrow.php'
    ];

    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo "✓ $file exists\n";
            echo "  Size: " . filesize($path) . " bytes\n";
            echo "  Last modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
        } else {
            echo "✗ $file NOT FOUND\n";
        }
    }

    echo "\nCheck error_log location:\n";
    echo "  " . (ini_get('error_log') ?: 'No error_log configured') . "\n";

    echo "\nFunctions in borrows.php:\n";
    $content = file_get_contents(__DIR__ . '/borrows.php');
    $functions = [];
    preg_match_all('/function\s+(\w+)\s*\(/', $content, $matches);
    foreach ($matches[1] as $func) {
        echo "  - $func\n";
    }
} else {
    // Web mode - show as HTML
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>API Test</title>
        <style>
            body {
                font-family: monospace;
                margin: 20px;
            }

            .ok {
                color: green;
            }

            .error {
                color: red;
            }

            pre {
                background: #f0f0f0;
                padding: 10px;
                border-radius: 4px;
            }
        </style>
    </head>

    <body>
        <h1>API Endpoint Status</h1>
        <?php
        $files = [
            'api/approve-borrow.php',
            'api/reject-borrow.php',
            'api/submit-borrow.php',
        ];

        foreach ($files as $file) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                echo "<p class=\"ok\">✓ $file exists</p>";
                echo "<pre>" . htmlspecialchars(file_get_contents($path), 0, 'UTF-8', false) . "</pre>";
            } else {
                echo "<p class=\"error\">✗ $file NOT FOUND</p>";
            }
        }
        ?>
    </body>

    </html>
    <?php
}
