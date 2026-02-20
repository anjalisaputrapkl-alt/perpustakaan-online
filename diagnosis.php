<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = require 'src/db.php';
    echo "Database connected.\n";

    echo "--- Books with copies = 0 ---\n";
    $stmt = $pdo->query("SELECT id, title, copies, school_id FROM books WHERE copies = 0");
    $books0 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($books0 as $b) {
        echo "Book ID: {$b['id']} | Title: {$b['title']} | School: {$b['school_id']}\n";
        
        // Check for active borrows
        $bStmt = $pdo->prepare("SELECT b.id, b.status, b.returned_at, m.name 
                                FROM borrows b 
                                LEFT JOIN members m ON b.member_id = m.id 
                                WHERE b.book_id = :bid AND b.returned_at IS NULL");
        $bStmt->execute(['bid' => $b['id']]);
        $borrows = $bStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($borrows)) {
            echo "  -> NO ACTIVE BORROW RECORD FOUND (returned_at IS NULL match failed)\n";
            
            // Check ANY borrows
            $allBStmt = $pdo->prepare("SELECT id, status, returned_at FROM borrows WHERE book_id = :bid ORDER BY id DESC LIMIT 3");
            $allBStmt->execute(['bid' => $b['id']]);
            $allB = $allBStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allB as $ab) {
                 echo "     Found borrow ID: {$ab['id']} | Status: {$ab['status']} | Returned At: " . ($ab['returned_at'] ?? 'NULL') . "\n";
            }
        } else {
            foreach ($borrows as $br) {
                echo "  -> Active Borrow ID: {$br['id']} | Status: {$br['status']} | Member: {$br['name']}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
