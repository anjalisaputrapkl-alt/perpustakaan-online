<?php
session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/EmailHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$verification_code = trim($_POST['verification_code'] ?? '');

if (!$user_id || empty($verification_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID dan kode verifikasi harus diisi']);
    exit;
}

try {
    // Get user dengan verification_code
    $stmt = $pdo->prepare(
        'SELECT id, email, verification_code, created_at 
         FROM users WHERE id = :user_id AND verification_code = :code'
    );
    $stmt->execute([
        'user_id' => $user_id,
        'code' => $verification_code
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kode verifikasi tidak valid']);
        exit;
    }

    // Cek apakah kode sudah expired (15 menit)
    if (isVerificationCodeExpired($user['created_at'], 15)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Kode verifikasi telah kadaluarsa. Silakan daftar ulang.'
        ]);
        exit;
    }

    // Update user menjadi verified
    $stmt = $pdo->prepare(
        'UPDATE users 
         SET is_verified = 1, 
             verified_at = NOW(), 
             verification_code = NULL 
         WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);

    // Set session untuk auto login
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = 'school';

    // Get updated user info untuk response
    $stmt = $pdo->prepare(
        'SELECT id, school_id, name, email, role, is_verified 
         FROM users WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);
    $verified_user = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Email berhasil diverifikasi! Anda sekarang dapat login.',
        'user' => $verified_user,
        'redirect_url' => 'index.php'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
    exit;
}
