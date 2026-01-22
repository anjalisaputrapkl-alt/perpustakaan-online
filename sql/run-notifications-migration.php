<?php
$pdo = require __DIR__ . '/../src/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/notifications_table_clean.sql');
    
    echo "SQL file loaded, length: " . strlen($sql) . " chars\n\n";
    
    // Split by semicolon and clean up
    $lines = explode(';', $sql);
    echo "Split into " . count($lines) . " parts\n\n";
    
    $statements = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed) && strpos($trimmed, '--') !== 0) {
            $statements[] = $trimmed;
        }
    }
    
    echo "Cleaned statements: " . count($statements) . "\n\n";
    
    $executed = 0;
    foreach ($statements as $i => $statement) {
        echo "Executing statement " . ($i+1) . ":\n";
        echo substr($statement, 0, 80) . "...\n";
        try {
            $pdo->exec($statement);
            $executed++;
            echo "✓ OK\n\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "\n✓ SUCCESS! " . $executed . " statements executed.\n\n";
    
    // Verify table exists
    $result = $pdo->query('SHOW TABLES LIKE "notifications"');
    if ($result->rowCount() > 0) {
        echo "✓ Tabel notifications sudah ada di database!\n\n";
        
        // Check table structure
        $columns = $pdo->query('DESCRIBE notifications');
        echo "Struktur Tabel:\n";
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "ERROR: Table tidak ditemukan!\n";
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
?>
