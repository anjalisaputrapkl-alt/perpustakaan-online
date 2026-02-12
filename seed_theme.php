<?php
$pdo = require 'src/db.php';
$school_id = 1; // Assuming default

$stmt = $pdo->prepare('INSERT INTO special_themes (school_id, name, date, theme_key, is_active) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$school_id, 'Test Theme', date('Y-m-d'), 'kemerdekaan', 1]);

echo "Seeded special theme for today (" . date('Y-m-d') . ") for school_id $school_id\n";
?>
