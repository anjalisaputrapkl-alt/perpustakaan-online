<?php
// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isAuthenticated()
{
    return !empty($_SESSION['user']) && (
        !empty($_SESSION['user']['id']) ||
        (!empty($_SESSION['user']['is_scanner']) && $_SESSION['user']['is_scanner'] === true)
    );
}

/**
 * Dapatkan user yang sedang login
 */
function getAuthUser()
{
    return $_SESSION['user'] ?? null;
}

/*** Redirect ke login jika belum autentikasi */
function requireAuth()
{
    if (!isAuthenticated()) {
        // Redirect ke halaman login
        $loginUrl = '/perpustakaan-online/?login_required=1';
        header('Location: ' . $loginUrl, true, 302);
        exit;
    }
}

/**
 * Logout user
 */
function logout()
{
    session_destroy();
    // Redirect ke halaman index/home
    header('Location: /perpustakaan-online/index.php', true, 302);
    exit;
}

/**
 * Get the current user ID from session
 */
function getCurrentUserId()
{
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Get the current school ID from session
 */
function getCurrentSchoolId()
{
    return $_SESSION['user']['school_id'] ?? null;
}

/**
 * Creates a limited session for scanning
 */
function loginByScanKey($key)
{
    if (empty($key))
        return false;

    $pdo = require __DIR__ . '/db.php';

    $stmt = $pdo->prepare('SELECT id, name FROM schools WHERE scan_access_key = :key');
    $stmt->execute(['key' => $key]);
    $school = $stmt->fetch();

    if ($school) {
        $_SESSION['user'] = [
            'id' => 0,
            'name' => 'Mobile Scanner',
            'email' => 'scanner@' . $school['id'],
            'role' => 'librarian',
            'school_id' => $school['id'],
            'is_scanner' => true
        ];
        return true;
    }

    return false;
}
?>