# Dokumentasi Sistem Autentikasi

## Perubahan yang Dilakukan

Telah ditambahkan sistem autentikasi yang melindungi halaman-halaman yang memerlukan login. Berikut adalah detail perubahannya:

### 1. File Baru: `src/auth.php`

File helper autentikasi yang menyediakan fungsi-fungsi:

- **`isAuthenticated()`** - Mengecek apakah user sudah login
- **`getAuthUser()`** - Mendapatkan data user yang sedang login
- **`requireAuth()`** - Mengarahkan ke login jika belum autentikasi
- **`logout()`** - Melakukan logout dan mengarahkan ke login

### 2. Halaman-halaman yang Dilindungi

Halaman berikut sekarang memerlukan login:

- `public/index.php` - Dashboard utama
- `public/books.php` - Manajemen buku
- `public/members.php` - Manajemen anggota
- `public/borrows.php` - Manajemen peminjaman
- `public/settings.php` - Pengaturan

### 3. Halaman yang Tidak Dilindungi (Public)

- `public/login.php` - Halaman login
- `public/register.php` - Halaman registrasi sekolah baru

### 4. Halaman Logout

- `public/logout.php` - Menggunakan fungsi `logout()` dari helper

## Cara Kerja

1. Ketika user membuka halaman yang dilindungi tanpa login, akan otomatis diarahkan ke `login.php`
2. User harus memasukkan email dan password untuk login
3. Setelah login berhasil, user akan diarahkan ke dashboard (`index.php`)
4. User dapat logout dengan mengklik menu logout, yang akan menghapus session dan mengarahkan ke login

## Implementasi di File

Setiap halaman yang dilindungi dimulai dengan:

```php
<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
```

Ini memastikan:

1. Session dimulai (di `auth.php`)
2. Autentikasi divalidasi (jika tidak login, diarahkan ke login.php)
3. User dapat melanjutkan mengakses halaman

## Keamanan

- Menggunakan PHP Session untuk menyimpan data login
- Password di-hash menggunakan `password_hash()` dan diverifikasi dengan `password_verify()`
- Session dihapus ketika logout
