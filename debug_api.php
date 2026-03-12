<?php
$pdo = require __DIR__ . '/src/db.php';
// Find a valid barcode for an active borrow
$stmt = $pdo->query('SELECT bk.isbn FROM borrows b JOIN books bk ON b.book_id = bk.id WHERE b.status IN ("borrowed", "overdue") LIMIT 1');
$isbn = $stmt->fetchColumn();

if (!$isbn) {
    die("No active borrows found to test.\n");
}

echo "Testing with ISBN: $isbn\n";

$url = "http://localhost/perpustakaan-online/public/api/process-return.php";
$data = ['barcode' => $isbn];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// We need a session! Let's mock it if possible or just run the logic directly in a script.
// Running locally via cURL might fail due to auth.
// Let's just run the logic directly in a script but catch ALL errors.

echo "Running logic directly...\n";
ob_start();
try {
    $_SESSION['user'] = ['school_id' => 1, 'id' => 1]; // Mock admin
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $input = ['barcode' => $isbn]; // Mock input for file_get_contents('php://input') replacement
    
    // Instead of including (which might exit), let's just copy the logic or use a closure
    // But since I want to be 100% sure about the REAL file:
    include 'public/api/process-return.php';
} catch (Exception $e) {
    echo "CAUGHT: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();
echo "OUTPUT:\n" . $output . "\n";
