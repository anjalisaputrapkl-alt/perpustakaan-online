<?php
/**
 * Script untuk menganalisis struktur table dan kesesuaian data
 */

try {
    $pdo = require __DIR__ . '/src/db.php';

    echo "<pre>";
    echo "=== ANALISIS STRUKTUR TABLE ===\n\n";

    // Check members table
    echo "TABLE: members\n";
    echo str_repeat("-", 80) . "\n";
    $result = $pdo->query("DESCRIBE members");
    echo $result->getColumnMeta(0) ? "Columns:\n" : "No columns\n";
    foreach ($pdo->query("DESCRIBE members") as $col) {
        echo sprintf("  %-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Null']);
    }

    echo "\n\nTABLE: siswa\n";
    echo str_repeat("-", 80) . "\n";
    echo "Columns:\n";
    foreach ($pdo->query("DESCRIBE siswa") as $col) {
        echo sprintf("  %-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Null']);
    }

    echo "\n\nSAMPLE DATA:\n";
    echo str_repeat("-", 80) . "\n";
    echo "\nMembers (first 3 rows):\n";
    foreach ($pdo->query("SELECT id, name, nisn, member_no, email, school_id FROM members LIMIT 3") as $row) {
        print_r($row);
    }

    echo "\nSiswa (first 3 rows):\n";
    foreach ($pdo->query("SELECT id_siswa, nama_lengkap, nis, nisn, email FROM siswa LIMIT 3") as $row) {
        print_r($row);
    }

    echo "\n</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>