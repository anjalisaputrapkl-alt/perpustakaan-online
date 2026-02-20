<?php
try {
    $pdo = require 'src/db.php';
    echo "--- BOOKS DATA (Top 10) ---\n";
    $stmt = $pdo->query('SELECT id, title, copies FROM books ORDER BY id DESC LIMIT 10');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | Title: " . $row['title'] . " | Copies: " . $row['copies'] . "\n";
    }
    echo "\n--- ALL BORROWS FOR BOOKS 18, 19 ---\n";
    $stmt = $pdo->query('SELECT b.id, b.book_id, b.member_id, b.status, b.returned_at, b.borrowed_at, m.name 
                         FROM borrows b 
                         LEFT JOIN members m ON b.member_id = m.id 
                         WHERE b.book_id IN (18, 19)');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Borrow ID: " . $row['id'] . " | Book ID: " . $row['book_id'] . " | Status: " . $row['status'] . " | Returned At: " . ($row['returned_at'] ?? 'NULL') . " | Member: " . $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
