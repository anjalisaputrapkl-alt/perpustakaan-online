<?php
$pdo = require __DIR__ . '/src/db.php';

function dumpTable($pdo, $table) {
    echo "\nTable: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo " - {$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo " Error: " . $e->getMessage() . "\n";
    }
}

dumpTable($pdo, 'books');
dumpTable($pdo, 'members');
dumpTable($pdo, 'borrows');
?>
