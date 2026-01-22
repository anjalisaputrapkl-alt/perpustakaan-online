# 🔧 FIX: Error 400 saat Login Setelah Register

## 🚨 Masalah

Ketika user mendaftar sekolah baru dan kemudian mencoba login, sistem menampilkan error:

```
POST http://localhost/perpustakaan-online/public/api/login.php 400 (Bad Request)
```

## 🔍 Root Cause

Ada dua kemungkinan penyebab:

### 1. **Database Migration Belum Dijalankan**

- Kolom `is_verified`, `verification_code`, `verified_at` belum ada di tabel `users`
- Sistem email verification tidak bisa berfungsi
- Login gagal karena struktur database tidak sesuai

### 2. **User Belum Terverifikasi Email**

- User yang baru register memiliki `is_verified = 0`
- Login diblokir karena email belum diverifikasi
- Message error yang ditampilkan mungkin kurang jelas

## ✅ SOLUSI (3 LANGKAH)

### STEP 1: Jalankan Database Migration

**Buka di browser:**

```
http://localhost/perpustakaan-online/sql/run-migration.php
```

**Tunggu sampai muncul pesan:**

```
✅ Migration completed successfully!
```

**Ini akan:**

- ✅ Menambahkan kolom `verification_code`
- ✅ Menambahkan kolom `is_verified` (default = 0)
- ✅ Menambahkan kolom `verified_at`
- ✅ Membuat database indexes

---

### STEP 2: Verifikasi Database (Optional)

**Buka di browser untuk check:**

```
http://localhost/perpustakaan-online/debug-db.php
```

**Harusnya muncul:**

```
✅ Kolom verification sudah ada:
  - verification_code
  - is_verified
  - verified_at
```

---

### STEP 3: Test Alur Lengkap

#### Scenario A: Registrasi & Verifikasi Baru

1. Buka: `http://localhost/perpustakaan-online/`
2. Klik "Daftarkan Sekarang"
3. Isi form:
   - Nama Sekolah: `SMA Test`
   - Nama Admin: `Admin Test`
   - Email: `test@sch.id`
   - Password: `password123`
4. Klik "Daftarkan Sekolah"
5. ✅ Modal verifikasi muncul
6. Masukkan kode 6 digit dari email
7. ✅ Otomatis login & redirect ke dashboard

#### Scenario B: Login Akun yang Sudah Terverifikasi

1. Buka: `http://localhost/perpustakaan-online/`
2. Klik "Login"
3. Pilih "Admin / Pustakawan"
4. Masukkan email & password
5. ✅ Login berhasil

#### Scenario C: Login Akun Belum Terverifikasi (Error Flow)

Jika user mencoba login akun yang belum diverifikasi:

- ❌ Error message: "Silakan verifikasi email Anda terlebih dahulu"
- User harus kembali ke email dan verifikasi terlebih dahulu

---

## 🐛 Troubleshooting

### Error: "Silakan verifikasi email Anda terlebih dahulu"

**Penyebab:** Email belum diverifikasi
**Solusi:**

1. Check email untuk kode verifikasi
2. Jika email tidak ada, kemungkinan server SMTP tidak configured
3. Gunakan Mailtrap untuk testing: https://mailtrap.io

### Error: "Email atau password salah"

**Penyebab:**

1. Email tidak terdaftar di database
2. Password salah
3. User mencoba login dengan email yang berbeda

**Solusi:**

1. Pastikan email yang digunakan saat registrasi
2. Pastikan password sesuai (case-sensitive)
3. Reset password jika lupa

### Error: "Method not allowed"

**Penyebab:** POST request tidak dikirim dengan benar

**Solusi:**

1. Check network tab di browser console (F12)
2. Pastikan form method = "POST"
3. Clear browser cache (Ctrl+Shift+Delete)

---

## 🔐 Security Notes

✅ **Email Verification Required** - Semua akun baru harus verifikasi email
✅ **Password Encrypted** - Password tidak disimpan plain text
✅ **Token Expiry** - Kode verifikasi hanya berlaku 15 menit
✅ **One-Time Use** - Kode hanya bisa dipakai sekali

---

## 📝 Database Check

**Untuk manual check di phpMyAdmin:**

1. Buka: `http://localhost/phpmyadmin`
2. Select database: `perpustakaan_online`
3. Select table: `users`
4. Click tab "Structure"
5. Check kolom:
   - ✅ `id` (int)
   - ✅ `email` (varchar)
   - ✅ `password` (varchar)
   - ✅ `is_verified` (tinyint) ← HARUS ADA
   - ✅ `verification_code` (varchar) ← HARUS ADA
   - ✅ `verified_at` (timestamp) ← HARUS ADA

Jika kolom belum ada → Jalankan migration!

---

## 📧 Email Configuration

### Untuk Development:

**Option 1: Gunakan Mailtrap (Recommended)**

1. Daftar di https://mailtrap.io
2. Ambil SMTP credentials
3. Edit `php.ini`:

```ini
[mail function]
SMTP = smtp.mailtrap.io
smtp_port = 2525
sendmail_from = test@mailtrap.io
```

**Option 2: Gunakan native PHP mail()**

- Sistem sudah siap
- Tergantung server SMTP configuration

### Untuk Production:

**Gunakan professional email service:**

- SendGrid
- AWS SES
- Gmail SMTP (dengan app password)

---

## ✨ Success Checklist

- [ ] Run migration script
- [ ] Check database columns
- [ ] Register akun baru
- [ ] Terima email verifikasi
- [ ] Input kode 6 digit
- [ ] Verifikasi berhasil
- [ ] Auto-login bekerja
- [ ] Dashboard accessible
- [ ] Logout & login normal bekerja

---

## 📞 Need More Help?

**File Documentation:**

- Full setup: `EMAIL_VERIFICATION_DOCS.md`
- Quick reference: `QUICK_START.md`
- Implementation: `IMPLEMENTATION_GUIDE.md`
- Code examples: `CODE_EXAMPLES.php`

**Support:**

- Email: support@perpustakaan.edu
- Phone: (0274) 555-1234
- Hours: Senin-Jumat 09:00-17:00

---

**Last Updated:** 2026-01-22
**Status:** ✅ Fixed
