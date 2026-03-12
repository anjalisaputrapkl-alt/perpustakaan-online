<?php
$pdo = require __DIR__ . '/src/db.php';
$stmt = $pdo->query("SELECT DATABASE()");
echo "Database: " . $stmt->fetchColumn() . "\n";
