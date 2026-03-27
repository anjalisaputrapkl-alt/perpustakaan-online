# Modul 1: Gerbang Utama (Login, Register, & OTP)

## 1. Pendaftaran Sekolah (Register)
**File: `public/api/register.php`**

Sistem menggunakan alur pendaftaran mandiri untuk sekolah dengan verifikasi email (OTP).
- **Cek Duplikasi**: Menggunakan `COUNT(*)` untuk memastikan email admin belum terdaftar.
- **Auto-slug**: Mengubah nama sekolah menjadi URL ramah (slug) menggunakan `preg_replace`.
- **Keamanan Password**: Menggunakan `password_hash()` dengan algoritma `PASSWORD_DEFAULT`.
- **OTP (One Time Password)**: Menghasilkan 6 digit kode unik yang berlaku selama 15 menit.

```php
// Menghasilkan kode verifikasi
$verification_code = generateVerificationCode();
$code_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Insert Admin dengan status is_verified = 0 (Belum Aktif)
$stmt = $pdo->prepare('INSERT INTO users (...) VALUES (...)');
```

## 2. Verifikasi Email
**File: `public/api/verify-and-login.php`**

Setelah mendaftar, user harus memasukkan kode OTP. 
- **Logika**: Sistem mencocokkan `verification_code` dan memastikan `NOW() < code_expires_at`.
- **Aksi**: Jika benar, kolom `is_verified` diubah menjadi `1`.

## 3. Sistem Masuk (Login)
**File: `public/api/login.php`**

Mendukung dua jenis entitas: **Admin Sekolah** (Email) dan **Anggota/Siswa** (NISN).
- **Logika Percabangan**:
  - `if ($user_type === 'student')`: Query ke tabel users berdasarkan kolom `nisn`.
  - `else`: Query ke tabel users berdasarkan kolom `email`.
- **Verifikasi**: Menggunakan `password_verify($password, $user['password'])`.
- **Session Handling**: Menyimpan `school_id` ke dalam session untuk isolasi data antar sekolah.

---
*Fakta Teknis: Isolasi data dimulai dari sini. Penanda `school_id` di Session adalah kunci utama agar data sekolah A tidak bisa dilihat oleh sekolah B.*
