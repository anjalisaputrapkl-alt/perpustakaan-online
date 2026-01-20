## 🚀 INSTALLATION GUIDE - MODUL PROFIL SISWA

### Ringkasan
Modul Profil Siswa yang **lengkap, aman, dan production-ready** untuk perpustakaan digital dengan fitur profil, edit, upload foto, dan kartu digital dengan QR Code.

---

## 📋 Checklist Instalasi

### STEP 1: Database Setup ✓

```bash
# Windows (Command Prompt)
mysql -u root -p perpustakaan_online < sql\migrations\student_profile.sql

# Linux/Mac
mysql -u root -p perpustakaan_online < sql/migrations/student_profile.sql
```

**Verifikasi:**
```sql
USE perpustakaan_online;
DESCRIBE siswa;
-- Harusnya keluar: id_siswa, nama_lengkap, nis, nisn, kelas, dll
```

### STEP 2: Buat Folder Upload ✓

```bash
# PowerShell (Windows)
New-Item -ItemType Directory -Force -Path "uploads\siswa"

# Command Prompt (Windows)
mkdir uploads\siswa

# Linux/Mac
mkdir -p uploads/siswa
chmod 755 uploads/siswa
chmod 644 uploads/siswa/*
```

**Verifikasi:**
```bash
# Windows
dir uploads\siswa

# Linux/Mac
ls -la uploads/siswa
# Harusnya: drwxr-xr-x
```

### STEP 3: Copy Files ✓

Pastikan file ini ada (copy dari working directory):

```
c:\xampp\htdocs\perpustakaan-online\
├── src/
│   ├── StudentProfileModel.php              ✓
│   └── PhotoUploadHandler.php               ✓
├── public/
│   ├── profile.php                          ✓
│   ├── student-card.php                     ✓
│   └── api/
│       └── profile.php                      ✓
├── assets/images/
│   └── default-avatar.html                  ✓
├── uploads/siswa/                           ✓ (folder)
└── sql/migrations/
    └── student_profile.sql                  ✓
```

### STEP 4: Test Installation ✓

1. **Login sebagai siswa**
   - Buka: `http://localhost/perpustakaan-online/public/login.php`
   - Masukkan username/password siswa

2. **Buka halaman profil**
   - Buka: `http://localhost/perpustakaan-online/public/profile.php`
   - Harusnya muncul:
     - Foto profil (atau default avatar)
     - Nama lengkap
     - Detail siswa (NIS, Kelas, dll)
     - Form edit profil
     - Form upload foto

3. **Test kartu digital**
   - Buka: `http://localhost/perpustakaan-online/public/student-card.php`
   - Harusnya muncul:
     - ID Card front (dengan foto, nama, ID)
     - ID Card back (dengan QR Code)
     - Button cetak dan download

4. **Test upload foto**
   - Di halaman profil, drag & drop foto
   - Validasi harusnya work:
     - Max 2MB
     - Format JPG/PNG/GIF
   - Foto harusnya ter-update otomatis

5. **Test edit profil**
   - Edit nama → Simpan → Reload → Verify
   - Edit email → Simpan → Validate format → Verify
   - Edit no HP → Validasi format → Simpan

---

## 📦 File Structure

