# ⚙️ TAHAP 2: KONFIGURASI AKHIR DAN QUICK START

## 📋 Checklist Setup Tahap 2

Pastikan semua file sudah ada sebelum testing:

```
perpustakaan-online/
├── src/
│   ├── Tenant.php          ✓ NEW - Tenant detection class
│   ├── auth.php            ✓ UPDATED - Multi-tenant redirects
│   ├── db.php              ✓ Sudah ada
│   └── config.php          ✓ Sudah ada
├── public/
│   ├── tenant-router.php   ✓ NEW - Tenant router & constants
│   ├── login-modal.php     ✓ NEW - School-specific login
│   ├── index.php           ✓ UPDATED - Tenant validation
│   ├── login.php           ✓ Sudah ada
│   ├── register.php        ✓ Sudah ada
│   └── ... (file lainnya)
├── index.php               ✓ UPDATED - Landing page with tenant detection
├── TAHAP2-RINGKASAN.md     ✓ Dokumentasi implementasi
├── TAHAP2-TESTING.md       ✓ Testing guide
└── TAHAP2-CONFIG.md        ← Anda sedang membaca ini

```

---

## 🔧 Persiapan Database

### 1. Pastikan Tabel Schools Ada

```sql
-- Check if schools table exists
SHOW TABLES LIKE 'schools';

-- Jika belum ada, create:
CREATE TABLE schools (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add sample data
INSERT INTO schools (name, slug) VALUES
('SMA 1 Jakarta', 'sma1'),
('SMP 5 Bandung', 'smp5'),
('SMA Negeri 3 Surabaya', 'sma3');
```

### 2. Verifikasi Users Table

```sql
-- Pastikan users table punya kolom school_id
DESCRIBE users;

-- Jika belum ada, add:
ALTER TABLE users ADD COLUMN school_id INT;
ALTER TABLE users ADD CONSTRAINT fk_school_id
    FOREIGN KEY (school_id) REFERENCES schools(id);

-- Add test users untuk setiap sekolah
INSERT INTO users (school_id, name, email, password, role) VALUES
(1, 'Admin SMA 1', 'admin@sma1.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm', 'admin'),
(2, 'Admin SMP 5', 'admin@smp5.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm', 'admin'),
(3, 'Admin SMA 3', 'admin@sma3.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm', 'admin');
```

**Password di atas adalah hash dari: "password"**

Untuk generate password baru:

```php
<?php
echo password_hash('password123', PASSWORD_DEFAULT);
// Gunakan output ini di INSERT VALUES
?>
```

---

## 🌐 Verifikasi Konfigurasi Apache & Hosts

### 1. Hosts File Sudah Di-update? (Tahap 1)

```
C:\Windows\System32\drivers\etc\hosts

Harus berisi:
127.0.0.1 perpus.test
127.0.0.1 sma1.perpus.test
127.0.0.1 smp5.perpus.test
127.0.0.1 sma3.perpus.test
```

**Test di Command Prompt:**

```powershell
ping perpus.test
ping sma1.perpus.test
ping smp5.perpus.test
```

### 2. Apache VirtualHost Di-restart? (Tahap 1)

```powershell
# Restart Apache
net stop Apache2.4
net start Apache2.4

# Atau gunakan XAMPP Control Panel: Stop → Start Apache
```

### 3. Test Syntax Apache

```powershell
C:\xampp\apache\bin\httpd.exe -t
# Output: Syntax OK
```

---

## 🚀 Quick Start Testing

### Test 1: Main Domain

```
1. Buka browser
2. Ketik: http://perpus.test/
3. Tekan Enter

Expected Result:
✓ Landing page ditampilkan
✓ Tombol "Login" dan "Daftar Sekolah" terlihat
✓ Modal form responsive
```

---

### Test 2: Valid Subdomain (SMA 1)

```
1. Buka browser
2. Ketik: http://sma1.perpus.test/
3. Tekan Enter

Expected Result:
✓ Auto redirect ke http://sma1.perpus.test/public/index.php
✓ Login page untuk "SMA 1 Jakarta" ditampilkan
✓ Subdomain badge "sma1.perpus.test" terlihat
```

---

### Test 3: Login di Subdomain

```
1. Di halaman login SMA 1 (dari Test 2)
2. Masukkan:
   - Email: admin@sma1.com
   - Password: password
3. Klik Login

Expected Result:
✓ Redirect ke dashboard
✓ Dashboard menampilkan data SMA 1
✓ Session user['school_id'] = 1
```

---

### Test 4: Invalid Subdomain

```
1. Buka browser
2. Ketik: http://invalid.perpus.test/
3. Tekan Enter

Expected Result:
✗ Error 404: "Sekolah tidak ditemukan"
✓ Link untuk kembali ke perpus.test
```

---

## 🐛 Debug Mode

Untuk debugging, tambahkan query string `?debug=1` ke URL:

