<?php
require __DIR__ . '/../src/db.php';

try {
    $pdo->exec("ALTER TABLE books ADD COLUMN access_level ENUM('all', 'teacher_only') NOT NULL DEFAULT 'all' AFTER category");
    echo "Successfully added access_level column to books table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column access_level already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
