<?php
session_start();

echo "Session ID: " . session_id() . "<br>";
echo "Session Data: <pre>";
var_dump($_SESSION);
echo "</pre>";

// Test save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['scan_data'][] = [
        'member_id' => 1,
        'book_id' => 1,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    echo "Data added to session<br>";
}

// Test get
if (isset($_GET['get']) && $_GET['get'] === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'session_id' => session_id(),
        'data' => $_SESSION['scan_data'] ?? [],
        'count' => count($_SESSION['scan_data'] ?? [])
    ]);
    exit;
}
?>
<html>

<body>
    <h1>Session Test</h1>
    <form method="POST">
        <button type="submit">Add Test Data</button>
    </form>
    <a href="?get=1">Fetch as JSON</a>
</body>

</html>