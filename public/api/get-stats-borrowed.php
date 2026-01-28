<?php
header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            b.title,
            b.author,
            m.name as member_name,
            m.nisn,
            br.borrowed_at,
            br.due_at,
            br.status,
            DATEDIFF(br.due_at, NOW()) as days_remaining
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid AND br.returned_at IS NULL
        ORDER BY br.borrowed_at DESC
    ");
    
    $stmt->execute(['sid' => $school_id]);
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($borrows as $borrow) {
        $days = $borrow['days_remaining'];
        $status_display = 'Sedang Dipinjam';
        if ($days < 0) {
            $status_display = 'TERLAMBAT (' . abs($days) . ' hari)';
        } elseif ($days <= 3) {
            $status_display = 'Akan Jatuh Tempo (' . $days . ' hari)';
        }
        
        $data[] = [
            'id' => $borrow['id'],
            'book_title' => htmlspecialchars($borrow['title']),
            'book_author' => htmlspecialchars($borrow['author'] ?? '-'),
            'member_name' => htmlspecialchars($borrow['member_name']),
            'member_nisn' => htmlspecialchars($borrow['nisn'] ?? '-'),
            'borrowed_date' => date('d M Y', strtotime($borrow['borrowed_at'])),
            'due_date' => date('d M Y', strtotime($borrow['due_at'])),
            'days_remaining' => $borrow['days_remaining'],
            'status' => $status_display
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
