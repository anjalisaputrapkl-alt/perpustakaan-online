<?php
/**
 * Process Barcode
 * Sederhana - terima barcode, return data anggota atau buku
 */

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$barcode = trim($input['barcode'] ?? '');

if (!$barcode) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Barcode kosong'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    // Try to find as member (NISN)
    $stmt = $pdo->prepare(
        'SELECT m.id, m.nisn as barcode, m.name, m.role, m.max_pinjam, 
                s.max_books_student, s.max_books_teacher, s.max_books_employee,
                "member" as type 
         FROM members m
         JOIN schools s ON m.school_id = s.id
         WHERE (m.nisn = ? OR m.id = ?)
         LIMIT 1'
    );
    $stmt->execute([$barcode, $barcode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Count active borrows
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM borrows 
             WHERE member_id = ? AND status NOT IN ("returned")'
        );
        $countStmt->execute([$result['id']]);
        $result['current_borrow_count'] = (int) $countStmt->fetchColumn();
        
        // Determine dynamic max_pinjam
        if (empty($result['max_pinjam'])) {
            $role = $result['role'] ?? 'student';
            if ($role === 'teacher') {
                $result['max_pinjam'] = (int) ($result['max_books_teacher'] ?? 10);
            } elseif ($role === 'employee') {
                $result['max_pinjam'] = (int) ($result['max_books_employee'] ?? 5);
            } else {
                $result['max_pinjam'] = (int) ($result['max_books_student'] ?? 3);
            }
        } else {
            $result['max_pinjam'] = (int) $result['max_pinjam'];
        }

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, isbn as barcode, title as name, cover_image, copies, max_borrow_days, access_level, "book" as type FROM books 
         WHERE isbn = ? OR id = ?
         LIMIT 1'
    );
    $stmt->execute([$barcode, $barcode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        exit;
    }

    // Not found
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Barcode tidak ditemukan dalam database'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>