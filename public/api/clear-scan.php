<?php
/**
 * Clear Scan - Clear scan data from session
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $_SESSION['scan_data'] = [];

    echo json_encode([
        'success' => true,
        'message' => 'Scan data cleared'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>