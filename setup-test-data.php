<?php
$pdo = require __DIR__ . '/src/db.php';

// Clean slate
$pdo->query("DELETE FROM borrows");

// Insert test data with correct status values
$stmt = $pdo->prepare(
    'INSERT INTO borrows (school_id, member_id, book_id, borrowed_at, due_at, status)
     VALUES (:school_id, :member_id, :book_id, :borrowed_at, :due_at, :status)'
);

$now = date('Y-m-d H:i:s');
$future = date('Y-m-d H:i:s', strtotime('+7 days'));

// Test data
$testData = [
    ['school' => 4, 'member' => 1, 'book' => 1, 'status' => 'pending_confirmation'],
    ['school' => 4, 'member' => 1, 'book' => 2, 'status' => 'pending_confirmation'],
    ['school' => 4, 'member' => 1, 'book' => 3, 'status' => 'borrowed'],
    ['school' => 4, 'member' => 1, 'book' => 4, 'status' => 'borrowed'],
];

foreach ($testData as $data) {
    $stmt->execute([
        'school_id' => $data['school'],
        'member_id' => $data['member'],
        'book_id' => $data['book'],
        'borrowed_at' => $now,
        'due_at' => $future,
        'status' => $data['status']
    ]);
}

echo "✓ Test data created\n\n";

$records = $pdo->query("SELECT id, book_id, status FROM borrows ORDER BY id")->fetchAll();
echo "Current data:\n";
foreach ($records as $r) {
    echo "  ID {$r['id']}: book_id={$r['book_id']}, status={$r['status']}\n";
}

echo "\nExpected display:\n";
echo "  Form Peminjaman Menunggu Konfirmasi: ID 1-2 (pending_confirmation)\n";
echo "  Daftar Peminjaman Aktif: ID 3-4 (borrowed)\n";
?>