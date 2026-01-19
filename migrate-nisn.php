<?php
// Migration script untuk menambahkan NISN column

$pdo = require __DIR__ . '/src/db.php';

try {
    // Cek apakah kolom NISN sudah ada
    $checkStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'nisn'");
    if ($checkStmt->rowCount() === 0) {
        echo "Menambahkan kolom NISN ke tabel users...\n";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `nisn` VARCHAR(20) UNIQUE AFTER `email`");
        echo "✓ Kolom NISN berhasil ditambahkan ke tabel users\n";
    } else {
        echo "ℹ Kolom NISN sudah ada di tabel users\n";
    }

    // Cek apakah kolom NISN sudah ada di members
    $checkStmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'nisn'");
    if ($checkStmt->rowCount() === 0) {
        echo "Menambahkan kolom NISN ke tabel members...\n";
        $pdo->exec("ALTER TABLE `members` ADD COLUMN `nisn` VARCHAR(20) UNIQUE AFTER `member_no`");
        echo "✓ Kolom NISN berhasil ditambahkan ke tabel members\n";
    } else {
        echo "ℹ Kolom NISN sudah ada di tabel members\n";
    }

    // Update role enum untuk termasuk 'student'
    echo "Mengupdate role enum di tabel users...\n";
    $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` enum('admin','librarian','student') DEFAULT 'librarian'");
    echo "✓ Role enum berhasil diupdate\n";

    echo "\n✓ Migration berhasil!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
