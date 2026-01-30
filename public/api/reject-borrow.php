<?php
/**
 * Reject Borrow - Delete the pending borrow record
 */

header('Content-Type: application/json');

require __DIR__ . '/../../src/auth.php';
requireAuth();

$borrow_id = $_POST['borrow_id'] ?? null;

if (!$borrow_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Borrow ID tidak ditemukan'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';
    $user = $_SESSION['user'];
    $sid = $user['school_id'];

    error_log("[REJECT-BORROW] borrow_id=$borrow_id, school_id=$sid");

    // Delete the pending borrow record
    $stmt = $pdo->prepare(
        'DELETE FROM borrows
         WHERE id=:id AND school_id=:sid AND status="pending_confirmation"'
    );
    $stmt->execute([
        'id' => (int) $borrow_id,
        'sid' => $sid
    ]);

    error_log("[REJECT-BORROW] Delete executed, rows affected: " . $stmt->rowCount());

    if ($stmt->rowCount() === 0) {
        error_log("[REJECT-BORROW] No rows affected - record not found or already processed");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Peminjaman tidak ditemukan atau sudah diproses'
        ]);
        exit;
    }

    error_log("[REJECT-BORROW] Success!");
    echo json_encode([
        'success' => true,
        'message' => 'Peminjaman telah ditolak'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>