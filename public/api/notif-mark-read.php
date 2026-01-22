<?php
/**
 * API Endpoint: notif-mark-read.php
 * 
 * Mark notifikasi sebagai dibaca
 * 
 * POST Parameters:
 * - notification_id: ID notifikasi (required)
 * - mark_all: 1 untuk mark semua (optional, ignore notification_id jika ada)
 * 
 * Response: {success: bool, message: string}
 */

header('Content-Type: application/json');
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsHelper.php';

// Security check
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$student_id = $user['id'];

try {
    $helper = new NotificationsHelper($pdo);

    $mark_all = (int)($_POST['mark_all'] ?? 0);
    $notification_id = (int)($_POST['notification_id'] ?? 0);

    if ($mark_all) {
        // Mark all as read
        if ($helper->markAllAsRead($school_id, $student_id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Semua notifikasi telah ditandai sebagai dibaca'
            ]);
        } else {
            throw new Exception('Gagal menandai notifikasi');
        }
    } else {
        // Mark single notification
        if (!$notification_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'notification_id diperlukan']);
            exit;
        }

        if ($helper->markAsRead($school_id, $notification_id, $student_id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Notifikasi telah ditandai sebagai dibaca'
            ]);
        } else {
            throw new Exception('Gagal menandai notifikasi');
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
