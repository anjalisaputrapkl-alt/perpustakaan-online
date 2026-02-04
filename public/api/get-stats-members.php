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
            m.id,
            m.name,
            m.nisn,
            m.email,
            m.status,
            m.created_at,
            (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND returned_at IS NULL AND school_id = :sid1) as current_borrows
        FROM members m
        WHERE m.school_id = :sid2
        ORDER BY m.created_at DESC
    ");
    
    $stmt->execute(['sid1' => $school_id, 'sid2' => $school_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($members as $member) {
        $data[] = [
            'id' => $member['id'],
            'name' => htmlspecialchars($member['name']),
            'nisn' => htmlspecialchars($member['nisn'] ?? '-'),
            'email' => htmlspecialchars($member['email'] ?? '-'),
            'status' => $member['status'] == 'active' ? 'Aktif' : 'Nonaktif',
            'current_borrows' => $member['current_borrows'],
            'joined_date' => date('d M Y', strtotime($member['created_at']))
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
