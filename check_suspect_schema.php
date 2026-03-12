<?php
$pdo = require __DIR__ . '/src/db.php';
$tables = ['borrows', 'members', 'waitlist', 'notifications', 'log_returns'];
$output = "";
foreach ($tables as $table) {
    $output .= "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $output .= "{$row['Field']} - {$row['Type']}\n";
        }
    } catch (Exception $e) {
        $output .= "Error: " . $e->getMessage() . "\n";
    }
    $output .= "\n";
}
file_put_contents('suspect_schema_log.txt', $output);
