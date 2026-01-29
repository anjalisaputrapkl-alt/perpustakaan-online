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
        'SELECT id, nisn as barcode, name, "member" as type FROM members 
         WHERE nisn = ? OR id = ?
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

    // Try to find as book (ISBN)
    $stmt = $pdo->prepare(
        'SELECT id, isbn as barcode, title as name, "book" as type FROM books 
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