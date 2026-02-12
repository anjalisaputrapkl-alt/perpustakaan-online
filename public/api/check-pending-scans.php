<?php
/**
 * Check Pending Scans API
 * Returns the count and IDs of current pending_confirmation records
 */

header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';
    $school_id = $_SESSION['user']['school_id'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status = "pending_confirmation"');
    $stmt->execute(['sid' => $school_id]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'count' => $count
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
