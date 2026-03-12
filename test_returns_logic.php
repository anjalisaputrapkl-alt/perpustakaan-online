<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = require __DIR__ . '/src/db.php';
    $sid = 1; // Testing with school_id 1

    echo "--- Test 1: Active Borrows Count ---\n";
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status IN ("borrowed", "overdue")');
    $stmt->execute(['sid' => $sid]);
    echo "Count: " . $stmt->fetchColumn() . "\n\n";

    echo "--- Test 2: Recent Returns ---\n";
    $stmt = $pdo->prepare(
      'SELECT b.*, bk.title, m.name as member_name 
       FROM borrows b
       JOIN books bk ON b.book_id = bk.id
       JOIN members m ON b.member_id = m.id
       WHERE b.school_id = :sid AND b.status = "returned"
       ORDER BY b.returned_at DESC LIMIT 5'
    );
    $stmt->execute(['sid' => $sid]);
    $returns = $stmt->fetchAll();
    echo "Found " . count($returns) . " returns.\n";
    if (count($returns) > 0) {
        print_r($returns[0]);
    }
    echo "\n";

    echo "--- Test 3: Process Return Logic (Simulation) ---\n";
    // Find an active borrow to test with
    $stmt = $pdo->query('SELECT b.id, bk.isbn FROM borrows b JOIN books bk ON b.book_id = bk.id WHERE b.status IN ("borrowed", "overdue") LIMIT 1');
    $testBorrow = $stmt->fetch();
    if ($testBorrow) {
        $barcode = $testBorrow['isbn'];
        echo "Testing with barcode: $barcode\n";
        
        // This simulates api/process-return.php logic
        $bookStmt = $pdo->prepare('SELECT id, title, author, isbn, copies FROM books WHERE (isbn = :isbn OR id = :id) AND school_id = :sid');
        $bookStmt->execute(['isbn' => $barcode, 'id' => $barcode, 'sid' => $sid]);
        $book = $bookStmt->fetch();
        echo "Book found: " . ($book ? $book['title'] : 'NO') . "\n";
        
        if ($book) {
            $borrowStmt = $pdo->prepare(
                'SELECT b.*, m.name as member_name, m.nisn
                 FROM borrows b
                 JOIN members m ON b.member_id = m.id
                 WHERE b.book_id = :book_id AND b.school_id = :sid 
                 AND b.status IN ("borrowed", "overdue", "pending_return")
                 ORDER BY b.borrowed_at ASC LIMIT 1'
            );
            $borrowStmt->execute(['book_id' => $book['id'], 'sid' => $sid]);
            $borrow = $borrowStmt->fetch();
            echo "Borrow found: " . ($borrow ? "ID " . $borrow['id'] : 'NO') . "\n";
            
            if ($borrow) {
                echo "Waitlist test...\n";
                $waitlistStmt = $pdo->prepare(
                    'SELECT w.*, u.id as student_real_id 
                     FROM waitlist w
                     JOIN members m ON w.member_id = m.id
                     JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id
                     WHERE w.school_id = :sid 
                     AND w.book_title = :title 
                     AND w.book_author = :author 
                     AND w.status = "pending"
                     ORDER BY w.created_at ASC'
                );
                $waitlistStmt->execute([
                    'sid' => $sid,
                    'title' => $book['title'],
                    'author' => $book['author']
                ]);
                $wait = $waitlistStmt->fetchAll();
                echo "Waitlist count: " . count($wait) . "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
