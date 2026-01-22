<?php
/**
 * API Endpoint: notif-fetch.php
 * 
 * Fetch notifikasi dengan filter optional
 * 
 * GET Parameters:
 * - type: filter by type (borrow, return_request, return_confirm, late_warning, info, new_book)
 * - limit: jumlah data (default 10)
 * - offset: pagination offset (default 0)
 * - unread_only: 1 untuk hanya unread (default 0)
 * 
 * Response: {success: bool, data: [], stats: {}}
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

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$student_id = $user['id'];

try {
    $helper = new NotificationsHelper($pdo);

    // Get parameters
    $type = $_GET['type'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 10), 50); // Max 50
    $offset = (int)($_GET['offset'] ?? 0);
    $unread_only = (int)($_GET['unread_only'] ?? 0);

    // Build query
    $query = 'SELECT * FROM notifications 
             WHERE school_id = :school_id AND student_id = :student_id';
    $params = [
        ':school_id' => $school_id,
        ':student_id' => $student_id
    ];

    if ($type) {
        $query .= ' AND type = :type';
        $params[':type'] = $type;
    }

    if ($unread_only) {
        $query .= ' AND is_read = 0';
    }

    $query .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    foreach ($params as $key => $value) {
        if (strpos($key, ':limit') === false && strpos($key, ':offset') === false) {
            $stmt->bindValue($key, $value);
        }
    }

    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats = $helper->getStatistics($school_id, $student_id);

    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'stats' => $stats,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
