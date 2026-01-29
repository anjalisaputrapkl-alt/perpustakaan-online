<?php
/**
 * Get Scan - Retrieve scan data from session
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $scanData = $_SESSION['scan_data'] ?? [];

    echo json_encode([
        'success' => true,
        'data' => $scanData,
        'count' => count($scanData)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>