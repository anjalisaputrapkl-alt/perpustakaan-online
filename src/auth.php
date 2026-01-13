<?php

/**
 * Mulai session dan cek autentikasi
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isAuthenticated()
{
    return !empty($_SESSION['user']);
}

/**
 * Dapatkan user yang sedang login
 */
function getAuthUser()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Redirect ke login jika belum autentikasi
 */
function requireAuth()
{
    if (!isAuthenticated()) {
        header('Location: /perpustakaan-online/public/login.php');
        exit;
    }
}

/**
 * Logout user
 */
function logout()
{
    session_destroy();
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

?>