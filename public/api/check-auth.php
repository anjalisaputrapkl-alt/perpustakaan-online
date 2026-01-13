<?php

// Mulai session untuk cek autentikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header JSON
header('Content-Type: application/json');

// Cek apakah user sudah login
$authenticated = !empty($_SESSION['user']);

// Return response JSON
echo json_encode([
    'authenticated' => $authenticated,
    'user' => $authenticated ? [
        'id' => $_SESSION['user']['id'] ?? null,
        'name' => $_SESSION['user']['name'] ?? null,
    ] : null
]);

?>