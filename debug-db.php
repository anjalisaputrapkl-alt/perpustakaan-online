<?php
/**
 * Debug script untuk check struktur tabel users
 * Akses: http://localhost/perpustakaan-online/debug-db.php
 */

$pdo = require __DIR__ . '/src/db.php';

echo "<h2>Struktur Tabel Users</h2>";
echo "<pre>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ") - " . ($col['Null'] === 'YES' ? 'nullable' : 'NOT NULL') . "\n";
    }

    echo "\n\n<h2>Check untuk kolom verification:</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field IN ('is_verified', 'verification_code', 'verified_at')");
    $verification_cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($verification_cols)) {
        echo "❌ Kolom verification BELUM ada\n\n";
        echo "Jalankan: http://localhost/perpustakaan-online/sql/run-migration.php\n";
    } else {
        echo "✅ Kolom verification sudah ada:\n";
        foreach ($verification_cols as $col) {
            echo "  - " . $col['Field'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";
?>