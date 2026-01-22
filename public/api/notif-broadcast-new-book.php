<?php
/**
 * API Endpoint: notif-broadcast-new-book.php
 * 
 * Broadcast notifikasi ke semua siswa saat admin menambah buku baru
 * 
 * POST Parameters:
 * - book_id: ID buku yang baru ditambahkan
 * - book_title: Judul buku (optional, akan fetch dari DB jika kosong)
 * 
 * Response: {success: bool, count: int, message: string}
 */

header('Content-Type: application/json');
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsHelper.php';

// Security check - admin only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $book_title = $_POST['book_title'] ?? null;

    if ($book_id <= 0) {
        throw new Exception('book_id tidak valid');
    }

    // Get book info jika title belum provided
    if (!$book_title) {
        $bookStmt = $pdo->prepare('SELECT title FROM books WHERE id = :book_id AND school_id = :school_id');
        $bookStmt->execute([':book_id' => $book_id, ':school_id' => $school_id]);
        $book = $bookStmt->fetch();
        
        if (!$book) {
            throw new Exception('Buku tidak ditemukan');
        }
        $book_title = $book['title'];
    }

    // Get all active students in this school
    $studentStmt = $pdo->prepare(
        'SELECT id FROM users WHERE school_id = :school_id AND role = "student"'
    );
    $studentStmt->execute([':school_id' => $school_id]);
    $students = $studentStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($students)) {
        echo json_encode([
            'success' => true,
            'count' => 0,
            'message' => 'Tidak ada siswa untuk dikirim notifikasi'
        ]);
        exit;
    }

    // Broadcast notification
    $helper = new NotificationsHelper($pdo);
    $message = 'Buku "' . htmlspecialchars($book_title) . '" telah ditambahkan di perpustakaan. Silakan pinjam sekarang!';
    
    $count = $helper->broadcastNotification(
        $school_id,
        $students,
        'new_book',
        'Buku Baru Tersedia',
        $message
    );

    echo json_encode([
        'success' => true,
        'count' => $count,
        'message' => $count . ' notifikasi buku baru berhasil dikirim'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
