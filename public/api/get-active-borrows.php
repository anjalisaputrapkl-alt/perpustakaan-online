<?php
// API endpoint untuk mendapatkan semua buku yang belum kembali (active borrows)
require_once '../../src/config.php';
require_once '../../src/db.php';
require_once '../../src/auth.php';

// Validasi autentikasi pengguna
requireAuth();

try {
    // Ambil school_id dari session
    $schoolId = $_SESSION['user']['school_id'];

    // Query untuk mendapatkan semua buku yang masih dipinjam dengan status 'borrowed' atau 'overdue'
    $query = "
        SELECT 
            b.id AS borrow_id,
            bk.title AS book_title,
            m.name AS member_name,
            b.borrowed_at,
            b.due_at,
            b.status,
            DATEDIFF(NOW(), b.borrowed_at) AS days_borrowed,
            DATEDIFF(b.due_at, NOW()) AS days_until_due
        FROM borrows b
        JOIN books bk ON b.book_id = bk.id
        JOIN members m ON b.member_id = m.id
        WHERE b.school_id = :school_id 
            AND b.status IN ('borrowed', 'overdue')
        ORDER BY b.due_at ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['school_id' => $schoolId]);
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Response JSON success
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'borrows' => $borrows,
        'count' => count($borrows)
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