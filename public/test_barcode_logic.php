<?php
// Mock input for API test
require __DIR__ . '/../src/db.php';

function test_barcode($barcode) {
    global $pdo;
    
    // Validasi logic seperti di process-barcode.php
    $stmt = $pdo->prepare(
        'SELECT id, nisn as barcode, name, role, "member" as type FROM members 
         WHERE nisn = ? OR id = ?
         LIMIT 1'
    );
    $stmt->execute([$barcode, $barcode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return $result;
    }

    $stmt = $pdo->prepare(
        'SELECT id, isbn as barcode, title as name, cover_image, copies, max_borrow_days, access_level, "book" as type FROM books 
         WHERE isbn = ? OR id = ?
         LIMIT 1'
    );
    $stmt->execute([$barcode, $barcode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result;
}

echo "=== TEST BOOK ===\n";
$stmt = $pdo->query("SELECT isbn, title FROM books WHERE access_level = 'teacher_only' LIMIT 1");
$book = $stmt->fetch();
if ($book) {
    echo "Testing Book: {$book['title']} (ISBN: {$book['isbn']})\n";
    $res = test_barcode($book['isbn']);
    print_r($res);
} else {
    echo "No teacher_only book found.\n";
}

echo "\n=== TEST STUDENT ===\n";
$stmt = $pdo->query("SELECT nisn, name FROM members WHERE role = 'student' LIMIT 1");
$student = $stmt->fetch();
if ($student) {
    echo "Testing Student: {$student['name']} (NISN: {$student['nisn']})\n";
    $res = test_barcode($student['nisn']);
    print_r($res);
} else {
    echo "No student found.\n";
}
