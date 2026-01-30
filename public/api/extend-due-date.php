<?php
/**
 * Extend Due Date - Perpanjang tenggat waktu peminjaman
 */

header('Content-Type: application/json');

require __DIR__ . '/../../src/auth.php';
requireAuth();

$borrow_id = $_POST['borrow_id'] ?? null;
$extend_days = (int) ($_POST['extend_days'] ?? 0);

if (!$borrow_id || $extend_days < 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Borrow ID atau jumlah hari perpanjangan tidak valid'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';
    $user = $_SESSION['user'];
    $sid = $user['school_id'];

    // Validate that the borrow belongs to this school
    $stmt = $pdo->prepare(
        'SELECT b.*, bk.title, m.name AS member_name 
         FROM borrows b
         JOIN books bk ON b.book_id = bk.id
         JOIN members m ON b.member_id = m.id
         WHERE b.id = :id AND b.school_id = :sid'
    );
    $stmt->execute(['id' => $borrow_id, 'sid' => $sid]);
    $borrow = $stmt->fetch();

    if (!$borrow) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Peminjaman tidak ditemukan'
        ]);
        exit;
    }

    // Verify that the borrow is still active (not returned)
    if ($borrow['status'] === 'returned' || $borrow['status'] === 'pending_return') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tidak dapat memperpanjang peminjaman yang sudah dikembalikan'
        ]);
        exit;
    }

    // Calculate new due date
    $currentDueDate = new DateTime($borrow['due_at']);
    $currentDueDate->modify("+{$extend_days} days");
    $newDueDate = $currentDueDate->format('Y-m-d H:i:s');

    // Update the due_at in database
    $updateStmt = $pdo->prepare(
        'UPDATE borrows SET due_at = :new_due_at
         WHERE id = :id AND school_id = :sid'
    );
    $updateStmt->execute([
        'new_due_at' => $newDueDate,
        'id' => $borrow_id,
        'sid' => $sid
    ]);

    // Log the action
    error_log("[EXTEND-DUE-DATE] borrow_id=$borrow_id, extend_days=$extend_days, new_due_date=$newDueDate, school_id=$sid");

    echo json_encode([
        'success' => true,
        'message' => 'Tenggat waktu telah diperpanjang',
        'new_due_date' => date('d/m/Y', strtotime($newDueDate)),
        'book_title' => $borrow['title'],
        'member_name' => $borrow['member_name']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("[EXTEND-DUE-DATE-ERROR] " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>