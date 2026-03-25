# Modul 2: Sesi Autentikasi & Dashboard

## A. Sistem Keamanan Halaman (Autentikasi Session)
**File: `src/auth.php`**

**1. Pengecekan Keberadaan Sesi**
Di setiap halaman private seperti `borrows.php`, fungsi `requireAuth()` dipanggil. Jika array `$_SESSION['user']` kosong (artinya user belum login via `login.php`), maka akan langsung di-redirect lemparkan ke halaman login utama.
```php
function isAuthenticated()
{
    return !empty($_SESSION['user']) && (
        !empty($_SESSION['user']['id']) || 
        (!empty($_SESSION['user']['is_scanner']) && $_SESSION['user']['is_scanner'] === true)
    );
}

function requireAuth()
{
    if (!isAuthenticated()) {
        $loginUrl = '/perpustakaan-online/?login_required=1';
        header('Location: ' . $loginUrl, true, 302);
        exit;
    }
}
```

## B. Pemisahan Tampilan UI Dashboard (Authorization)
**File: `public/index.php` (Admin) & `public/student-dashboard.php` (Siswa)**

**1. Verifikasi Kepemilikan Data**
Pada halaman dashboard admin, tampilan hanya dirender setelah sistem mencocokkan `school_id` admin dari server sessions agar tak bercampur dengan sekolah lain.
```php
$user = $_SESSION['user'];
$sid = $user['school_id']; // Ditarik aman dari Session Backend

// Contoh query statistic dashboard dengan membatasi cakupan pada school_id tertentu
$stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE school_id = :sid');
$stmt->execute(['sid' => $sid]);
$totalBooks = $stmt->fetchColumn();
```
