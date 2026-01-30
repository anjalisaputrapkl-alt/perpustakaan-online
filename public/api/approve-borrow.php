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

    if ($due_at) {
        // Validate due_at format
        $testDate = strtotime($due_at);
        if ($testDate === false) {
            error_log("[APPROVE-BORROW] Invalid due_at format: $due_at");
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Format tenggat tidak valid'
            ]);
            exit;
        }

        // Update status to borrowed AND set custom due_at
        $stmt = $pdo->prepare(
            'UPDATE borrows SET status="borrowed", due_at=:due_at
             WHERE id=:id AND school_id=:sid AND status="pending_confirmation"'
        );
        $result = $stmt->execute([
            'id' => (int) $borrow_id,
            'sid' => $sid,
            'due_at' => $due_at
        ]);
        error_log("[APPROVE-BORROW] Update with due_at executed, rows affected: " . $stmt->rowCount());
    } else {
        // Update status to borrowed (keep existing due_at)
        $stmt = $pdo->prepare(
            'UPDATE borrows SET status="borrowed"
             WHERE id=:id AND school_id=:sid AND status="pending_confirmation"'
        );
        $result = $stmt->execute([
            'id' => (int) $borrow_id,
            'sid' => $sid
        ]);
        error_log("[APPROVE-BORROW] Update without due_at executed, rows affected: " . $stmt->rowCount());
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