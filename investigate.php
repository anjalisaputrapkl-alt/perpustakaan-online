<?php
session_start();
$pdo = require __DIR__ . '/src/db.php';
echo "USER SESSION:\n";
print_r($_SESSION['user'] ?? 'NULL');
echo "\nSPECIAL THEMES TABLE:\n";
$stmt = $pdo->query('SELECT * FROM special_themes');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
