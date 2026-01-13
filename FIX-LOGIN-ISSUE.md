# 🔧 FIX: Halaman Login Mengarah ke XAMPP Default

## ❌ Masalah

Saat mencoba login, halaman menampilkan welcome page XAMPP bukan aplikasi Perpustakaan Online.

## ✅ Solusi

### Langkah 1: Periksa URL yang Anda Akses

**BENAR:**

```
http://perpus.test/
http://sma1.perpus.test/
http://smp5.perpus.test/
```

**SALAH (akan menunjukkan XAMPP):**

```
http://localhost/
http://127.0.0.1/
http://localhost/perpustakaan-online/
```

**Jika belum ada di hosts file, tambahkan:**

File: `C:\Windows\System32\drivers\etc\hosts`

```
127.0.0.1 perpus.test
127.0.0.1 sma1.perpus.test
127.0.0.1 smp5.perpus.test
127.0.0.1 sma3.perpus.test
```

### Langkah 2: Konfigurasi Apache VirtualHost

File: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

**Hapus atau comment** VirtualHost yang sudah ada, kemudian **tambahkan ini di akhir file:**

```apache
# Main Domain - Landing Page
<VirtualHost *:80>
    ServerName perpus.test
    ServerAlias www.perpus.test
    DocumentRoot "C:/xampp/htdocs/perpustakaan-online"

    <Directory "C:/xampp/htdocs/perpustakaan-online">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# School Subdomains - Dashboards
<VirtualHost *:80>
    ServerName *.perpus.test
    DocumentRoot "C:/xampp/htdocs/perpustakaan-online/public"

    <Directory "C:/xampp/htdocs/perpustakaan-online/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Langkah 3: Restart Apache

**Command Prompt (Run as Administrator):**

```cmd
net stop Apache2.4
net start Apache2.4
```

**Atau gunakan XAMPP Control Panel:**

- Click "Stop" untuk Apache
- Click "Start" untuk Apache

### Langkah 4: Verifikasi Konfigurasi

**Command Prompt:**

```cmd
C:\xampp\apache\bin\httpd.exe -t
```

**Expected output:**

```
Syntax OK
```

### Langkah 5: Akses Aplikasi

**Buka browser dan akses:**

```
http://perpus.test/
```

Seharusnya akan melihat landing page Perpustakaan Online dengan tombol login dan register.

---

## 🔍 Troubleshooting

### Problem: "This site can't be reached"

**Solusi:**

1. Pastikan hosts file sudah benar (gunakan Notepad sebagai Administrator)
2. Flush DNS: `ipconfig /flushdns`
3. Restart browser
4. Coba akses `http://perpus.test/` lagi

### Problem: "Syntax error in Apache config"

**Solusi:**

1. Buka `httpd-vhosts.conf` dengan text editor
2. Cek apakah ada typo di konfigurasi VirtualHost
3. Pastikan path menggunakan forward slash `/` bukan backslash `\`
4. Jalankan `httpd.exe -t` untuk cek syntax

### Problem: "Sekolah tidak ditemukan"

**Solusi:**

1. Ini berarti VirtualHost bekerja, tapi sekolah belum ada di database
2. Jalankan SQL dari FINAL-DEPLOYMENT.md untuk insert data sekolah
3. Pastikan database sudah memiliki schools table dengan slug column

---

## ✨ Setelah Berhasil

1. **Main domain (perpus.test):**

   - Menampilkan landing page
   - Modal login dan register

2. **School subdomain (sma1.perpus.test):**

   - Redirect ke halaman login sekolah
   - Tampil nama sekolah: "SMA 1 Jakarta"

3. **Login:**
   - Email: `admin@sma1.com`
   - Password: `password`

---

## 📋 Checklist Konfigurasi

- [ ] Hosts file sudah diupdate dengan 4 domain
- [ ] Apache VirtualHost sudah dikonfigurasi
- [ ] Apache sudah di-restart
- [ ] `httpd.exe -t` menampilkan "Syntax OK"
- [ ] Bisa akses http://perpus.test/
- [ ] Database sudah punya data schools
- [ ] Bisa login dengan admin@sma1.com / password

---

**Jika masih ada masalah, jalankan:**

```bash
C:\xampp\php\php.exe final-validation.php
```

Script ini akan menunjukkan area mana yang masih bermasalah.
