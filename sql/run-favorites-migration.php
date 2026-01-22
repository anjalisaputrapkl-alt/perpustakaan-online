<?php
$pdo = require __DIR__ . '/../src/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS favorites (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        book_id INT NOT NULL,
        category VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        KEY idx_student (student_id),
        KEY idx_book (book_id),
        KEY idx_student_book (student_id, book_id),
        UNIQUE KEY unique_student_book (student_id, book_id),
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Table favorites created successfully!\n";
    
    // Verify
    $result = $pdo->query('SHOW TABLES LIKE "favorites"');
    if ($result->rowCount() > 0) {
        echo "✓ Table verified!\n";
        
        $columns = $pdo->query('DESCRIBE favorites');
        echo "\nTable Structure:\n";
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
