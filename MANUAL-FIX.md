# PERBAIKAN MANUAL - LANGKAH PER LANGKAH

## MASALAH

Saat akses aplikasi, masih dapat halaman XAMPP welcome page, bukan Perpustakaan Online.

## PENYEBAB

1. Hosts file belum punya entry untuk perpus.test
2. Apache VirtualHost belum dikonfigurasi

## SOLUSI (3 LANGKAH MANUAL)

### LANGKAH 1: UPDATE HOSTS FILE

**File:** `C:\Windows\System32\drivers\etc\hosts`

**Cara membuka:**

1. Tekan `Windows Key + R`
2. Ketik: `notepad C:\Windows\System32\drivers\etc\hosts`
3. Tekan Enter
4. Click "Yes" jika diminta elevate (jalankan sebagai admin)

**Tambahkan di AKHIR file (sebelum line yang kosong):**

```
127.0.0.1 perpus.test
127.0.0.1 sma1.perpus.test
127.0.0.1 smp5.perpus.test
127.0.0.1 sma3.perpus.test
```

**SIMPAN file (Ctrl+S)**

---

### LANGKAH 2: UPDATE APACHE VHOSTS

**File:** `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

**Cara membuka:**

1. Buka file manager ke: `C:\xampp\apache\conf\extra\`
2. Right-click pada `httpd-vhosts.conf`
3. Select "Open With" → Notepad
4. Jika minta, klik "Continue" (jalankan sebagai admin)

**HAPUS SEMUA ISI FILE**

**GANTI DENGAN INI:**

```apache
# Perpustakaan Online - Virtual Hosts Configuration
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

**SIMPAN file (Ctrl+S)**

---

### LANGKAH 3: RESTART APACHE & FLUSH DNS

**Buka Command Prompt sebagai Administrator:**

1. Tekan `Windows Key + R`
2. Ketik: `cmd`
3. Tekan `Ctrl+Shift+Enter` (jalankan sebagai admin)
4. Klik "Yes"

**Copy-paste perintah ini satu-satu (tekan Enter setelah masing-masing):**

```cmd
net stop Apache2.4
```

(Tunggu sampai selesai)

```cmd
ipconfig /flushdns
```

```cmd
net start Apache2.4
```

(Tunggu sampai selesai)

```cmd
C:\xampp\apache\bin\httpd.exe -t
```

Harusnya output: `Syntax OK`

---

## VERIFIKASI

Setelah semua langkah di atas selesai:

1. **Tutup SEMUA browser**

2. **Clear cache:**

   - Buka browser baru
   - Tekan `Ctrl+Shift+Delete`
   - Pilih "All time"
   - Click "Clear"
   - Tutup browser

3. **Buka browser lagi** dan akses:
   ```
   http://perpus.test/
   ```

**HARUSNYA MUNCUL:**

- ✓ "Perpustakaan Digital" title
- ✓ Halaman landing dengan tombol login/register
- ✓ BUKAN halaman XAMPP

---

## TROUBLESHOOT

### Masih melihat XAMPP page?

**Pastikan:**

1. URL adalah `http://perpus.test/` (BUKAN `http://localhost/`)
2. Browser cache sudah dihapus (Ctrl+Shift+Delete)
3. Browser sudah ditutup dan dibuka lagi
4. Hosts file sudah disimpan
5. VirtualHost config sudah disimpan
6. Apache sudah di-restart

### Syntax Error saat jalankan httpd.exe -t?

1. Buka `httpd-vhosts.conf` lagi
2. Periksa apakah ada typo atau karakter aneh
3. Pastikan semua `<` dan `>` ada
4. Coba copy-paste dari file ini lagi dengan hati-hati

---

## Jika Masih Tidak Bisa

Copy file konfigurasi yang sudah saya siapkan:

**Dari:** `C:\xampp\htdocs\perpustakaan-online\httpd-vhosts-new.conf`
**Ke:** `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

Lalu restart Apache lagi.
