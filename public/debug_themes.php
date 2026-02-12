<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/ThemeModel.php';

echo "<h1>Debug Special Themes</h1>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION['user'] ?? 'No user in session');
echo "</pre>";

$school_id = $_SESSION['user']['school_id'] ?? null;

if ($school_id) {
    echo "<h2>Current School Themes (ID: $school_id)</h2>";
    $themeModel = new ThemeModel($pdo);
    
    try {
        $specialThemes = $themeModel->getSpecialThemes($school_id);
        echo "<h3>All Special Themes</h3>";
        echo "<pre>";
        print_r($specialThemes);
        echo "</pre>";
        
        $activeTheme = $themeModel->checkSpecialTheme($school_id);
        echo "<h3>Active Theme Today</h3>";
        echo "<pre>";
        var_dump($activeTheme);
        echo "</pre>";
        
        echo "<h3>Today's Date (PHP)</h3>";
        echo date('Y-m-d');
        
        $stmt = $pdo->query("SELECT CURRENT_DATE as db_date");
        echo "<h3>Today's Date (DB)</h3>";
        print_r($stmt->fetch());
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>No school_id found in session.</p>";
}

echo "<h2>Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE special_themes");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
