<?php
/**
 * TEST FILE - Debug Stats Modal
 * Akses via: http://localhost/perpustakaan-online/public/test-api.php
 */

session_start();

// Simulate login jika belum
if (empty($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 4,
        'school_id' => 4,
        'name' => 'Test User'
    ];
}

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

echo "<!DOCTYPE html>";
echo "<html><head><title>API Test</title>";
echo "<style>body { font-family: Arial; padding: 20px; } .test { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; } .pass { color: green; } .fail { color: red; } pre { background: white; padding: 10px; overflow-x: auto; }</style>";
echo "</head><body>";

echo "<h1>📊 Test Stats API</h1>";
echo "<p>School ID: <strong>$school_id</strong></p>";

// Test 1: Check database connection
echo "<div class='test'>";
echo "<h2>Test 1: Database Connection</h2>";
try {
    $result = $pdo->query("SELECT COUNT(*) as total FROM books WHERE school_id = $school_id");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "<p class='pass'>✓ Database connected</p>";
    echo "<p>Books in DB: <strong>" . $count['total'] . "</strong></p>";
} catch (Exception $e) {
    echo "<p class='fail'>✗ Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Get Books Data
echo "<div class='test'>";
echo "<h2>Test 2: Books Data</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.title, b.author, b.category, b.copies,
            (SELECT COUNT(*) FROM borrows WHERE book_id = b.id AND returned_at IS NULL AND school_id = :sid) as borrowed_count
        FROM books b
        WHERE b.school_id = :sid
        ORDER BY b.created_at DESC
    ");
    $stmt->execute(['sid' => $school_id]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='pass'>✓ Query executed</p>";
    echo "<p>Books found: <strong>" . count($books) . "</strong></p>";
    
    if (count($books) > 0) {
        echo "<pre>";
        echo json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Get Members Data
echo "<div class='test'>";
echo "<h2>Test 3: Members Data</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id, m.name, m.nisn, m.email, m.status,
            (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND returned_at IS NULL AND school_id = :sid) as current_borrows
        FROM members m
        WHERE m.school_id = :sid
        ORDER BY m.created_at DESC
    ");
    $stmt->execute(['sid' => $school_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='pass'>✓ Query executed</p>";
    echo "<p>Members found: <strong>" . count($members) . "</strong></p>";
    
    if (count($members) > 0) {
        echo "<pre>";
        echo json_encode($members, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Get Borrows Data
echo "<div class='test'>";
echo "<h2>Test 4: Borrows Data</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            br.id, b.title, b.author, m.name as member_name, m.nisn,
            br.borrowed_at, br.due_at, br.status,
            DATEDIFF(br.due_at, NOW()) as days_remaining
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid AND br.returned_at IS NULL
        ORDER BY br.borrowed_at DESC
    ");
    $stmt->execute(['sid' => $school_id]);
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='pass'>✓ Query executed</p>";
    echo "<p>Borrows found: <strong>" . count($borrows) . "</strong></p>";
    
    if (count($borrows) > 0) {
        echo "<pre>";
        echo json_encode($borrows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Get Overdue Data
echo "<div class='test'>";
echo "<h2>Test 5: Overdue Data</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            br.id, b.title, b.author, m.name as member_name, m.nisn,
            br.borrowed_at, br.due_at, br.status,
            DATEDIFF(NOW(), br.due_at) as days_overdue
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid AND br.returned_at IS NULL AND br.status = 'overdue'
        ORDER BY br.due_at ASC
    ");
    $stmt->execute(['sid' => $school_id]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='pass'>✓ Query executed</p>";
    echo "<p>Overdue found: <strong>" . count($overdue) . "</strong></p>";
    
    if (count($overdue) > 0) {
        echo "<pre>";
        echo json_encode($overdue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    } else {
        echo "<p>No overdue items</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6: Check endpoint directly
echo "<div class='test'>";
echo "<h2>Test 6: Test Endpoint Response</h2>";
echo "<p>Testing if endpoint returns JSON correctly...</p>";
echo "<button onclick=\"testEndpoint('api/get-stats-books.php', 'books')\">Test Books Endpoint</button>";
echo "<button onclick=\"testEndpoint('api/get-stats-members.php', 'members')\">Test Members Endpoint</button>";
echo "<button onclick=\"testEndpoint('api/get-stats-borrowed.php', 'borrowed')\">Test Borrowed Endpoint</button>";
echo "<button onclick=\"testEndpoint('api/get-stats-overdue.php', 'overdue')\">Test Overdue Endpoint</button>";
echo "<pre id='endpoint-result'></pre>";
echo "</div>";

echo "</body>";
echo "<script>";
echo "function testEndpoint(url, name) {";
echo "  const resultEl = document.getElementById('endpoint-result');";
echo "  resultEl.innerHTML = 'Loading ' + name + '...';";
echo "  fetch(url)";
echo "    .then(r => { ";
echo "      console.log('Status:', r.status);";
echo "      resultEl.innerHTML += '\\nStatus: ' + r.status + '\\n';";
echo "      return r.json();";
echo "    })";
echo "    .then(d => {";
echo "      resultEl.innerHTML += JSON.stringify(d, null, 2);";
echo "      console.log('Response:', d);";
echo "    })";
echo "    .catch(e => {";
echo "      resultEl.innerHTML += 'ERROR: ' + e.message;";
echo "      console.error('Error:', e);";
echo "    });";
echo "}";
echo "</script>";
echo "</html>";
?>
