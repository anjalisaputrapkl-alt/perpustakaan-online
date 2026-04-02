<?php
session_start();
header('Content-Type: application/json');
$pdo = require __DIR__ . '/../../src/db.php';

// Mengecek apakah user sudah login atau belum
if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'Sesi berakhir, silakan login kembali'
    ]);
    exit;
}

// Mengambil data session user yang sedang aktif
$user = $_SESSION['user'];
$school_id = $user['school_id'];
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Memastikan parameter ID buku dikirim oleh client
if (!$book_id) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'ID Buku diperlukan'
    ]);
    exit;
}

try {
    //  Mengambil detail buku berdasarkan ID dan School_ID
    $stmt = $pdo->prepare(
        'SELECT id, title, author, category, isbn, copies, shelf, row_number, cover_image
         FROM books
         WHERE id = :id AND school_id = :school_id'
    );

    $stmt->execute([
        'id' => $book_id,
        'school_id' => $school_id
    ]);

    $book = $stmt->fetch(PDO::FETCH_ASSOC);


    //  Mengirim data jika ketemu
    if ($book) {
        echo json_encode([
            'success' => true,
            'data' => $book
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Data buku tidak ditemukan di sekolah ini'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kesalahan pada database: ' . $e->getMessage()
    ]);
}
?>