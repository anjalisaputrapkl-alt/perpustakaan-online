<?php
/**
 * Approve Borrow - Change status from pending_confirmation to borrowed
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

    // Get optional custom due_at date
    $due_at = $_POST['due_at'] ?? null;

    error_log("[APPROVE-BORROW] borrow_id=$borrow_id, due_at=$due_at, school_id=$sid");

    // 1. Check if book is still available
    $checkStmt = $pdo->prepare(
        'SELECT b.book_id, bk.copies FROM borrows b
         JOIN books bk ON b.book_id = bk.id
         WHERE b.id = :id AND b.school_id = :sid'
    );
    $checkStmt->execute(['id' => (int) $borrow_id, 'sid' => $sid]);
    $bookData = $checkStmt->fetch();

    if (!$bookData) {
        throw new Exception('Data peminjaman tidak ditemukan');
    }

    if ($bookData['copies'] < 1) {
        throw new Exception('Gagal: Buku sudah dipinjam oleh orang lain (Stok 0)');
    }

    if ($due_at) {
        // Update status AND set custom due_at
        $stmt = $pdo->prepare(
            'UPDATE borrows SET status="borrowed", due_at=:due_at
             WHERE id=:id AND school_id=:sid AND status="pending_confirmation"'
        );
        $result = $stmt->execute([
            'id' => (int) $borrow_id,
            'sid' => $sid,
            'due_at' => $due_at
        ]);
    } else {
        // Update status
        $stmt = $pdo->prepare(
            'UPDATE borrows SET status="borrowed"
             WHERE id=:id AND school_id=:sid AND status="pending_confirmation"'
        );
        $result = $stmt->execute([
            'id' => (int) $borrow_id,
            'sid' => $sid
        ]);
    }

    if ($stmt->rowCount() > 0) {
        // DECREMENT STOCK (Set to 0)
        $updateStock = $pdo->prepare('UPDATE books SET copies = 0 WHERE id = :bid');
        $updateStock->execute(['bid' => $bookData['book_id']]);
        
        error_log("[APPROVE-BORROW] Stock set to 0 for book_id=" . $bookData['book_id']);
    }

    if ($stmt->rowCount() === 0) {
        error_log("[APPROVE-BORROW] No rows affected - record not found or already processed");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Peminjaman tidak ditemukan atau sudah diproses'
        ]);
        exit;
    }

    error_log("[APPROVE-BORROW] Success!");

    echo json_encode([
        'success' => true,
        'message' => 'Peminjaman telah diterima'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>