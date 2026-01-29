<?php
require __DIR__ . '/src/auth.php';
requireAuth();
$pdo = require __DIR__ . '/src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all borrowing data like borrows.php does
$stmt = $pdo->prepare(
    'SELECT b.*, bk.title, m.name AS member_name
     FROM borrows b
     JOIN books bk ON b.book_id = bk.id
     JOIN members m ON b.member_id = m.id
     WHERE b.school_id = :sid
     ORDER BY b.borrowed_at DESC'
);
$stmt->execute(['sid' => $sid]);
$borrows = $stmt->fetchAll();

echo "=== VERIFICATION OF BORROWS DISPLAY ===\n\n";
echo "All records for school_id=$sid:\n";
foreach ($borrows as $b) {
    echo "  ID {$b['id']}: Book {$b['title']}, Status: {$b['status']}\n";
}

echo "\n1. Form Peminjaman Menunggu Konfirmasi (pending_confirmation):\n";
$pending = array_filter($borrows, fn($b) => $b['status'] === 'pending_confirmation');
if (empty($pending)) {
    echo "   (Empty)\n";
} else {
    foreach ($pending as $p) {
        echo "   - ID {$p['id']}: {$p['title']}\n";
    }
}

echo "\n2. Daftar Peminjaman Aktif (borrowed):\n";
$active = array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation');
if (empty($active)) {
    echo "   (Empty)\n";
} else {
    foreach ($active as $a) {
        echo "   - ID {$a['id']}: {$a['title']}\n";
    }
}

echo "\n3. Empty check for 'Daftar Peminjaman Aktif':\n";
$shouldBeEmpty = empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation'));
echo "   Should display table: " . (!$shouldBeEmpty ? "YES ✓" : "NO (show empty state)") . "\n";
?>