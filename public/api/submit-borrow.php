<?php
/**
 * Submit Borrow - Insert directly to borrows table
 * Receive array of borrow records and save to database
 */

header('Content-Type: application/json');

require __DIR__ . '/../../src/auth.php';
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$borrows = $input['borrows'] ?? [];

if (empty($borrows)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada data peminjaman'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    // Get school_id from session
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];

    $inserted = 0;
    $errors = [];

    // Start transaction
    $pdo->beginTransaction();

    foreach ($borrows as $borrow) {
        try {
            // Validate required fields
            if (empty($borrow['member_id']) || empty($borrow['book_id'])) {
                $errors[] = "Borrow record missing member_id or book_id";
                continue;
            }

            // Calculate due date (7 days from now)
            $dueDate = date('Y-m-d H:i:s', strtotime('+7 days'));

            // Insert into borrows table with pending_confirmation status
            $stmt = $pdo->prepare(
                'INSERT INTO borrows (school_id, member_id, book_id, borrowed_at, due_at, status)
                 VALUES (:school_id, :member_id, :book_id, NOW(), :due_at, "pending_confirmation")'
            );
            $stmt->execute([
                'school_id' => $school_id,
                'member_id' => (int) $borrow['member_id'],
                'book_id' => (int) $borrow['book_id'],
                'due_at' => $dueDate
            ]);

            $inserted++;
            error_log("[BORROW] Inserted: member_id=" . $borrow['member_id'] .
                ", book_id=" . $borrow['book_id']);

        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
            error_log("[BORROW] Error: " . $e->getMessage());
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'total' => count($borrows),
        'errors' => $errors,
        'message' => "$inserted dari " . count($borrows) . " peminjaman berhasil dicatat"
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("[BORROW] Database error: " . $e->getMessage());
}
?>