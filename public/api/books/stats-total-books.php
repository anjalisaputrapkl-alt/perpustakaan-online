<?php
/**
 * API untuk menampilkan semua buku
 * GET /api/books/stats-total-books.php
 */

header('Content-Type: application/json');
require __DIR__ . '/../../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare('
        SELECT id, title, author, category, isbn, copies, created_at 
        FROM books 
        WHERE school_id = :sid 
        ORDER BY created_at DESC
    ');
    $stmt->execute(['sid' => $school_id]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'books' => $books
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
