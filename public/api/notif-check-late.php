<?php
/**
 * API Endpoint: notif-check-late.php
 * 
 * Check & create late_warning notifikasi otomatis
 * Bisa dijalankan via cron atau manual trigger
 * 
 * GET/POST Parameters:
 * - student_id: optional, jika kosong check semua siswa
 * 
 * Response: {success: bool, count: int, message: string}
 */

header('Content-Type: application/json');
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsHelper.php';

// Security check - untuk cron/admin only, bisa ganti dengan API key
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    // Uncomment untuk require login, comment untuk allow cron
    // http_response_code(401);
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}

try {
    $school_id = $_SESSION['user']['school_id'] ?? $_GET['school_id'] ?? null;
    $student_id = $_GET['student_id'] ?? $_POST['student_id'] ?? null;

    if (!$school_id) {
        throw new Exception('school_id diperlukan');
    }

    $helper = new NotificationsHelper($pdo);
    
    // Check late warnings
    $count = $helper->checkAndCreateLateWarnings($school_id, $student_id);

    echo json_encode([
        'success' => true,
        'count' => $count,
        'message' => $count . ' late warning notifikasi berhasil dibuat'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
