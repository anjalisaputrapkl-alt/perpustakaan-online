<?php
session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        // Determine redirect URL based on user_type
        $redirect_url = 'index.php';

        if ($user_type === 'student') {
            // Redirect siswa ke dashboard siswa
            $redirect_url = 'student-dashboard.php';
        } else if ($user_type === 'school' || $user['role'] === 'admin') {
            // Redirect admin/pustakawan ke dashboard admin
            $redirect_url = 'index.php';
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect_url' => $redirect_url
        ]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
    exit;
}
