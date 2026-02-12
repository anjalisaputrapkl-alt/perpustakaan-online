<?php
$pdo = require 'src/db.php';
$stmt = $pdo->query('SELECT * FROM special_themes');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