```
perpustakaan-online/
│
├── src/                                     [Backend Models]
│   ├── StudentProfileModel.php              (Model: 300+ lines)
│   │   ├── getProfile($studentId)
│   │   ├── updateProfile($studentId, $data)
│   │   ├── updatePhotoPath($studentId, $path)
│   │   └── Private helpers
│   │
│   └── PhotoUploadHandler.php               (Handler: 200+ lines)
│       ├── handleUpload($file, $studentId)
│       ├── deleteOldPhoto($path)
│       ├── createUploadDirectory()
│       └── validateUploadDirectory()
│
├── public/                                  [Frontend Pages]
│   ├── profile.php                          (Profil: 750+ lines)
│   │   ├── Profil info card (kiri)
│   │   ├── Edit form (kanan)
│   │   ├── Upload area dengan drag&drop
│   │   └── Responsive design (desktop/tablet/mobile)
│   │
│   ├── student-card.php                     (ID Card: 350+ lines)
│   │   ├── Front card (foto, nama, ID)
│   │   ├── Back card (QR code)
│   │   ├── Print-friendly CSS
│   │   └── Responsive grid layout
│   │
│   └── api/
│       └── profile.php                      (API: 150+ lines)
│           ├── GET action=get_profile
│           ├── POST action=update_profile
│           └── POST action=upload_photo
│
├── uploads/
│   └── siswa/                               [User Uploads]
│       ├── siswa_1_1234567890.jpg           (auto-generated names)
│       ├── siswa_2_1234567891.png
│       └── ... (user uploaded files)
│
├── assets/images/
│   └── default-avatar.html                  (Placeholder avatar)
│
├── sql/migrations/
│   └── student_profile.sql                  (Database: 60+ lines)
│       └── CREATE TABLE siswa with safe migration
│
├── STUDENT_PROFILE_README.md                (Dokumentasi: 600+ lines)
└── STUDENT_PROFILE_QUICK_START.md           (Quick ref: 200+ lines)
```

---

## 🔧 Konfigurasi

### PHP Configuration Check

```bash
# Cek php.ini
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"

# Output harusnya:
upload_max_filesize => 2M (minimal, lebih baik 5-10M)
post_max_size => 8M (minimal)
memory_limit => 128M (minimal)
```

### Database Configuration

