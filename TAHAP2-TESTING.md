# TAHAP 2: PENYESUAIAN SISTEM TENANT - TESTING GUIDE

## 📋 Ringkasan File yang Dibuat

| File                         | Fungsi                                          |
| ---------------------------- | ----------------------------------------------- |
| `src/Tenant.php`             | Class untuk deteksi tenant dari subdomain       |
| `public/tenant-router.php`   | Router yang load Tenant class dan set constants |
| `public/login-modal.php`     | Login page untuk subdomain sekolah              |
| `src/auth.php` (updated)     | Updated untuk support multi-tenant redirect     |
| `index.php` (updated)        | Updated dengan tenant detection                 |
| `public/index.php` (updated) | Updated dengan tenant validation                |

---

## 🔧 Testing: Persiapan Database

Sebelum testing, pastikan tabel `schools` sudah ada. Jalankan query berikut di phpMyAdmin atau MySQL CLI:

```sql
-- Tambahkan data sekolah untuk testing
INSERT INTO schools (id, name, slug) VALUES
(1, 'SMA 1 Jakarta', 'sma1'),
(2, 'SMP 5 Bandung', 'smp5');

-- Pastikan sudah ada users dengan school_id tersebut
INSERT INTO users (school_id, name, email, password, role) VALUES
(1, 'Admin SMA 1', 'admin@sma1.com', '$2y$10$...password_hash_sma1...', 'admin'),
(2, 'Admin SMP 5', 'admin@smp5.com', '$2y$10$...password_hash_smp5...', 'admin');
```

**Untuk membuat password hash**, gunakan PHP:

```php
$password = password_hash('password123', PASSWORD_DEFAULT);
echo $password; // Copy hasil ini ke database
```

---

## ✅ Testing Skenario 1: Akses Main Domain

### URL: `http://perpus.test/`

**Yang terjadi:**

1. `index.php` include `public/tenant-router.php`
2. Tenant detection: `IS_MAIN_DOMAIN = true`, `SCHOOL_ID = null`
3. Landing page ditampilkan (tidak ada kondisi redirect)

**Expected Output:**

```
✓ Landing page dengan tombol "Login" dan "Daftar Sekolah"
✓ Modal login/register berfungsi
✓ Tidak ada indikasi sekolah (SCHOOL_ID = null)
```

**Debug Info** (add ke index.php sementara):

```php
<!-- Debug: Hapus setelah testing -->
<div style="position:fixed;bottom:10px;right:10px;background:#f0f;color:#fff;padding:10px;font-size:10px;">
  IS_MAIN_DOMAIN: <?php echo IS_MAIN_DOMAIN ? 'true' : 'false'; ?><br>
  SCHOOL_ID: <?php echo SCHOOL_ID ?? 'null'; ?><br>
  HOST: <?php echo CURRENT_HOST; ?>
</div>
```

---

## ✅ Testing Skenario 2: Akses Subdomain Sekolah (Valid)

### URL: `http://sma1.perpus.test/`

**Yang terjadi:**

1. `index.php` include `public/tenant-router.php`
2. Tenant detection:
   - Parse host: `sma1.perpus.test` → subdomain = `sma1`
   - Query ke database: cari schools dengan slug = 'sma1'
   - Ditemukan: SMA 1 Jakarta (id=1)
   - `IS_MAIN_DOMAIN = false`, `IS_VALID_TENANT = true`, `SCHOOL_ID = 1`
3. Redirect ke `/public/index.php` (dashboard sekolah)

**Expected Output:**

```
✓ Auto redirect ke http://sma1.perpus.test/public/index.php
✓ Tampil login page untuk SMA 1 Jakarta
✓ Subdomain "sma1" ditampilkan di header login page
```

**Debug:**

```php
// Cek log dalam login-modal.php
IS_VALID_TENANT: <?php echo IS_VALID_TENANT ? 'true' : 'false'; ?>
SCHOOL_NAME: <?php echo SCHOOL_NAME; ?>
SCHOOL_ID: <?php echo SCHOOL_ID; ?>
SUBDOMAIN: <?php echo SUBDOMAIN; ?>
```

---

## ✅ Testing Skenario 3: Akses Subdomain Sekolah (Invalid)

### URL: `http://invalid-school.perpus.test/`

**Yang terjadi:**

1. Parse host: `invalid-school.perpus.test` → subdomain = `invalid-school`
2. Query database: tidak ditemukan slug 'invalid-school'
3. `IS_VALID_TENANT = false`
4. Tampil error message

**Expected Output:**

```
✓ Error: "Sekolah tidak ditemukan"
✓ Link untuk kembali ke perpus.test
✓ HTTP Status 404
```

---

