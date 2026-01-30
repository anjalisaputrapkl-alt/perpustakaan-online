<?php
// Debug script untuk test borrow-book API
session_start();

// Set user session jika tidak ada
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Test Student',
        'school_id' => 1,
        'email' => 'student@test.com',
        'user_type' => 'student'
    ];
}

header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/MemberHelper.php';
require_once __DIR__ . '/../../src/NotificationsHelper.php';

try {
    $student = $_SESSION['user'];
    $school_id = $student['school_id'] ?? null;

    if (!$school_id) {
        throw new Exception('Invalid school_id');
    }

    $memberHelper = new MemberHelper($pdo);
    $member_id = $memberHelper->getMemberId($student);

    // Test: Get first available book
    $bookStmt = $pdo->prepare('SELECT id, title, copies FROM books WHERE school_id = :school_id AND copies > 0 LIMIT 1');
    $bookStmt->execute(['school_id' => $school_id]);
    $book = $bookStmt->fetch();

    if (!$book) {
        throw new Exception('No available books found');
    }

    echo json_encode([
        'debug' => 'Test data ready',
        'school_id' => $school_id,
        'member_id' => $member_id,
        'book_id' => $book['id'],
        'book_title' => $book['title'],
        'book_copies' => $book['copies']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
