<?php
/**
 * Save Scan - Save scan data to session for real-time display on admin page
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['member_id']) || empty($input['book_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields',
        'received' => $input
    ]);
    exit;
}

try {
    // Initialize session array
    if (!isset($_SESSION['scan_data'])) {
        $_SESSION['scan_data'] = [];
    }

    // Add to session
    $_SESSION['scan_data'][] = [
        'member_id' => (int) $input['member_id'],
        'member_name' => $input['member_name'] ?? 'Unknown',
        'book_id' => (int) $input['book_id'],
        'book_title' => $input['book_title'] ?? 'Unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Data saved',
        'count' => count($_SESSION['scan_data']),
        'data' => $_SESSION['scan_data']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>