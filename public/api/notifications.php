<?php
/**
 * API Notifikasi Siswa
 * Endpoint untuk operasi notifikasi
 */

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/NotificationsModel.php';

// Cek authentikasi
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Silakan login terlebih dahulu'
    ]);
    exit;
}

$studentId = $_SESSION['user']['id'] ?? null;
$action = $_GET['action'] ?? '';
$notificationId = $_GET['id'] ?? '';

// Validasi student ID
if (!$studentId || !is_numeric($studentId)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Student ID tidak valid'
    ]);
    exit;
}

try {
    $model = new NotificationsModel($pdo);

    switch ($action) {
        /**
         * GET /api/notifications.php?action=list&sort=latest
         * Ambil semua notifikasi siswa
         */
        case 'list':
            $sort = $_GET['sort'] ?? 'latest';
            $validSorts = ['latest', 'oldest', 'unread'];
            $sort = in_array($sort, $validSorts) ? $sort : 'latest';

            $notifications = $model->getNotifications($studentId, $sort);
            $stats = $model->getStatistics($studentId);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'stats' => $stats,
                'total' => count($notifications)
            ]);
            break;

        /**
         * GET /api/notifications.php?action=detail&id=1
         * Ambil detail notifikasi spesifik
         */
        case 'detail':
            if (!$notificationId || !is_numeric($notificationId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Bad Request: ID notifikasi tidak valid'
                ]);
                exit;
            }

            $notification = $model->getNotificationDetail($notificationId, $studentId);
            
            // Update status baca otomatis
            $model->updateNotificationStatus($notificationId, $studentId, 1);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $notification
            ]);
            break;

        /**
         * POST /api/notifications.php?action=mark_read
         * Tandai notifikasi sebagai dibaca
         */
        case 'mark_read':
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Bad Request: ID notifikasi tidak valid'
                ]);
                exit;
            }

            $updated = $model->updateNotificationStatus($_POST['id'], $studentId, 1);

            if ($updated) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Notifikasi ditandai sebagai dibaca'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan'
                ]);
            }
            break;

        /**
         * POST /api/notifications.php?action=mark_all_read
         * Tandai semua notifikasi sebagai dibaca
         */
        case 'mark_all_read':
            $model->markAllAsRead($studentId);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Semua notifikasi ditandai sebagai dibaca'
            ]);
            break;

        /**
         * POST /api/notifications.php?action=delete
         * Hapus notifikasi
         */
        case 'delete':
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Bad Request: ID notifikasi tidak valid'
                ]);
                exit;
            }

            $deleted = $model->deleteNotification($_POST['id'], $studentId);

            if ($deleted) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Notifikasi dihapus'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan'
                ]);
            }
            break;

        /**
         * GET /api/notifications.php?action=unread_count
         * Hitung notifikasi belum dibaca
         */
        case 'unread_count':
            $count = $model->countUnread($studentId);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'unread_count' => $count
            ]);
            break;

        /**
         * GET /api/notifications.php?action=stats
         * Ambil statistik notifikasi
         */
        case 'stats':
            $stats = $model->getStatistics($studentId);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Bad Request: Action tidak dikenal',
                'available_actions' => [
                    'list' => 'GET - Ambil daftar notifikasi',
                    'detail' => 'GET - Ambil detail notifikasi',
                    'mark_read' => 'POST - Tandai sebagai dibaca',
                    'mark_all_read' => 'POST - Tandai semua sebagai dibaca',
                    'delete' => 'POST - Hapus notifikasi',
                    'unread_count' => 'GET - Hitung notifikasi belum dibaca',
                    'stats' => 'GET - Ambil statistik'
                ]
            ]);
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