```
http://sma1.perpus.test/?debug=1
http://sma1.perpus.test/public/index.php?debug=1
```

Debug info akan menampilkan:

- IS_MAIN_DOMAIN value
- IS_VALID_TENANT value
- SCHOOL_ID value
- SCHOOL_NAME value
- SUBDOMAIN value
- $\_SESSION['tenant'] content
- $\_SESSION['user'] content

---

## 📝 Important Notes

### 1. Session Management

Setiap halaman yang include `tenant-router.php` otomatis:

- Deteksi tenant dari subdomain
- Set constants (IS_MAIN_DOMAIN, SCHOOL_ID, dll)
- Simpan ke $\_SESSION['tenant']

### 2. Query Best Practices

Selalu filter by school_id:

```php
// ✓ CORRECT
$stmt = $pdo->prepare(
    'SELECT * FROM books WHERE school_id = :school_id'
);
$stmt->execute(['school_id' => SCHOOL_ID]);

// ✓ ALSO CORRECT (menggunakan helper function)
$school_id = getCurrentSchoolId();
$stmt = $pdo->prepare(
    'SELECT * FROM books WHERE school_id = ?'
);
$stmt->execute([$school_id]);

// ✗ WRONG - Tanpa school_id filter
$stmt = $pdo->prepare('SELECT * FROM books');
```

### 3. Protected Pages Pattern

Setiap halaman yang hanya untuk sekolah tertentu harus:

```php
<?php
// Load tenant router
require __DIR__ . '/tenant-router.php';

// Enforce valid tenant
requireValidTenant('/');

// Load auth
require __DIR__ . '/../src/auth.php';
requireAuth();

// Rest of your code...
```

---

## 🔗 File Dependencies

```
public/index.php
  └── include public/tenant-router.php
        └── require src/db.php
        └── require src/Tenant.php
              └── require src/db.php
  └── include src/auth.php

public/login-modal.php
  └── include public/tenant-router.php
        └── (same as above)
  └── require src/db.php

index.php (landing page)
  └── include public/tenant-router.php
        └── (same as above)
```

---

## ✅ Verification Checklist

- [ ] Database: schools table created dengan data sample
- [ ] Database: users table punya school_id column
- [ ] Hosts file: semua subdomain ditambahkan
- [ ] Apache: httpd-vhosts.conf ter-configure dengan wildcard
- [ ] Apache: Syntax OK dan sudah di-restart
- [ ] Files: Semua file baru sudah di-create
- [ ] Files: Semua file update sudah di-apply
- [ ] Test: Main domain (perpus.test) berfungsi
- [ ] Test: Valid subdomain (sma1.perpus.test) redirect & login work
- [ ] Test: Invalid subdomain error handling
- [ ] Test: Cross-tenant validation (user tidak bisa akses sekolah lain)

---

## 🆘 Troubleshooting

### Problem: "Sekolah tidak ditemukan" di valid subdomain

**Penyebab:**

1. Data di schools table belum di-insert
2. Slug di database tidak match dengan subdomain
3. Database connection error

**Solusi:**

```sql
-- Verify data ada
SELECT * FROM schools;

-- Verify slug format (harus lowercase, no spaces)
SELECT * FROM schools WHERE slug = 'sma1';

-- Cek DB connection di tenant-router.php berjalan OK
```

### Problem: Login page tidak tampil dengan school name

**Penyebab:**

1. Tenant detection gagal
2. SCHOOL_NAME constant tidak ter-set

**Solusi:**

```php
// Debug di login-modal.php
<?php echo SCHOOL_NAME ?? 'ERROR: SCHOOL_NAME not set'; ?>
<?php echo SCHOOL_ID ?? 'ERROR: SCHOOL_ID not set'; ?>
```

### Problem: User dari SMP 5 bisa akses SMA 1

**Penyebab:**

1. Validasi di public/index.php belum di-update
2. Query login tidak filter school_id

**Solusi:**

- Pastikan public/index.php punya validasi:

```php
if ($user['school_id'] !== SCHOOL_ID) {
    header('Location: /public/logout.php');
    exit;
}
```

- Pastikan login-modal.php punya WHERE school_id:

```php
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE email = :email AND school_id = :school_id LIMIT 1'
);
```

---

## 📚 Dokumentasi Referensi

- `TAHAP1-CONFIG.md` - Konfigurasi Apache & Hosts
- `TAHAP2-RINGKASAN.md` - Penjelasan lengkap implementasi
- `TAHAP2-TESTING.md` - Detailed testing scenarios
- `TAHAP2-CONFIG.md` - File ini (Quick setup guide)

---

## 🎯 Next Steps

Setelah Tahap 2 berfungsi sempurna:

**→ Lanjut ke Tahap 3: Pemisahan Website Utama dan Website Sekolah**

- Reorganisasi folder structure
- Update semua protected pages
- Add tenant indicator di navbar
- Comprehensive multi-school testing
