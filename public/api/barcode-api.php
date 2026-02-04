<?php
// API endpoint untuk search books dan generate barcode
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
    // Session - hardcode school_id for now (testing)
    if (!isset($_SESSION)) {
        session_start();
    }

    require_once __DIR__ . '/../../src/db.php';
    require_once __DIR__ . '/../../src/BarcodeModel.php';

    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $school_id = $_SESSION['user']['school_id'] ?? null;
    $user_id = $_SESSION['user']['id'] ?? null;

    if (!$school_id) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: School ID not found']);
        exit;
    }

    if ($action === 'get_all_ids') {
        try {
            $stmt = $pdo->prepare('SELECT id FROM books WHERE school_id = ?');
            $stmt->execute([$school_id]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'count' => count($ids),
                'ids' => $ids
            ]);
        } catch (Exception $e) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'search') {
        $query = trim($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Minimal 2 karakter']);
            exit;
        }
        
        try {
            $barcode = new BarcodeModel($pdo, $school_id);
            $results = $barcode->searchBooks($query, 20);
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'count' => count($results),
                'books' => $results
            ]);
        } catch (Exception $e) {
            error_log("Barcode API Search Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'generate_bulk') {
        $book_ids = $_POST['book_ids'] ?? [];
        if (is_string($book_ids)) {
            $book_ids = json_decode($book_ids, true) ?: explode(',', $book_ids);
        }
        
        if (empty($book_ids)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No book IDs provided']);
            exit;
        }
        
        try {
            $barcodeModel = new BarcodeModel($pdo, $school_id);
            $results = [];
            
            foreach ($book_ids as $id) {
                $id = intval($id);
                if ($id <= 0) continue;
                
                $book = $barcodeModel->getBookById($id);
                if ($book) {
                    $res = $barcodeModel->generateCombinedBarcode($book);
                    if ($res['success']) {
                        $results[] = $res;
                        try {
                            $barcodeModel->logBarcodeGeneration($id, $user_id);
                        } catch (Exception $e) {}
                    }
                }
            }
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'count' => count($results),
                'results' => $results
            ]);
        } catch (Exception $e) {
            error_log("Barcode API Bulk Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'generate') {
        $book_id = intval($_POST['book_id'] ?? 0);
        
        if ($book_id <= 0) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid book ID']);
            exit;
        }
        
        try {
            $barcode = new BarcodeModel($pdo, $school_id);
            $book = $barcode->getBookById($book_id);
            
            if (!$book) {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Buku tidak ditemukan']);
                exit;
            }
            
            $result = $barcode->generateCombinedBarcode($book);
            
            if ($result['success']) {
                try {
                    $barcode->logBarcodeGeneration($book_id, $user_id);
                } catch (Exception $e) {
                    error_log("Log error: " . $e->getMessage());
                }
                
                ob_end_clean();
                echo json_encode($result);
            } else {
                ob_end_clean();
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
        } catch (Exception $e) {
            error_log("Barcode API Generate Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    error_log("Barcode API Exception: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
