# 📧 Email Verification System - Dokumentasi

## Pengenalan

Sistem verifikasi email telah ditambahkan ke platform Perpustakaan Digital untuk meningkatkan keamanan dan validasi saat pendaftaran sekolah baru. Ketika sekolah mendaftar, mereka akan menerima kode verifikasi 6 digit via email yang harus dimasukkan untuk mengaktifkan akun mereka.

## Alur Pendaftaran dengan Verifikasi Email

```
1. Admin Sekolah klik "Daftarkan Sekarang"
   ↓
2. Isi form registrasi (nama sekolah, nama admin, email, password)
   ↓
3. Klik tombol "Daftarkan Sekolah"
   ↓
4. Sistem membuat akun dengan status is_verified = 0
   ↓
5. Generate kode verifikasi 6 digit random
   ↓
6. Kirim email ke alamat email admin dengan kode verifikasi
   ↓
7. Modal verifikasi otomatis terbuka
   ↓
8. Admin memasukkan kode 6 digit dari email
   ↓
9. Sistem validasi kode (check: kesamaan kode, cek expiry 15 menit)
   ↓
10. Jika valid → status akun berubah menjadi verified
    ↓
11. Otomatis login dan redirect ke dashboard admin
    ↓
12. Akun sekarang aktif dan siap digunakan
```

## File yang Ditambahkan / Diubah

### File Baru:

1. **`src/EmailHelper.php`** - Helper functions untuk email
   - `sendVerificationEmail()` - Mengirim email verifikasi dengan kode
   - `generateVerificationCode()` - Generate kode 6 digit random
   - `isVerificationCodeExpired()` - Cek apakah kode sudah expired

2. **`public/api/verify-email.php`** - API endpoint untuk verifikasi kode
   - Menerima user_id dan verification_code
   - Validasi kode
   - Update status user menjadi verified
   - Mengembalikan user info untuk auto-login

3. **`sql/migrations/add_email_verification.sql`** - SQL migration script

### File Yang Dimodifikasi:

1. **`index.php`**
   - Menambahkan modal HTML untuk verifikasi email
   - Menambahkan JavaScript untuk handling:
     - Input kode 6 digit dengan auto-focus
     - Timer countdown 15 menit
     - Validasi dan submission form
     - Auto-login setelah verifikasi berhasil

2. **`public/api/register.php`**
   - Generate verification code
   - Simpan ke database dengan is_verified = 0
   - Kirim email verifikasi
   - Return user_id dan email untuk modal verifikasi

3. **`assets/css/landing.css`**
   - Styling untuk verification modal
   - Styling untuk code input fields
   - Styling untuk timer dan error messages

4. **`sql/run-migration.php`**
   - Updated untuk menjalankan email verification migrations

## Database Schema

### Kolom Baru di Tabel `users`:

```sql
ALTER TABLE `users` ADD COLUMN `verification_code` VARCHAR(10) NULL;
ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `verified_at` TIMESTAMP NULL;

-- Indexes untuk performa
ALTER TABLE `users` ADD INDEX `idx_verification_code` (`verification_code`);
ALTER TABLE `users` ADD INDEX `idx_is_verified` (`is_verified`);
```

## Cara Menggunakan

### Step 1: Jalankan Database Migration

Buka browser dan kunjungi:

```
http://localhost/perpustakaan-online/sql/run-migration.php
```

Script ini akan otomatis:

- Menambahkan kolom verification_code
- Menambahkan kolom is_verified
- Menambahkan kolom verified_at
- Membuat indexes untuk performa
- Mengupdate akun existing menjadi verified

### Step 2: Konfigurasi Email (Opsional)

Sistem menggunakan fungsi `mail()` PHP yang memerlukan konfigurasi SMTP di server.

**Untuk development dengan XAMPP:**

Edit file `php.ini` di folder XAMPP:

```ini
[mail function]
SMTP = smtp.mailtrap.io
smtp_port = 2525
sendmail_from = your-email@domain.com
```

Atau gunakan tools seperti:

- **Mailtrap** (free tier) - https://mailtrap.io
- **Mailhog** (local SMTP server)
- **SendGrid** (production)

### Step 3: Test Pendaftaran

1. Buka `http://localhost/perpustakaan-online/`
2. Klik "Daftarkan Sekarang"
3. Isi form dengan data:
   - Nama Sekolah: `SMA Test`
   - Nama Admin: `Admin Test`
   - Email: `test@sch.id`
   - Password: `password123`
4. Klik "Daftarkan Sekolah"
5. Modal verifikasi akan muncul
6. Masukkan kode 6 digit yang dikirim ke email
7. Klik "Verifikasi Email"
8. Jika berhasil, otomatis login dan redirect ke dashboard

## Fitur-Fitur

### ✅ Verifikasi Email Input

- Input 6 digit dengan auto-focus antar field
- Hanya menerima input angka (numeric)
- Auto-fokus ke field berikutnya saat input
- Backspace untuk kembali ke field sebelumnya

