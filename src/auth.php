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
 * Untuk multi-tenant, redirect ke login-modal.php dari subdomain
 */
function requireAuth()
{
    if (!isAuthenticated()) {
        // Jika di subdomain sekolah, gunakan login-modal.php dari subdomain
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = explode(':', $_SERVER['HTTP_HOST'])[0];
            $parts = explode('.', $host);

            if (count($parts) >= 3) {
                // Subdomain sekolah
                header('Location: /public/login-modal.php');
                exit;
            }
        }

        // Default login page
        header('Location: /public/login.php');
        exit;
    }
}

/**
 * Logout user
 */
function logout()
{
    session_destroy();
    header('Location: /');
    exit;
}

?>