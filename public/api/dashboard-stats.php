<?php
/**
 * Dashboard Statistics API
 * GET /api/dashboard-stats.php
 * 
 * Returns summary statistics for dashboard pie chart and status indicators:
 * - Total books
 * - Total members
 * - Books borrowed (currently out)
 * - Books overdue
 * - Available books
 */

header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    // Helper function to count data
    function countData($pdo, $sql, $sid)
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['sid' => $sid]);
        return intval($stmt->fetchColumn());
    }

    // Get all statistics
    $total_books = countData($pdo, "SELECT COUNT(*) FROM books WHERE school_id = :sid", $school_id);
    $total_members = countData($pdo, "SELECT COUNT(*) FROM members WHERE school_id = :sid", $school_id);
    $total_borrowed = countData($pdo, "SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND returned_at IS NULL", $school_id);
    $total_overdue = countData($pdo, "SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status='overdue' AND returned_at IS NULL", $school_id);
    
    // Calculate available books
    $total_available = $total_books - $total_borrowed;
    
    // Get monthly borrow data for charts
    $stmt = $pdo->prepare("
        SELECT MONTH(borrowed_at) as month, COUNT(*) as count 
        FROM borrows 
        WHERE school_id = :sid AND YEAR(borrowed_at) = YEAR(NOW())
        GROUP BY MONTH(borrowed_at)
        ORDER BY MONTH(borrowed_at)
    ");
    $stmt->execute(['sid' => $school_id]);
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create array with 0 for all months
    $monthly_borrows = array_fill(0, 12, 0);
    foreach ($monthly_data as $row) {
        $monthly_borrows[$row['month'] - 1] = $row['count'];
    }
    
    // Return comprehensive statistics
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_books' => $total_books,
            'total_members' => $total_members,
            'total_borrowed' => $total_borrowed,
            'total_overdue' => $total_overdue,
            'total_available' => $total_available
        ],
        'chart_data' => [
            'status_chart' => [
                'labels' => ['Tersedia', 'Dipinjam', 'Terlambat'],
                'data' => [
                    $total_available,
                    $total_borrowed,
                    $total_overdue
                ],
                'backgroundColor' => ['#16a34a', '#2563eb', '#dc2626']
            ],
            'monthly_chart' => $monthly_borrows
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
