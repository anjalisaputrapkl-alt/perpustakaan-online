<?php
$pdo = require __DIR__ . '/../src/db.php';

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "  - " . $table . "\n";
    }
    
    // Cek untuk buku table variations
    echo "\n\nChecking for books/buku related tables...\n";
    $result = $pdo->query("SHOW TABLES LIKE '%buku%'");
    $bookTables = $result->fetchAll(PDO::FETCH_COLUMN);
    if (empty($bookTables)) {
        echo "No 'buku' related tables found.\n";
    } else {
        foreach ($bookTables as $table) {
            echo "Found: " . $table . "\n";
        }
    }
    
    // Check untuk books table
    $result = $pdo->query("SHOW TABLES LIKE '%book%'");
    $bookTables = $result->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($bookTables)) {
        foreach ($bookTables as $table) {
            echo "Found: " . $table . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
