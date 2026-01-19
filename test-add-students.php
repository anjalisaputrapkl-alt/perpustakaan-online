<?php
// Test Script - Tambahkan siswa test dengan NISN

$pdo = require __DIR__ . '/src/db.php';

// Data siswa test
$test_students = [
    [
        'name' => 'Budi Santoso',
        'email' => 'budi@sekolah.com',
        'member_no' => '001',
        'nisn' => '1234567890'
    ],
    [
        'name' => 'Siti Nurhaliza',
        'email' => 'siti@sekolah.com',
        'member_no' => '002',
        'nisn' => '1234567891'
    ],
    [
        'name' => 'Ahmad Rijal',
        'email' => 'ahmad@sekolah.com',
        'member_no' => '003',
        'nisn' => '1234567892'
    ]
];

try {
    $school_id = 7; // Sesuaikan dengan school_id yang ada

    foreach ($test_students as $student) {
        // Check apakah sudah ada
        $checkStmt = $pdo->prepare('SELECT id FROM members WHERE nisn = :nisn');
        $checkStmt->execute(['nisn' => $student['nisn']]);

        if ($checkStmt->rowCount() > 0) {
            echo "⚠️ Siswa dengan NISN {$student['nisn']} sudah ada, skip...\n";
            continue;
        }

        // Insert ke members
        $stmt = $pdo->prepare('
            INSERT INTO members (school_id, name, email, member_no, nisn)
            VALUES (:sid, :name, :email, :no, :nisn)
        ');
        $stmt->execute([
            'sid' => $school_id,
            'name' => $student['name'],
            'email' => $student['email'],
            'no' => $student['member_no'],
            'nisn' => $student['nisn']
        ]);

        // Insert ke users (create account)
        $default_password = password_hash($student['nisn'], PASSWORD_BCRYPT);
        $userStmt = $pdo->prepare('
            INSERT INTO users (school_id, name, email, password, role, nisn)
            VALUES (:sid, :name, :email, :password, :role, :nisn)
        ');
        $userStmt->execute([
            'sid' => $school_id,
            'name' => $student['name'],
            'email' => $student['email'],
            'password' => $default_password,
            'role' => 'student',
            'nisn' => $student['nisn']
        ]);

        echo "✅ Siswa ditambahkan: {$student['name']} (NISN: {$student['nisn']})\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "DATA LOGIN SISWA TEST:\n";
    echo str_repeat("=", 60) . "\n";
    foreach ($test_students as $student) {
        echo "Nama: {$student['name']}\n";
        echo "NISN: {$student['nisn']}\n";
        echo "Password: {$student['nisn']}\n";
        echo "---\n";
    }
    echo str_repeat("=", 60) . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