File: `src/config.php` (sudah ada atau buat baru)

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'perpustakaan_online',
        'user' => 'root',
        'pass' => '',  // Sesuaikan password
        'charset' => 'utf8mb4'
    ]
];
?>
```

### Folder Permissions

```bash
# Linux/Mac
chmod 755 uploads/siswa                     # Readable & writable
chmod 644 uploads/siswa/*.jpg               # File read-only

# Windows (via PowerShell as Admin)
icacls "uploads\siswa" /grant Users:F /T
```

---

## 🧪 Testing

### 1. Unit Test - Model

```php
<?php
require_once 'src/StudentProfileModel.php';
require_once 'src/db.php';

$pdo = getDBConnection();
$model = new StudentProfileModel($pdo);

// Test get profile
$profile = $model->getProfile(1);
echo json_encode($profile, JSON_PRETTY_PRINT);
// Harusnya output data lengkap

// Test update
$result = $model->updateProfile(1, [
    'nama_lengkap' => 'Test User',
    'email' => 'test@example.com'
]);
echo json_encode($result);
// Harusnya: {success: true, message: "Profil berhasil diperbarui"}

// Test photo path
$photoUrl = $model->getPhotoPath('/uploads/siswa/test.jpg');
echo $photoUrl;
// Output: /uploads/siswa/test.jpg atau /default-avatar.png
?>
```

### 2. API Test - cURL

```bash
# Test get profile
curl -b "PHPSESSID=abc123" \
  "http://localhost/perpustakaan-online/public/api/profile.php?action=get_profile"

# Response:
# {
#   "success": true,
#   "data": {
#     "id_siswa": 1,
#     "nama_lengkap": "Ahmad Risky",
#     ...
#   }
# }

# Test update profile
curl -b "PHPSESSID=abc123" \
  -X POST \
  -d "action=update_profile&nama_lengkap=Ahmad%20Baru&email=ahmad@test.com" \
  "http://localhost/perpustakaan-online/public/api/profile.php"

# Response:
# {"success": true, "message": "Profil berhasil diperbarui"}

# Test upload photo (requires file)
curl -b "PHPSESSID=abc123" \
  -F "action=upload_photo" \
  -F "photo=@/path/to/photo.jpg" \
  "http://localhost/perpustakaan-online/public/api/profile.php"

# Response:
# {
#   "success": true,
#   "path": "/uploads/siswa/siswa_1_1705756800.jpg",
#   "message": "Foto berhasil diupload dan disimpan"
# }
```

### 3. Browser Test

```javascript
// Test di browser console
// 1. Get profile
fetch('/perpustakaan-online/public/api/profile.php?action=get_profile')
  .then(r => r.json())
  .then(d => console.log(d));

// 2. Update profile
const formData = new FormData();
formData.append('action', 'update_profile');
formData.append('nama_lengkap', 'Test User');
formData.append('email', 'test@example.com');

fetch('/perpustakaan-online/public/api/profile.php', {
  method: 'POST',
  body: formData
}).then(r => r.json()).then(d => console.log(d));

// 3. Upload photo
const fileInput = document.querySelector('input[type="file"]');
const upload = new FormData();
upload.append('action', 'upload_photo');
upload.append('photo', fileInput.files[0]);

fetch('/perpustakaan-online/public/api/profile.php', {
  method: 'POST',
  body: upload
}).then(r => r.json()).then(d => console.log(d));
```

### 4. Database Test

```sql
-- Test 1: Check tabel
DESCRIBE siswa;

-- Test 2: Lihat data
SELECT id_siswa, nama_lengkap, email, foto FROM siswa LIMIT 5;

-- Test 3: Check foto path
SELECT id_siswa, nama_lengkap, 
  CASE 
    WHEN foto IS NULL OR foto = '' THEN 'DEFAULT'
    ELSE 'CUSTOM'
  END as foto_type
FROM siswa;

-- Test 4: Check timestamps
SELECT id_siswa, created_at, updated_at FROM siswa WHERE id_siswa = 1;

-- Test 5: Full record
SELECT * FROM siswa WHERE id_siswa = 1\G
```

---

## 🐛 Troubleshooting

### Problem: "Profil siswa tidak ditemukan"

**Penyebab**: 
- Session id_siswa tidak ada
- Data siswa belum di database

**Solusi**:
```sql
-- Insert test data
INSERT INTO siswa (nama_lengkap, nis, nisn, kelas, email)
VALUES ('Ahmad Test', '001', '1234567890001', 'XI RPL', 'ahmad@test.com');
```

### Problem: "Gagal membuat folder upload"

**Penyebab**: 
- Folder tidak ada
- Permission denied

**Solusi**:
```bash
# Buat folder
mkdir -p uploads/siswa

# Set permission di Linux/Mac
chmod 755 uploads/siswa
chmod 777 uploads/siswa    # Jika masih error

# Di Windows: jalankan Command Prompt as Admin
icacls "uploads\siswa" /grant Everyone:F /T
```

### Problem: "Ukuran file terlalu besar"

**Penyebab**: 
- File > 2MB
- php.ini upload_max_filesize terlalu kecil

**Solusi**:
```php
// Di php.ini
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M

# Restart Apache
apache2ctl graceful  # Linux
# atau lewat XAMPP Control Panel
```

### Problem: "Format email tidak valid"

**Penyebab**: Email tidak sesuai format

**Solusi**:
```
Gunakan format: user@domain.com
Contoh valid:
- ahmad@example.com ✓
- siti.nur@test.co.id ✓
- user.name+tag@example.com ✓

Invalid:
- ahmad @example.com (ada space)
- ahmad@example (tanpa TLD)
- @example.com (tanpa user)
```

### Problem: "Format nomor HP tidak valid"

**Penyebab**: Format tidak match pattern

**Solusi**:
```
Valid format:
08123456789 (Indonesia, dimulai 0)
+6281234567890 (International dengan +62)
62812345678 (Tanpa +)

Invalid:
+081234567890 (Jangan +0)
12345678 (Terlalu pendek)
0812345 (Terlalu pendek)
+1-800-123-4567 (Format US, tidak support)
```

### Problem: "Foto tidak muncul setelah upload"

**Penyebab**: 
- Path foto salah di database
- File tidak tersimpan
- Browser cache

**Solusi**:
```javascript
// Clear cache: Ctrl+Shift+Del (Windows) atau Cmd+Shift+Del (Mac)

// Or di browser console:
location.reload(true);  // Force refresh
```

### Problem: "QR Code tidak tampil"

**Penyebab**: 
- Internet tidak terkoneksi
- qrserver.com blocked/down

**Solusi**:
```php
// Di student-card.php, cek URL QR
// Alternatif: Gunakan library qrcode.js (offline)
// https://davidshimjs.github.io/qrcodejs/
```

### Problem: "Permission denied" saat upload

**Penyebab**: 
- Folder permission 644 atau 555
- File ownership tidak sesuai

**Solusi**:
```bash
# Ubah permission
chmod 755 uploads/siswa

# Ubah owner (jika perlu)
chown www-data:www-data uploads/siswa
chown www-data:www-data uploads/siswa/*

# Verifikasi
ls -la uploads/siswa
# Harusnya: drwxr-xr-x dan -rw-r--r--
```

---

## 📊 Database Query Examples

### Setup Awal

```sql
-- Tambah data test jika tabel kosong
INSERT INTO siswa (
    nama_lengkap, nis, nisn, kelas, jurusan, 
    tanggal_lahir, jenis_kelamin, email, no_hp, alamat
) VALUES (
    'Ahmad Risky Pratama', '001', '1234567890001', 'XI RPL', 
    'Rekayasa Perangkat Lunak', '2006-05-15', 'L', 
    'ahmad@example.com', '08123456789', 'Jl. Merdeka No. 10'
);
```

### Common Queries

```sql
-- 1. Cek data siswa dengan foto
SELECT id_siswa, nama_lengkap, 
  IF(foto IS NULL OR foto = '', 'NO', 'YES') as punya_foto
FROM siswa;

-- 2. Cek siswa yang belum upload foto
SELECT id_siswa, nama_lengkap, email 
FROM siswa WHERE foto IS NULL OR foto = '';

-- 3. Update foto path
UPDATE siswa SET foto = '/uploads/siswa/siswa_1_12345.jpg'
WHERE id_siswa = 1;

-- 4. Reset foto
UPDATE siswa SET foto = NULL WHERE id_siswa = 1;

-- 5. Lihat history edit
SELECT id_siswa, nama_lengkap, created_at, updated_at 
FROM siswa ORDER BY updated_at DESC;
```

---

## ✅ Final Checklist

- [ ] Database imported (`student_profile.sql`)
- [ ] Folder `/uploads/siswa/` dibuat
- [ ] Semua PHP files di tempat yang benar
- [ ] `assets/images/default-avatar.html` ada
- [ ] Login sebagai siswa berfungsi
- [ ] Buka `/public/profile.php` → data loading
- [ ] Buka `/public/student-card.php` → kartu visible
- [ ] Upload foto berfungsi (drag & drop)
- [ ] Edit profil berfungsi
- [ ] Email validation berfungsi
- [ ] No HP validation berfungsi
- [ ] QR Code tampil
- [ ] Print kartu berfungsi
- [ ] Mobile responsive
- [ ] Tidak ada error di console

---

## 📚 Dokumentasi

- **STUDENT_PROFILE_README.md** - Dokumentasi lengkap (600+ lines)
- **STUDENT_PROFILE_QUICK_START.md** - Quick reference (200+ lines)
- **Code comments** - Inline documentation di setiap method

---

## 🎉 Selesai!

Module Profil Siswa sudah **siap pakai**!

**Fitur yang sudah lengkap:**
- ✅ Profil display
- ✅ Edit profil (nama, email, no HP, alamat)
- ✅ Upload foto (dengan validation)
- ✅ Kartu digital (ID Card + QR Code)
- ✅ Responsive design
- ✅ Full security
- ✅ Error handling
- ✅ Database safe migration

**Total code:** ~2000+ lines production-ready! 🚀

---

**Version**: 1.0.0  
**Status**: Production Ready ✅  
**Last Updated**: 2024-01-20
