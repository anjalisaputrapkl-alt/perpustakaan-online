<?php
// Mock session
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'school_id' => 1,
    'role' => 'admin',
    'name' => 'Admin'
];

$pdo = require __DIR__ . '/src/db.php';

// Find an active borrow to "return"
$stmt = $pdo->query('
    SELECT b.id, b.book_id, bk.isbn, bk.title, bk.author 
    FROM borrows b 
    JOIN books bk ON b.book_id = bk.id 
    WHERE b.status = "borrowed" 
    LIMIT 1
');
$borrow = $stmt->fetch();

if (!$borrow) {
    die("No active borrows to test with.\n");
}

echo "Testing return for book: {$borrow['title']} (ISBN: {$borrow['isbn']})\n";

// Ensure there is a waitlist entry for this book
// (Manual insert for testing)
$pdo->prepare('INSERT INTO waitlist (school_id, book_title, book_author, member_id, status) VALUES (1, ?, ?, 2, "pending")')
    ->execute([$borrow['title'], $borrow['author']]);

echo "Created waitlist entry for this book.\n";

// Simulate the POST request to process-return.php
// We can't really "fetch" locally easily without a server, so we'll just include the file logic
// But process-return.php might exit/echo.
// Let's just run it via command line if possible or wrap it.

$barcode = $borrow['isbn'];
$_POST['barcode'] = $barcode; // Mocking if needed, but process-return uses php://input

// Instead of including, let's just run a separate script that mimics the logic but without header() calls
?>
