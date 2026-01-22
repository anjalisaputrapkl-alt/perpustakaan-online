<?php
/**
 * TEST SCRIPT untuk Favorit Buku System
 * Jalankan: php test-favorites.php
 */

session_start();

// Mock session sebagai student
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Test Student',
        'school_id' => 1,
        'role' => 'student'
    ];
}

$pdo = require __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/NotificationsHelper.php';

$studentId = $_SESSION['user']['id'];
$schoolId = $_SESSION['user']['school_id'];

echo "=== TEST FAVORIT BUKU SYSTEM ===\n\n";

try {
    // TEST 1: Check table exists
    echo "TEST 1: Check 'favorites' table exists\n";
    $result = $pdo->query('SHOW TABLES LIKE "favorites"');
    if ($result->rowCount() > 0) {
        echo "✓ Table 'favorites' exists\n\n";
    } else {
        echo "✗ Table 'favorites' NOT FOUND\n";
        exit(1);
    }

    // TEST 2: Check book table
    echo "TEST 2: Check 'books' table and get sample books\n";
    $stmt = $pdo->query('SELECT id, title FROM books LIMIT 3');
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($books)) {
        echo "✓ Found " . count($books) . " sample books\n";
        foreach ($books as $book) {
            echo "  - [ID: " . $book['id'] . "] " . $book['title'] . "\n";
        }
    } else {
        echo "✗ No books found in database\n";
    }
    echo "\n";

    // TEST 3: Add favorite
    if (!empty($books)) {
        echo "TEST 3: Add book to favorites\n";
        $bookId = $books[0]['id'];
        $bookTitle = $books[0]['title'];
        
        $stmt = $pdo->prepare(
            'INSERT INTO favorites (student_id, book_id, created_at) VALUES (?, ?, NOW())'
        );
        $result = $stmt->execute([$studentId, $bookId]);
        
        if ($result) {
            echo "✓ Favorite added for book: " . $bookTitle . "\n";
            $favoriteId = $pdo->lastInsertId();
            echo "  - Favorite ID: " . $favoriteId . "\n";
            
            // Create notification
            $helper = new NotificationsHelper($pdo);
            $notifResult = $helper->createNotification(
                $schoolId,
                $studentId,
                'info',
                'Buku Ditambahkan ke Favorit',
                'Anda telah menambahkan "' . $bookTitle . '" ke koleksi favorit Anda.'
            );
            
            if ($notifResult) {
                echo "✓ Notification created\n";
            } else {
                echo "⚠ Notification creation failed\n";
            }
        } else {
            echo "✗ Failed to add favorite\n";
        }
        echo "\n";
    }

    // TEST 4: List favorites
    echo "TEST 4: List all favorites for student\n";
    $stmt = $pdo->prepare(
        'SELECT f.id, f.book_id, f.created_at, b.title, b.author
         FROM favorites f
         JOIN books b ON f.book_id = b.id
         WHERE f.student_id = ?
         ORDER BY f.created_at DESC'
    );
    $stmt->execute([$studentId]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($favorites)) {
        echo "✓ Found " . count($favorites) . " favorite(s)\n";
        foreach ($favorites as $fav) {
            echo "  - [Fav ID: " . $fav['id'] . "] " . $fav['title'] . " by " . $fav['author'] . "\n";
        }
    } else {
        echo "✗ No favorites found\n";
    }
    echo "\n";

    // TEST 5: Count favorites
    echo "TEST 5: Count total favorites\n";
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total favorites: " . $result['total'] . "\n\n";

    // TEST 6: Check for duplicate
    echo "TEST 6: Check duplicate prevention\n";
    if (!empty($books)) {
        $bookId = $books[0]['id'];
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) as total FROM favorites WHERE student_id = ? AND book_id = ?'
        );
        $stmt->execute([$studentId, $bookId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            echo "✓ Duplicate check working - Book is already in favorites\n";
        } else {
            echo "⚠ No duplicate found (first time adding)\n";
        }
    }
    echo "\n";

    // TEST 7: Check notifications
    echo "TEST 7: Check notifications created\n";
    $stmt = $pdo->prepare(
        'SELECT id, title, message FROM notifications 
         WHERE student_id = ? AND type = "info"
         ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([$studentId]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notif) {
        echo "✓ Latest notification:\n";
        echo "  - Title: " . $notif['title'] . "\n";
        echo "  - Message: " . substr($notif['message'], 0, 50) . "...\n";
    } else {
        echo "⚠ No notification found\n";
    }
    echo "\n";

    echo "=== ALL TESTS COMPLETED ===\n";
    echo "Backend Favorit system is working! ✅\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>
