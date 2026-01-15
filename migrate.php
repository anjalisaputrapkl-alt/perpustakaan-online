<?php
$pdo = require __DIR__ . '/src/db.php';

try {
    // Add shelf column if not exists
    $pdo->exec("ALTER TABLE books ADD COLUMN shelf VARCHAR(50) DEFAULT NULL");
    echo "✓ shelf column added\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') === false) {
        echo "✗ Error adding shelf: " . $e->getMessage() . "\n";
    } else {
        echo "✓ shelf column already exists\n";
    }
}

try {
    // Add row_number column if not exists
    $pdo->exec("ALTER TABLE books ADD COLUMN row_number INT DEFAULT NULL");
    echo "✓ row_number column added\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') === false) {
        echo "✗ Error adding row_number: " . $e->getMessage() . "\n";
    } else {
        echo "✓ row_number column already exists\n";
    }
}

echo "Migration complete!\n";
?>