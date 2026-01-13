<?php
session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$school_name = trim($_POST['school_name'] ?? '');
$admin_name = trim($_POST['admin_name'] ?? '');
$admin_email = trim($_POST['admin_email'] ?? '');
$admin_password = $_POST['admin_password'] ?? '';

if (empty($school_name) || empty($admin_name) || empty($admin_email) || empty($admin_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

if (strlen($admin_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $admin_email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar']);
        exit;
    }

    // Create school
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school_name)));
    $stmt = $pdo->prepare('INSERT INTO schools (name, slug) VALUES (:name, :slug)');
    $stmt->execute(['name' => $school_name, 'slug' => $slug]);
    $school_id = $pdo->lastInsertId();

    // Create admin user
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (school_id, name, email, password, role) VALUES (:school_id, :name, :email, :password, "admin")');
    $stmt->execute([
        'school_id' => $school_id,
        'name' => $admin_name,
        'email' => $admin_email,
        'password' => $password_hash
    ]);

    echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
    exit;
}
