# SOLUSI AKSES LOCALHOST

## Setup Selesai!

Seluruh sistem perpustakaan-online sudah lengkap dan siap digunakan.

### Cara Mengakses:

1. **Pastikan Apache berjalan:**

   - Buka XAMPP Control Panel
   - Klik tombol "Start" di sebelah "Apache"
   - Tunggu sampai status menjadi "Running" (text warna hijau)

2. **Akses Landing Page:**

   - Buka browser
   - Ketik: `http://localhost/perpustakaan-online/`
   - Atau: `http://localhost/`

3. **Fitur yang Tersedia:**
   - Landing page dengan modal login/register
   - Login dan register di popup modal (tidak perlu ke halaman terpisah)
   - Dashboard setelah login dengan stats, books, members, dll
   - Logout yang membawa ke landing page

### Flow Aplikasi:

```
Landing Page (http://localhost/)
    ↓
[Klik Login] → Modal Login Popup
    ↓
Dashboard (http://localhost/perpustakaan-online/public/index.php)
    ↓
[Klik Logout] → Kembali ke Landing Page
```

### File Penting:

- **Landing Page:** `/perpustakaan-online/index.php`
- **Dashboard:** `/perpustakaan-online/public/index.php`
- **Login Page:** `/perpustakaan-online/public/login.php`
- **Register API:** `/perpustakaan-online/public/api/register.php`
- **Login API:** `/perpustakaan-online/public/api/login.php`
- **Styling:** `/perpustakaan-online/landing.css`
- **Config:** `/perpustakaan-online/src/config.php`

### Jika Apache Tidak Bisa Start:

1. Buka XAMPP Control Panel (graphical)
2. Pilih Apache
3. Klik tombol "Start"
4. Jika masih gagal, cek port 80 tidak digunakan aplikasi lain
5. Stop aplikasi yang menggunakan port 80 (IIS, Skype, dll)

### Catatan Teknis:

- Virtual hosts telah di-disable untuk akses via `localhost` biasa
- All URLs menggunakan path `/perpustakaan-online/`
- Database: MySQL (setup via `sql/schema.sql`)
- PHP Version: 8.2.12 (included in XAMPP)

Selamat menggunakan Perpustakaan Online! 📚
