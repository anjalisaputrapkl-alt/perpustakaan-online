<?php

// ngasih tau ke browser kalo ini JSON
header('Content-Type: application/json');

// Periksa autentikasi pengguna
require __DIR__ . '/../../src/auth.php';
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
// Ekstrak dan bersihkan input barcode serta ambil school ID dari session
$barcode = trim($input['barcode'] ?? '');
$sid = $_SESSION['user']['school_id'];

// Validasi input barcode tidak kosong
if (!$barcode) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Barcode kosong'
    ]);
    exit;
}

try {
    // Inisialisasi koneksi database
    $pdo = require __DIR__ . '/../../src/db.php';

    // Periksa apakah barcode cocok dengan NISN atau ID anggota
    $stmt = $pdo->prepare(
        'SELECT m.id, m.nisn as barcode, m.name, m.role, m.max_pinjam, 
                s.max_books_student, s.max_books_teacher, s.max_books_employee,
                "member" as type 
         FROM members m
         JOIN schools s ON m.school_id = s.id
         WHERE (m.nisn = ? OR m.id = ?) AND m.school_id = ?
         LIMIT 1'
    );
    $stmt->execute([$barcode, $barcode, $sid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika anggota ditemukan, hitung peminjaman aktif
    if ($result) {
        // Hitung peminjaman aktif
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM borrows 
             WHERE member_id = ? AND status NOT IN ("returned")'
        );
        $countStmt->execute([$result['id']]);
        $result['current_borrow_count'] = (int) $countStmt->fetchColumn();

        // Atur batas peminjaman maksimal berdasarkan peran anggota (guru/karyawan/siswa)
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

        // Return data anggota
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        exit;
    }

    // Hapus prefix "B-" dari barcode jika ada untuk pencocokan ID buku
    $book_id_search = $barcode;
    if (strpos(strtoupper($barcode), 'B-') === 0) {
        $book_id_search = substr($barcode, 2);
    }

    // Query buku berdasarkan ISBN atau nomor ID
    $stmt = $pdo->prepare(
        'SELECT id, CONCAT("B-", id) as barcode, title as name, cover_image, copies, max_borrow_days, access_level, "book" as type FROM books 
         WHERE (isbn = ? OR id = ?) AND school_id = ?
         LIMIT 1'
    );
    $stmt->execute([$barcode, $book_id_search, $sid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika buku ditemukan, periksa apakah sedang dipinjam
    if ($result) {
        // Periksa ketersediaan buku melalui jumlah copy
        $isBorrowedByCopies = ((int) $result['copies'] < 1);

        $borrowStmt = $pdo->prepare(
            "SELECT b.id, m.name AS borrower_name
             FROM borrows b
             LEFT JOIN members m ON b.member_id = m.id
             WHERE b.book_id = ?
               AND b.status NOT IN ('returned', 'rejected')
             ORDER BY b.id DESC LIMIT 1"
        );
        $borrowStmt->execute([$result['id']]);
        // Ambil record peminjaman aktif untuk menemukan nama peminjam
        $activeBorrow = $borrowStmt->fetch(PDO::FETCH_ASSOC);
        // Periksa apakah record peminjaman ada (buku sudah dipinjam)
        $isBorrowedByRecord = ($activeBorrow !== false);

        // Gabungkan kedua pengecekan: tandai dipinjam jika copy habis ATAU peminjaman aktif ada
        $result['is_borrowed'] = $isBorrowedByCopies || $isBorrowedByRecord;
        // Tambahkan nama peminjam ke response jika tersedia
        $result['borrower_name'] = ($activeBorrow !== false) ? ($activeBorrow['borrower_name'] ?? null) : null;

        // Return data buku
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        exit;
    }

    // Return error jika barcode tidak ditemukan di anggota atau buku
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Barcode tidak ditemukan dalam database'
    ]);

    // Tangkap error database atau pemrosesan apapun
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>