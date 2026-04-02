<?php
// API endpoint untuk mendapatkan semua pengembalian buku dalam sesi ini
require_once '../../src/config.php';
require_once '../../src/db.php';
require_once '../../src/auth.php';

// Validasi autentikasi pengguna
requireAuth();

try {
    // Ambil school_id dari session
    $schoolId = $_SESSION['user']['school_id'];

    // Ambil session ID atau timestamp awal sesi (Anda bisa menyesuaikan definisi "sesi" sesuai kebutuhan)
    // Asumsi: Sesi dimulai dari jam 00:00 hari ini
    $sessionStart = date('Y-m-d 00:00:00');
    $sessionEnd = date('Y-m-d H:i:s');

    // Query untuk mendapatkan semua pengembalian dalam sesi ini dengan status 'returned'
    $query = "
        SELECT 
            b.id AS borrow_id,
            bk.title AS book_title,
            m.name AS member_name,
            b.borrowed_at,
            b.returned_at,
            b.status,
            b.fine_amount
        FROM borrows b
        JOIN books bk ON b.book_id = bk.id
        JOIN members m ON b.member_id = m.id
        WHERE b.school_id = :school_id 
            AND b.status = 'returned'
            AND b.returned_at >= :session_start
            AND b.returned_at <= :session_end
        ORDER BY b.returned_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'school_id' => $schoolId,
        'session_start' => $sessionStart,
        'session_end' => $sessionEnd
    ]);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Response JSON success
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'returns' => $returns,
        'count' => count($returns),
        'session_start' => $sessionStart,
        'session_end' => $sessionEnd
    ]);

} catch (Exception $e) {
    // Response JSON error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>