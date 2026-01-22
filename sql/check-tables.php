<?php
$pdo = require __DIR__ . '/../src/db.php';

echo "=== BUKU TABLE STRUCTURE ===\n";
$columns = $pdo->query('DESCRIBE buku');
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== USERS TABLE STRUCTURE ===\n";
$columns = $pdo->query('DESCRIBE users');
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>
