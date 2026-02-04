<?php
/**
 * Search Students API - Search students by name or NISN
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    // Include auth
    require_once __DIR__ . '/../../src/auth.php';
    requireAuth();
    
    // Include database
    $pdo = require_once __DIR__ . '/../../src/db.php';
    
    $school_id = $_SESSION['user']['school_id'] ?? null;
    
    if (!$school_id) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'School ID not found in session'
        ]);
        exit;
    }
    
    $query = $_GET['q'] ?? '';
    $all = ($_GET['all'] ?? '0') === '1';
    
    if (!$all && strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'students' => []
        ]);
        exit;
    }
    
    // Search members by name or NISN - UPDATED TO JOIN SISWA TABLE FOR PHOTO
    if ($all) {
        $stmt = $pdo->prepare(
            'SELECT m.id, m.name, m.nisn, m.status, s.foto 
             FROM members m
             LEFT JOIN users u ON u.nisn = m.nisn AND u.school_id = m.school_id AND u.role = "student"
             LEFT JOIN siswa s ON s.id_siswa = u.id
             WHERE m.school_id = :school_id 
             ORDER BY m.name ASC'
        );
        $stmt->execute(['school_id' => $school_id]);
    } else {
        $searchTerm = '%' . $query . '%';
        $stmt = $pdo->prepare(
            'SELECT m.id, m.name, m.nisn, m.status, s.foto 
             FROM members m
             LEFT JOIN users u ON u.nisn = m.nisn AND u.school_id = m.school_id AND u.role = "student"
             LEFT JOIN siswa s ON s.id_siswa = u.id
             WHERE m.school_id = :school_id 
             AND (m.name LIKE :name_search OR m.nisn LIKE :nisn_search)
             ORDER BY m.name ASC 
             LIMIT 20'
        );
        $stmt->execute([
            'school_id' => $school_id,
            'name_search' => $searchTerm,
            'nisn_search' => $searchTerm
        ]);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("[SEARCH-STUDENTS-DB-ERROR] " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_type' => 'database'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("[SEARCH-STUDENTS-ERROR] " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_type' => 'general',
        'trace' => $e->getTraceAsString()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("[SEARCH-STUDENTS-FATAL] " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'error_type' => 'fatal',
        'trace' => $e->getTraceAsString()
    ]);
}
