# Modul 2: Autentikasi, Sesi, & Dashboard

## 1. Pelindung Halaman (Middleware Sederhana)
**File: `src/auth.php`**

Setiap halaman di folder `public/` (kecuali index luar) mewajibkan login.
- **Fungsi `requireAuth()`**: Mengecek apakah `$_SESSION['user']` ada.
- **Redirect**: Jika akses ilegal terdeteksi, user dilempar balik ke `index.php`.

```php
function requireAuth() {
    if (!isset($_SESSION['user'])) {
        header('Location: /perpustakaan-online/index.php');
        exit;
    }
}
```

## 2. Dashboard Berbasis Role (Peran)
Sistem mengenali siapa yang login dan memberikan dashboard yang berbeda:
- **Admin (`index.php`)**: Menampilkan statistik agregat (`COUNT`) dari total buku, anggota, dan denda tertunggak di sekolah tersebut.
- **Siswa (`student-dashboard.php`)**: Menampilkan "Kartu Anggota Digital", daftar buku yang sedang dipinjam, dan buku favorit.

## 3. Isolasi Data Multi-Sekolah
Di setiap query database pada dashboard, sistem **WAJIB** menyertakan parameter `school_id`.
```php
// Contoh mengambil statistik hanya untuk sekolah yang sedang login
$stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE school_id = :sid');
$stmt->execute(['sid' => $_SESSION['user']['school_id']]);
```

---
*Fakta Teknis: Session di PHP ini bersifat Server-Side, artinya data `school_id` tidak bisa dimanipulasi oleh user melalui browser, menjadikannya sangat aman untuk aplikasi multi-tenant.*
