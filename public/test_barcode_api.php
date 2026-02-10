<?php
// Mock input for API test
$book_isbn = '978-602-06-3317-6'; // Find a real ISBN from DB if possible
$student_nisn = '99999'; // From previous debug
$teacher_nisn = '12345'; // Need to find a teacher

require __DIR__ . '/../src/db.php';

echo "=== TEST BOOK ===\n";
$stmt = $pdo->query("SELECT isbn FROM books WHERE access_level = 'teacher_only' LIMIT 1");
$book = $stmt->fetch();
if ($book) {
    $_POST_JSON = json_encode(['barcode' => $book['isbn']]);
    // Simulate request
    $ch = curl_init('http://localhost/perpustakaan-online/public/api/process-barcode.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST_JSON);
    $response = curl_exec($ch);
    echo "Response: " . $response . "\n";
    $json = json_decode($response, true);
    echo "Has access_level? " . (isset($json['data']['access_level']) ? "YES ({$json['data']['access_level']})" : "NO") . "\n";
} else {
    echo "No teacher_only book found for test.\n";
}

echo "\n=== TEST STUDENT ===\n";
$stmt = $pdo->query("SELECT nisn FROM members WHERE role = 'student' LIMIT 1");
$student = $stmt->fetch();
if ($student) {
    $_POST_JSON = json_encode(['barcode' => $student['nisn']]);
    $ch = curl_init('http://localhost/perpustakaan-online/public/api/process-barcode.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST_JSON);
    $response = curl_exec($ch);
    echo "Response: " . $response . "\n";
    $json = json_decode($response, true);
    echo "Has role? " . (isset($json['data']['role']) ? "YES ({$json['data']['role']})" : "NO") . "\n";
} else {
    echo "No student found.\n";
}
