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
            DATEDIFF(NOW(), br.due_at) as days_overdue
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid AND br.returned_at IS NULL AND br.status = 'overdue'
        ORDER BY br.due_at ASC
    ");
    
    $stmt->execute(['sid' => $school_id]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($overdue as $item) {
        $data[] = [
            'id' => $item['id'],
            'book_title' => htmlspecialchars($item['title']),
            'book_author' => htmlspecialchars($item['author'] ?? '-'),
            'member_name' => htmlspecialchars($item['member_name']),
            'member_nisn' => htmlspecialchars($item['nisn'] ?? '-'),
            'borrowed_date' => date('d M Y', strtotime($item['borrowed_at'])),
            'due_date' => date('d M Y', strtotime($item['due_at'])),
            'days_overdue' => $item['days_overdue']
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