### ⏱️ Timer Countdown

- Menampilkan sisa waktu (15 menit)
- Auto-enable tombol "Kirim Ulang" saat <1 menit
- Peringatan warna merah saat kadaluarsa

### 🔒 Keamanan

- Kode verification hanya valid 15 menit
- Kode divalidasi dari database (bukan di client)
- Password dienkripsi dengan PASSWORD_DEFAULT
- Database indexes untuk performa query

### 📧 Email Notification

- Email HTML yang profesional
- Informasi lengkap: nama admin, nama sekolah, kode verifikasi
- Instruksi keamanan di email
- Warning tidak membagikan kode

### 🎨 User Experience

- Modal yang user-friendly
- Error messages yang jelas
- Success message sebelum redirect
- Loading state pada button submit

## API Endpoints

### POST `/public/api/register.php`

Register sekolah baru dan kirim verifikasi email.

**Request:**

```json
{
  "school_name": "SMA Maju Jaya",
  "admin_name": "Budi Santoso",
  "admin_email": "budi@sch.id",
  "admin_password": "password123"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Pendaftaran berhasil. Silakan verifikasi email Anda.",
  "user_id": 42,
  "email": "budi@sch.id"
}
```

**Error Response (400/500):**

```json
{
  "success": false,
  "message": "Email sudah terdaftar" / "Gagal mengirim email" / etc
}
```

### POST `/public/api/verify-email.php`

Verifikasi kode dan aktivasi akun.

**Request:**

```json
{
  "user_id": 42,
  "verification_code": "123456"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Email berhasil diverifikasi! Anda sekarang dapat login.",
  "user": {
    "id": 42,
    "school_id": 15,
    "name": "Budi Santoso",
    "email": "budi@sch.id",
    "role": "admin",
    "is_verified": 1
  },
  "redirect_url": "admin-dashboard.php"
}
```

**Error Response (401/500):**

```json
{
  "success": false,
  "message": "Kode verifikasi tidak valid" / "Kode verifikasi telah kadaluarsa" / etc
}
```

## Enhancement di Masa Depan

Fitur-fitur yang bisa ditambahkan:

1. **Kirim Ulang Kode**
   - Tombol "Kirim Ulang" untuk generate kode baru
   - Rate limiting (max 3x per jam)

2. **Social Login**
   - Login dengan Google
   - Login dengan Microsoft

3. **Two-Factor Authentication**
   - OTP via SMS
   - Authenticator app

4. **Email Templates**
   - Multiple template languages
   - Branding email dengan logo sekolah

5. **Audit Logging**
   - Log semua aktivitas verifikasi
   - Track failed attempts

## Troubleshooting

### Email tidak terkirim

**Masalah:** Pendaftaran berhasil tapi email tidak sampai

**Solusi:**

1. Cek konfigurasi SMTP di php.ini
2. Cek mail logs: `/var/log/mail.log` (Linux) atau Event Viewer (Windows)
3. Gunakan Mailtrap/Mailhog untuk testing
4. Cek spam folder email

### Database migration error

**Masalah:** Migration error saat membuka run-migration.php

**Solusi:**

1. Cek koneksi database di sql/run-migration.php
2. Jalankan manual SQL queries dari phpMyAdmin:

```sql
ALTER TABLE `users` ADD COLUMN `verification_code` VARCHAR(10) NULL AFTER `password`;
ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0 AFTER `verification_code`;
ALTER TABLE `users` ADD COLUMN `verified_at` TIMESTAMP NULL AFTER `is_verified`;
ALTER TABLE `users` ADD INDEX `idx_verification_code` (`verification_code`);
ALTER TABLE `users` ADD INDEX `idx_is_verified` (`is_verified`);
```

### Kode tidak bisa disubmit

**Masalah:** Tombol verifikasi tidak berfungsi

**Solusi:**

1. Pastikan semua 6 digit sudah diisi
2. Cek error message di browser console (F12)
3. Pastikan JavaScript enabled
4. Clear browser cache (Ctrl+Shift+Delete)

## Testing Checklist

- [ ] Database migration berhasil
- [ ] Form registrasi menerima input
- [ ] Email verifikasi terkirim
- [ ] Modal verifikasi terbuka otomatis
- [ ] Input kode 6 digit bekerja
- [ ] Timer countdown berjalan
- [ ] Verifikasi berhasil
- [ ] Otomatis login dan redirect
- [ ] Akun bisa login di menu login
- [ ] Error handling (kode salah, expired, dll)

## Support & Documentation

Untuk pertanyaan atau laporan bug, silakan hubungi:

- Email: support@perpustakaan.edu
- Phone: (0274) 555-1234
- Hours: Senin-Jumat 09:00-17:00

---

**Version:** 1.0.0  
**Last Updated:** 2026-01-22  
**Maintained By:** Development Team
