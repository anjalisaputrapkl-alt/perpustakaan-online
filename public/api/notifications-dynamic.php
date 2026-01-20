<?php
session_start();
$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/NotificationsService.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = $_SESSION['user']['id'];
$action = $_GET['action'] ?? 'list';

try {
    $service = new NotificationsService($pdo);

    switch ($action) {
        case 'list':
            // Ambil semua notifikasi
            $sort = $_GET['sort'] ?? 'latest';
            $notifications = $service->getAllNotifications($studentId);

            // Sort berdasarkan parameter
            if ($sort === 'oldest') {
                $notifications = array_reverse($notifications);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'total' => count($notifications),
                'unread' => count(array_filter($notifications, fn($n) => !$n['status_baca']))
            ]);
            break;

        case 'stats':
            // Ambil statistik
            $stats = $service->getStatistics($studentId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        case 'unread_count':
            // Hitung belum dibaca
            $notifications = $service->getAllNotifications($studentId);
            $unreadCount = count(array_filter($notifications, fn($n) => !$n['status_baca']));
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => htmlspecialchars($e->getMessage())
    ]);
}
?>