## ✅ Testing Skenario 4: Login dari Subdomain Sekolah

### URL: `http://sma1.perpus.test/public/login-modal.php`

**Langkah:**

1. Buka URL di atas
2. Lihat "SMA 1 Jakarta" dan "sma1.perpus.test" di header

**Form 1: Correct Credentials**

```
Email: admin@sma1.com
Password: password123
→ Click Login
```

**Expected:**

```
✓ Redirect ke http://sma1.perpus.test/public/index.php
✓ Dashboard SMA 1 ditampilkan
✓ $_SESSION['user']['school_id'] = 1
```

**Form 2: Correct Email tapi dari Sekolah Lain**

```
Email: admin@smp5.com (dari SMP 5)
Password: password123
→ Click Login
```

**Expected:**

```
✗ Error: "Email atau password salah untuk sekolah ini"
✓ Query only search dalam school_id=1 (SMA 1)
✗ Tidak bisa login dengan akun dari sekolah lain
```

**Form 3: Wrong Password**

```
Email: admin@sma1.com
Password: wrongpassword
→ Click Login
```

**Expected:**

```
✗ Error: "Email atau password salah untuk sekolah ini"
```

---

## ✅ Testing Skenario 5: Akses Dashboard dari URL Langsung (Subdomain)

### URL: `http://sma1.perpus.test/public/index.php`

**Tanpa Login:**

1. Buka URL
2. Tidak ada session user

**Expected:**

```
✓ Redirect ke /public/login-modal.php
✓ Tampil login page untuk SMA 1
```

**Dengan Login dari SMA 1:**

1. Login terlebih dahulu via login-modal.php
2. Buka `/public/index.php`

**Expected:**

```
✓ Dashboard SMA 1 ditampilkan
✓ Buku, member, peminjaman hanya dari sekolah 1
✓ Validasi: user['school_id'] === SCHOOL_ID (success)
```

**Dengan Login dari SMP 5 tapi akses SMA 1:**

1. Clear cookies/logout
2. Buka `http://smp5.perpus.test/public/login-modal.php`
3. Login dengan admin@smp5.com
4. Buka `http://sma1.perpus.test/public/index.php`

**Expected:**

```
✗ Redirect ke /public/logout.php
✗ Session destroyed
✓ Validasi: user['school_id'] (2) !== SCHOOL_ID (1) → logout
```

---

## 📊 Testing Checklist

- [ ] Main domain (perpus.test) tampil landing page
- [ ] Subdomain valid (sma1.perpus.test) redirect ke dashboard
- [ ] Subdomain invalid (invalid.perpus.test) tampil error 404
- [ ] Login dengan akun dari sekolah lain ditolak
- [ ] Data terpisah per sekolah (verify via database queries)
- [ ] Session mengandung tenant info
- [ ] Cross-tenant access validation berfungsi
- [ ] Logout dari subdomain redirect ke root

---

## 🐛 Debug Helpers

### Tambahkan ke setiap halaman untuk debugging:

```php
<!-- Debug Tenant Info -->
<?php if ($_GET['debug'] === '1'): ?>
<div style="background:#ff0;padding:10px;font-family:monospace;font-size:10px;margin-bottom:20px;">
    <strong>TENANT DEBUG INFO:</strong><br>
    IS_MAIN_DOMAIN: <?php echo IS_MAIN_DOMAIN ? 'TRUE' : 'FALSE'; ?><br>
    IS_VALID_TENANT: <?php echo IS_VALID_TENANT ? 'TRUE' : 'FALSE'; ?><br>
    SCHOOL_ID: <?php echo SCHOOL_ID ?? 'null'; ?><br>
    SCHOOL_NAME: <?php echo SCHOOL_NAME ?? 'null'; ?><br>
    SUBDOMAIN: <?php echo SUBDOMAIN ?? 'null'; ?><br>
    CURRENT_HOST: <?php echo CURRENT_HOST; ?><br>
    <br>
    <strong>SESSION['TENANT']:</strong><br>
    <pre><?php var_dump($_SESSION['tenant'] ?? []); ?></pre>
    <strong>SESSION['USER']:</strong><br>
    <pre><?php var_dump($_SESSION['user'] ?? []); ?></pre>
</div>
<?php endif; ?>
```

Akses dengan query string: `http://sma1.perpus.test/?debug=1`

---

## 🚀 Tahap 3 Selanjutnya

Setelah testing Tahap 2 berhasil, lanjut ke **Tahap 3: Pemisahan Website Utama dan Website Sekolah**

Yang akan dilakukan:

- Buat public/schools/ untuk halaman khusus sekolah
- Update semua protected pages untuk require valid tenant
- Add breadcrumb/indicator sekolah di dashboard
- Test data isolation antar sekolah
