<?php
session_start();
$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/FavoriteModel.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = $_SESSION['user']['id'];
$action = $_GET['action'] ?? null;

try {
    $model = new FavoriteModel($pdo);

    switch ($action) {
        case 'categories':
            // Ambil daftar kategori
            $categories = $model->getCategories();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            break;

        case 'books_by_category':
            // Ambil buku berdasarkan kategori
            $category = $_GET['category'] ?? null;
            $books = $model->getBooksByCategory($category);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $books,
                'total' => count($books)
            ]);
            break;

        case 'add':
            // Tambah ke favorit
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }

            $bookId = $_POST['id_buku'] ?? null;
            $category = $_POST['kategori'] ?? null;

            if (!$bookId || !is_numeric($bookId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID buku tidak valid']);
                exit;
            }

            $model->addFavorite($studentId, (int)$bookId, $category);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Buku berhasil ditambahkan ke favorit'
            ]);
            break;

        case 'list':
            // Ambil list favorit
            $category = $_GET['category'] ?? null;
            $favorites = $model->getFavorites($studentId, $category);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $favorites,
                'total' => count($favorites)
            ]);
            break;

        case 'remove':
            // Hapus dari favorit
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }

            $favoriteId = $_POST['id_favorit'] ?? null;

            if (!$favoriteId || !is_numeric($favoriteId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID favorit tidak valid']);
                exit;
            }

            $model->removeFavorite($studentId, (int)$favoriteId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Buku berhasil dihapus dari favorit'
            ]);
            break;

        case 'count':
            // Hitung total favorit
            $count = $model->countFavorites($studentId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => htmlspecialchars($e->getMessage())
    ]);
}
?>
