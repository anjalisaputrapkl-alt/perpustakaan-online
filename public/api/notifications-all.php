<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/NotificationsService.php';

try {
    $service = new NotificationsService($pdo);
    $studentId = $_SESSION['user']['id'];
    
    // Get ALL notifications without filtering
    $notifications = $service->getAllNotifications($studentId);
    
    echo json_encode([
        'success' => true,
        'data' => $notifications
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
