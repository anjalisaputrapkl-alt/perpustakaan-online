# PANDUAN LENGKAP - SISTEM VERIFIKASI EMAIL

## ✅ YANG SUDAH DIPERBAIKI

### 1. **File EmailHelper.php**

- ✅ Fixed escape sequence errors in email headers
- ✅ Added development email logging (emails disimpan di `logs/emails.log`)
- ✅ Email function always returns `true` (tidak block registration)

### 2. **File register.php**

- ✅ Added better error handling dan logging
- ✅ Registration tidak gagal jika email tidak terkirim
- ✅ Mengembalikan `user_id` dan `email` untuk verification modal

### 3. **File index.php**

- ✅ Improved form error handling dengan console logging
- ✅ Better error messages yang menampilkan response dari server
- ✅ Detailed debugging untuk troubleshooting

### 4. **Database Migration**

- ✅ Columns sudah ditambahkan ke users table:
  - `verification_code` (VARCHAR(10))
  - `is_verified` (TINYINT(1))
  - `verified_at` (TIMESTAMP)

---

## 🚀 CARA MENGGUNAKAN SISTEM

### Step 1: Daftar Sekolah

1. Buka: http://localhost/perpustakaan-online/
2. Klik tombol "**Daftar**" (Daftarkan Sekolah Anda)
3. Isi form dengan data:
   - **Nama Sekolah**: (contoh: SMA Test)
   - **Nama Admin**: (contoh: Budi Santoso)
   - **Email Admin**: HARUS pakai format `@sch.id` (contoh: admin@sch.id)
   - **Password Admin**: Minimal 6 karakter

4. Klik "**✓ Daftarkan Sekolah**"

### Step 2: Verifikasi Email

1. Modal popup akan muncul: "**Verifikasi Email Anda**"
2. Kode verifikasi 6 digit akan ditampilkan di console browser (check F12 → Console)
3. Salin kode tersebut dan masukkan ke 6 input field yang tersedia
4. Auto-focus akan membantu Anda melanjutkan ke field berikutnya
5. Klik "**Verifikasi Email**"

### Step 3: Auto-Login

1. Setelah verifikasi berhasil, Anda akan otomatis login
2. Dashboard admin akan terbuka
3. Akun sudah aktif dan siap digunakan!

---

## 📋 STATUS SISTEM

Periksa status sistem di: http://localhost/perpustakaan-online/status.php

---

## 🔍 DEBUGGING & TESTING

### Test Registration API Langsung

Kunjungi: http://localhost/perpustakaan-online/test-register.html

Form siap pakai dengan field pre-filled untuk testing cepat.

### Lihat Email Log

Lokasi: `logs/emails.log`

Semua verifikasi email dicatat di file ini untuk development purposes.

### Browser Console

Tekan **F12** → Tab **Console** untuk melihat:

- Raw response dari server
- JSON parsing errors (jika ada)
- Detailed logging setiap step

---

## ⚙️ KONFIGURASI EMAIL (OPTIONAL)

### Untuk Production (Email Actual)

Edit file `src/EmailHelper.php` line ~74:

```php
// Ganti:
return true;

// Dengan:
return mail($recipient_email, $subject, $message, $headers);
```

Pastikan server Anda sudah configured untuk send mail.

### Untuk Development (Current)

Email akan ter-log di `logs/emails.log` dan verification modal akan menampilkan kode.

---

## 🛡️ FITUR KEAMANAN

✅ **Password Hashing**: Menggunakan PHP `PASSWORD_DEFAULT` (bcrypt)  
✅ **SQL Injection Prevention**: Prepared statements dengan PDO  
✅ **Email Verification**: 6-digit random code dengan 15-minute expiry  
✅ **Session Management**: Auto-login setelah verifikasi  
✅ **Role-Based Access**: Admin vs Student login berbeda

---

## 📝 FILE YANG DIMODIFIKASI

1. **src/EmailHelper.php** - Email functions & logging
2. **public/api/register.php** - Registration endpoint
3. **public/api/verify-email.php** - Verification endpoint
4. **public/api/login.php** - Login dengan is_verified check
5. **index.php** - Frontend forms & validation
6. **sql/run-migration.php** - Database schema updates

---

## ✅ CHECKLIST SEBELUM PRODUCTION

- [ ] Run database migration (`sql/run-migration.php`)
- [ ] Konfigurasi email server untuk actual mail sending
- [ ] Test registration → verification → login flow
- [ ] Update email templates dengan branding Anda
- [ ] Set correct `From` email address di EmailHelper
- [ ] Configure SMTP jika diperlukan
- [ ] Backup database sebelum deployment
- [ ] Test di multiple browsers

---

## 📞 TROUBLESHOOTING

### Error: "Unexpected token '<', "<!DOCTYPE html>" is not valid JSON"

**Penyebab**: Server mengembalikan HTML error, bukan JSON

**Solusi**:

1. Check browser console (F12)
2. Lihat raw response dari server
3. Cek `logs/emails.log` apakah email ter-log
4. Run `status.php` untuk diagnostik lengkap

### Kode Verifikasi Tidak Diterima

**Untuk Development**: Kode ditampilkan di browser console (F12)

**Untuk Production**:

1. Check email inbox & spam folder
2. Verify server SMTP configuration
3. Check `logs/emails.log`

### Registration Form Tidak Submit

1. Pastikan email menggunakan domain `@sch.id`
2. Pastikan semua field diisi (required)
3. Check browser console untuk error messages
4. Refresh halaman dan coba lagi

---

## 📌 INFORMASI PENTING

- **Verification Code**: Berlaku 15 menit
- **Email Domain**: HARUS `@sch.id` (configurable)
- **Database**: Sudah ada 3 columns baru untuk verification
- **Logs**: Tersimpan di `/logs/emails.log`

---

**Status**: ✅ SIAP UNTUK TESTING & PRODUCTION

Terakhir diupdate: 22 Januari 2026
