<?php
/**
 * API untuk analisis salinan (rata-rata, max, min)
 * GET /api/books/stats-avg-copies.php
 */

header('Content-Type: application/json');
require __DIR__ . '/../../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    // Hitung rata-rata
    $stmt = $pdo->prepare('
        SELECT AVG(copies) as average 
        FROM books 
        WHERE school_id = :sid
    ');
    $stmt->execute(['sid' => $school_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $average = floatval($result['average'] ?? 0);
    
    // Buku dengan salinan paling banyak
    $stmt = $pdo->prepare('
        SELECT id, title, copies 
        FROM books 
        WHERE school_id = :sid 
        ORDER BY copies DESC 
        LIMIT 1
    ');
    $stmt->execute(['sid' => $school_id]);
    $max_book = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$max_book) $max_book = null;
    
    // Buku dengan salinan paling sedikit
    $stmt = $pdo->prepare('
        SELECT id, title, copies 
        FROM books 
        WHERE school_id = :sid 
        ORDER BY copies ASC 
        LIMIT 1
    ');
    $stmt->execute(['sid' => $school_id]);
    $min_book = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$min_book) $min_book = null;
    
    echo json_encode([
        'success' => true,
        'average' => $average,
        'max_book' => $max_book ?: null,
        'min_book' => $min_book ?: null,
        'debug' => [
            'school_id' => $school_id,
            'has_max_book' => !empty($max_book),
            'has_min_book' => !empty($min_book)
        ]
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
