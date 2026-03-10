<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/MemberHelper.php';

try {
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];
    
    // Get member_id
    $memberHelper = new MemberHelper($pdo);
    $member_id = $memberHelper->getMemberId($user);

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        // Fallback for form-data
        $input = $_POST;
    }

    $title = $input['title'] ?? null;
    $author = $input['author'] ?? null;
    $action = $input['action'] ?? 'join'; // join or leave

    if (!$title || !$author) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Judul dan Penulis harus diisi']);
        exit;
    }

    if ($action === 'join') {
        // Check if student is already in waitlist for this book
        $checkStmt = $pdo->prepare(
            'SELECT id FROM waitlist 
             WHERE school_id = :sid AND member_id = :mid 
             AND book_title = :title AND book_author = :author 
             AND status = "pending"'
        );
        $checkStmt->execute([
            'sid' => $school_id,
            'mid' => $member_id,
            'title' => $title,
            'author' => $author
        ]);

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Anda sudah terdaftar di antrean buku ini']);
            exit;
        }

        // Insert into waitlist
        $insertStmt = $pdo->prepare(
            'INSERT INTO waitlist (school_id, book_title, book_author, member_id, status) 
             VALUES (:sid, :title, :author, :mid, "pending")'
        );
        $insertStmt->execute([
            'sid' => $school_id,
            'title' => $title,
            'author' => $author,
            'mid' => $member_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Berhasil masuk antrean! Kami akan memberi tahu Anda saat buku tersedia.']);
    } else {
        // Cancel waitlist entry
        $deleteStmt = $pdo->prepare(
            'UPDATE waitlist SET status = "cancelled" 
             WHERE school_id = :sid AND member_id = :mid 
             AND book_title = :title AND book_author = :author 
             AND status = "pending"'
        );
        $deleteStmt->execute([
            'sid' => $school_id,
            'mid' => $member_id,
            'title' => $title,
            'author' => $author
        ]);

        echo json_encode(['success' => true, 'message' => 'Berhasil keluar dari antrean']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
