<?php
// Check database status
$pdo = require __DIR__ . '/../../src/db.php';

try {
    // Get schools
    $schoolStmt = $pdo->prepare('SELECT id, name FROM schools LIMIT 5');
    $schoolStmt->execute();
    $schools = $schoolStmt->fetchAll();

    // Get books count by school
    $booksStmt = $pdo->prepare('SELECT school_id, COUNT(*) as total, SUM(copies) as total_copies FROM books GROUP BY school_id');
    $booksStmt->execute();
    $books_info = $booksStmt->fetchAll();

    // Get members
    $membersStmt = $pdo->prepare('SELECT id, name, school_id FROM members LIMIT 5');
    $membersStmt->execute();
    $members = $membersStmt->fetchAll();

    // Get recent books
    $recentBooksStmt = $pdo->prepare('SELECT id, title, copies, school_id FROM books ORDER BY created_at DESC LIMIT 5');
    $recentBooksStmt->execute();
    $recent_books = $recentBooksStmt->fetchAll();

    echo json_encode([
        'schools' => $schools,
        'books_per_school' => $books_info,
        'recent_members' => $members,
        'recent_books' => $recent_books
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
