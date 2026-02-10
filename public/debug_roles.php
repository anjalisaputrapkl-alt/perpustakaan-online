<?php
require __DIR__ . '/../src/db.php';
$stmt = $pdo->query("SELECT DISTINCT role FROM members");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
