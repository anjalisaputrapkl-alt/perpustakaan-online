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
    
    // Get monthly borrow data for charts (Current Year)
    $stmt = $pdo->prepare("
        SELECT MONTH(borrowed_at) as month, COUNT(*) as count 
        FROM borrows 
        WHERE school_id = :sid AND YEAR(borrowed_at) = YEAR(NOW())
        GROUP BY MONTH(borrowed_at)
        ORDER BY MONTH(borrowed_at)
    ");
    $stmt->execute(['sid' => $school_id]);
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $monthly_borrows = array_fill(0, 12, 0);
    foreach ($monthly_data as $row) {
        $monthly_borrows[$row['month'] - 1] = $row['count'];
    }

    // Get Weekly Trends (Last 7 Days)
    $weekly_trends = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND DATE(borrowed_at) = :dt");
        $stmt->execute(['sid' => $school_id, 'dt' => $date]);
        $weekly_trends[] = [
            'label' => date('D', strtotime($date)),
            'count' => intval($stmt->fetchColumn())
        ];
    }

    // Top 5 Most Borrowed Books
    $stmt = $pdo->prepare("
        SELECT b.title, COUNT(br.id) as borrow_count 
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        WHERE br.school_id = :sid
        GROUP BY br.book_id
        ORDER BY borrow_count DESC
        LIMIT 5
    ");
    $stmt->execute(['sid' => $school_id]);
    $top_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 Most Active Members
    $stmt = $pdo->prepare("
        SELECT m.name, COUNT(br.id) as borrow_count 
        FROM borrows br
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid
        GROUP BY br.member_id
        ORDER BY borrow_count DESC
        LIMIT 5
    ");
    $stmt->execute(['sid' => $school_id]);
    $top_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
            'monthly_chart' => $monthly_borrows,
            'weekly_trend' => $weekly_trends,
            'top_books' => $top_books,
            'top_members' => $top_members
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
