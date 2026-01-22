# ⚡ QUICK START - Email Verification System

## Apa yang diimplementasikan?

Sistem verifikasi email untuk pendaftaran sekolah dengan fitur:

- 📧 Kirim kode 6 digit ke email
- ⏱️ Timer 15 menit untuk verifikasi
- ✅ Auto-aktivasi akun setelah verifikasi
- 🔐 Security dengan server-side validation

---

## 3 Langkah Implementasi

### 1️⃣ Jalankan Database Migration

```
http://localhost/perpustakaan-online/sql/run-migration.php
```

Ini akan menambahkan 3 kolom ke tabel `users`:

- `verification_code` - Simpan kode 6 digit
- `is_verified` - Status verifikasi (0/1)
- `verified_at` - Timestamp saat diverifikasi

✅ Tunggu sampai muncul "Migration completed successfully!"

---

### 2️⃣ Setup Email (OPTIONAL - Bisa Skip untuk Testing)

**Untuk Development:**

Gunakan Mailtrap (free):

1. Daftar di https://mailtrap.io
2. Ambil SMTP credentials
3. Edit `php.ini` dengan SMTP settings

Atau biarkan default (system mail) - tergantung server config.

---

### 3️⃣ Test Functionality

**Link:** http://localhost/perpustakaan-online/

**Step:**

1. Klik "Daftarkan Sekarang"
2. Isi form:
   - Nama Sekolah: `SMA Test`
   - Nama Admin: `Admin Test`
   - Email: `test@sch.id` (harus @sch.id)
   - Password: `password123`
3. Klik "Daftarkan Sekolah"
4. 📋 Modal verifikasi otomatis muncul
5. 📧 Cek email untuk kode
6. 🔢 Masukkan 6 digit kode
7. ✅ Klik "Verifikasi Email"
8. ✨ Otomatis login & redirect ke dashboard

---

## 📁 File Baru / Diubah

**BARU:**

- `src/EmailHelper.php` - Email functions
- `public/api/verify-email.php` - API verifikasi
- `sql/migrations/add_email_verification.sql` - SQL schema
- `EMAIL_VERIFICATION_DOCS.md` - Full documentation
- `IMPLEMENTATION_GUIDE.md` - Setup guide

**DIMODIFIKASI:**

- `public/api/register.php` - Add verification flow
- `index.php` - Add verification modal & JS
- `assets/css/landing.css` - Add styling
- `sql/run-migration.php` - Add migration checks

---

## 🎯 Alur Singkat

```
Register Form
    ↓
Generate Kode Verifikasi
    ↓
Kirim Email
    ↓
Modal Verifikasi Muncul
    ↓
User Input Kode (6 digit)
    ↓
Validasi Kode
    ↓
Update User: is_verified = 1
    ↓
Auto Login
    ↓
Redirect ke Dashboard
```

---

## 🔑 Key Features

| Feature            | Details                               |
| ------------------ | ------------------------------------- |
| **Kode**           | 6 digit random, valid 15 menit        |
| **Input**          | Auto-focus antar field, numeric only  |
| **Validation**     | Server-side, database check           |
| **Timer**          | Countdown 15 menit dengan warning     |
| **Error Handling** | Clear messages untuk error            |
| **Security**       | Password encrypted, server validation |
| **UX**             | Modal profesional, smooth redirect    |

---

## ✅ Checklist Testing

```
□ Migration sukses
□ Kolom database ada
□ Form register bekerja
□ Email terkirim
□ Modal verifikasi terbuka
□ Input 6 digit berfungsi
□ Verifikasi berhasil
□ Auto login bekerja
□ Redirect ke dashboard
□ Error handling OK
```

---

## 🐛 Troubleshooting Cepat

| Problem              | Solution                                    |
| -------------------- | ------------------------------------------- |
| Email tidak terkirim | Setup SMTP di php.ini atau gunakan Mailtrap |
| Migration error      | Run manual SQL dari phpMyAdmin              |
| Kode input error     | Clear cache & reload                        |
| Timer tidak jalan    | Check browser console                       |
| Login tidak berhasil | Check is_verified=1 di database             |

---

## 📧 Email Template Preview

Email yang diterima user:

```
┌─────────────────────────────────┐
│   ✓ VERIFIKASI EMAIL            │
│   Pendaftaran Perpustakaan      │
│                                 │
│   Halo [Admin Name],            │
│                                 │
│   Kode Verifikasi Anda:        │
│                                 │
│   ┌─────────────────┐          │
│   │  1 2 3 4 5 6    │          │
│   │  (6 digit)      │          │
│   └─────────────────┘          │
│                                 │
│   ⚠️ Kode berlaku 15 menit     │
│                                 │
└─────────────────────────────────┘
```

---

## 🔐 Security Points

✅ Kode random (tidak predictable)  
✅ Expiry 15 menit (time-based)  
✅ Database validation (server-side)  
✅ One-time use (dihapus setelah terpakai)  
✅ Password encrypted (PASSWORD_DEFAULT)

---

## 📞 Need Help?

1. **Setup Issues** → Baca `IMPLEMENTATION_GUIDE.md`
2. **How It Works** → Baca `EMAIL_VERIFICATION_DOCS.md`
3. **Code Details** → Check source files
4. **Support** → support@perpustakaan.edu

---

## 🎉 You're All Set!

Sistem verifikasi email sudah siap digunakan.

Cukup:

1. Jalankan migration
2. Test registrasi
3. Enjoy! 🚀

---

**Last Updated:** 2026-01-22  
**Version:** 1.0.0
