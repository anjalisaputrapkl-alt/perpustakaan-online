<?php
/**
 * Student Data Structure Diagnostic
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

// Check student session
echo "<pre style='background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 6px; font-family: Courier New;'>";
echo "=== SESSION DATA ===\n";
echo "User ID: " . ($_SESSION['user']['id'] ?? 'NOT SET') . "\n";
echo "School ID: " . ($_SESSION['user']['school_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['user']['role'] ?? 'NOT SET') . "\n";
echo "Full Session: " . print_r($_SESSION['user'], true) . "\n\n";

// Check tables that exist
echo "=== DATABASE TABLES ===\n";
$tables_query = $pdo->query("SHOW TABLES FROM perpustakaan_online");
$tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "- $table\n";
}

echo "\n=== MEMBERS TABLE STRUCTURE ===\n";
$members_cols = $pdo->query("SHOW COLUMNS FROM members");
$cols = $members_cols->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

// Try to get current user's student record
$user_id = $_SESSION['user']['id'] ?? null;
if ($user_id) {
    echo "\n=== CURRENT USER'S DATA ===\n";
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($member) {
        echo "Found:\n";
        foreach ($member as $key => $value) {
            echo "$key: " . ($value ?: '(NULL)') . "\n";
        }
    } else {
        echo "No member found with ID: $user_id\n";
    }
}

echo "</pre>";
