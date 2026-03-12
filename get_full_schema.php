<?php
$pdo = require __DIR__ . '/src/db.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$output = "";
foreach ($tables as $table) {
    $output .= "--- TABLE: $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output .= "{$row['Field']} - {$row['Type']}\n";
    }
    $output .= "\n";
}
file_put_contents('full_schema_log.txt', $output);
