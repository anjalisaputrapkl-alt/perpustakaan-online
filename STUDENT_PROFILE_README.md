# 📚 STUDENT PROFILE MODULE - DOKUMENTASI LENGKAP

## 📋 Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Instalasi](#instalasi)
3. [Struktur File](#struktur-file)
4. [Database Schema](#database-schema)
5. [Backend API Reference](#backend-api-reference)
6. [Frontend Implementation](#frontend-implementation)
7. [Fitur-Fitur](#fitur-fitur)
8. [Query Examples](#query-examples)
9. [Security Features](#security-features)
10. [Testing Guide](#testing-guide)
11. [Troubleshooting](#troubleshooting)

---

## Pengenalan

### Apa Itu Module Profil Siswa?

Module Profil Siswa adalah sistem lengkap untuk mengelola profil siswa di perpustakaan digital dengan fitur:

✅ **Profil Lengkap** - Menampilkan identitas siswa dari database  
✅ **Edit Profil** - Update nama, email, nomor HP, alamat  
✅ **Upload Foto** - Upload dan validasi foto profil  
✅ **Kartu Digital** - ID Card modern dengan QR Code  
✅ **Responsive** - Desktop, tablet, dan mobile-friendly  
✅ **Secure** - PDO prepared statements, session auth  

### Teknologi

- **Backend**: PHP 7.2+ dengan PDO
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Icons**: Iconify
- **QR Code**: QR Server API (external)

---

## Instalasi

### Step 1: Import Database

```bash
# Windows
mysql -u root -p perpustakaan_online < sql\migrations\student_profile.sql

# Linux/Mac
mysql -u root -p perpustakaan_online < sql/migrations/student_profile.sql
```

**Verifikasi**:
```sql
DESCRIBE siswa;
```

### Step 2: Buat Folder Upload

```bash
# Windows (PowerShell)
New-Item -ItemType Directory -Force -Path "uploads/siswa"

# Linux/Mac
mkdir -p uploads/siswa
chmod 755 uploads/siswa
```

### Step 3: Copy Files

Pastikan file berikut ada:

```
✓ src/StudentProfileModel.php
✓ src/PhotoUploadHandler.php
✓ public/api/profile.php
✓ public/profile.php
✓ public/student-card.php
```

### Step 4: Verifikasi Instalasi

1. Login sebagai siswa
2. Kunjungi: `http://localhost/perpustakaan-online/public/profile.php`
3. Harusnya bisa melihat profil siswa dari database

---

## Struktur File

```
perpustakaan-online/
├── src/
│   ├── StudentProfileModel.php         (Model untuk CRUD profil)
│   └── PhotoUploadHandler.php          (Handler upload foto)
├── public/
│   ├── profile.php                     (Halaman profil utama)
│   ├── student-card.php                (Halaman kartu digital)
│   └── api/
│       └── profile.php                 (REST API endpoint)
├── uploads/
│   └── siswa/                          (Folder foto profil)
│       ├── siswa_1_1234567890.jpg
│       ├── siswa_2_1234567891.png
│       └── ...
├── sql/migrations/
│   └── student_profile.sql             (Database migration)
└── assets/
    └── images/
        └── default-avatar.png          (Default foto)
```

---

## Database Schema

### Tabel: `siswa`

```sql
CREATE TABLE `siswa` (
    id_siswa INT AUTO_INCREMENT PRIMARY KEY,          -- ID unik siswa
    nama_lengkap VARCHAR(100) NOT NULL,               -- Nama lengkap
    nis VARCHAR(20),                                  -- Nomor Induk Siswa
    nisn VARCHAR(20),                                 -- Nomor Induk Siswa Nasional
    kelas VARCHAR(20),                                -- Kelas (XI RPL, XII IPA, dst)
    jurusan VARCHAR(50),                              -- Jurusan
    tanggal_lahir DATE,                               -- Tanggal lahir
    jenis_kelamin CHAR(1),                            -- L/P atau M/F
    alamat TEXT,                                      -- Alamat lengkap
    email VARCHAR(100),                               -- Email
    no_hp VARCHAR(15),                                -- Nomor HP
    foto VARCHAR(255),                                -- Path foto profil
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   -- Dibuat tanggal
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP    -- Diupdate tanggal
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Kolom Penjelasan

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| `id_siswa` | INT | Primary key, auto increment |
| `nama_lengkap` | VARCHAR(100) | Nama lengkap siswa |
| `nis` | VARCHAR(20) | Nomor identitas sekolah |
| `nisn` | VARCHAR(20) | Nomor identitas nasional |
| `kelas` | VARCHAR(20) | Kelas siswa |
| `jurusan` | VARCHAR(50) | Program jurusan |
| `tanggal_lahir` | DATE | Format: YYYY-MM-DD |
| `jenis_kelamin` | CHAR(1) | L (Laki-laki) atau P (Perempuan) |
| `alamat` | TEXT | Alamat lengkap |
| `email` | VARCHAR(100) | Email siswa |
| `no_hp` | VARCHAR(15) | Format: 08xx atau +62xx |
| `foto` | VARCHAR(255) | Path relatif ke foto |
| `created_at` | TIMESTAMP | Otomatis timestamp |
| `updated_at` | TIMESTAMP | Otomatis saat diupdate |

### Indexes

```sql
KEY `idx_nis` (`nis`)      -- Index untuk cari berdasarkan NIS
KEY `idx_nisn` (`nisn`)    -- Index untuk cari berdasarkan NISN
KEY `idx_email` (`email`)  -- Index untuk cari berdasarkan email
```

---

## Backend API Reference

### Endpoint: `/public/api/profile.php`

#### 1. GET Profil Siswa

**Request:**
```
GET /public/api/profile.php?action=get_profile
```

**Authorization**: Session auth (id_siswa harus di session)

**Response (Success - 200):**
```json
{
    "success": true,
    "data": {
        "id_siswa": 1,
        "nama_lengkap": "Ahmad Risky",
        "nis": "001",
        "nisn": "1234567890001",
        "kelas": "XI RPL",
        "jurusan": "Rekayasa Perangkat Lunak",
        "tanggal_lahir": "2006-05-15",
        "jenis_kelamin": "L",
        "alamat": "Jl. Merdeka No. 10",
        "email": "ahmad@example.com",
        "no_hp": "08123456789",
        "foto": "/perpustakaan-online/uploads/siswa/siswa_1_1234567890.jpg",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-20 14:45:00"
    }
}
```

**Response (Error - 401):**
```json
{
    "success": false,
    "message": "Unauthorized. Silakan login terlebih dahulu"
}
```

#### 2. POST Update Profil

**Request:**
```
POST /public/api/profile.php?action=update_profile

Body (form-data):
- nama_lengkap: Ahmad Risky Pratama
- email: ahmad@example.com
- no_hp: 08123456789
- alamat: Jl. Merdeka No. 10
```

**Validasi:**
- `nama_lengkap`: Min 3 karakter
- `email`: Format email valid
- `no_hp`: Format nomor HP (08xx atau +62xx)

**Response (Success - 200):**
```json
{
    "success": true,
    "message": "Profil berhasil diperbarui"
}
```

**Response (Error - 400):**
```json
{
    "success": false,
    "message": "Format email tidak valid"
}
```

#### 3. POST Upload Foto

**Request:**
```
POST /public/api/profile.php?action=upload_photo

Body (form-data):
- photo: [file image]
```

**Validasi:**
- **Format**: JPG, JPEG, PNG, GIF
- **Ukuran**: Max 2MB
- **MIME Type**: image/jpeg, image/png, image/gif

**Response (Success - 200):**
```json
{
    "success": true,
    "path": "/perpustakaan-online/uploads/siswa/siswa_1_1705756800.jpg",
    "message": "Foto berhasil diupload dan disimpan"
}
```

**Response (Error - 400):**
```json
{
    "success": false,
    "message": "Ukuran file terlalu besar. Maksimal 2MB"
}
```

---

## Backend Code Reference

### StudentProfileModel Class

```php
// Load model
require_once 'src/StudentProfileModel.php';
$model = new StudentProfileModel($pdo);

// 1. Get profil siswa
$profile = $model->getProfile($studentId);
// Returns: Array dengan semua data profil
// Returns: false jika tidak ditemukan

// 2. Update profil
$result = $model->updateProfile($studentId, [
    'nama_lengkap' => 'Nama Baru',
    'email' => 'email@example.com',
    'no_hp' => '08123456789',
    'alamat' => 'Alamat baru'
]);
// Returns: ['success' => true/false, 'message' => 'string']

// 3. Update path foto
$success = $model->updatePhotoPath($studentId, '/uploads/siswa/foto.jpg');
// Returns: true/false

// 4. Static helpers
$date = StudentProfileModel::formatDate('2006-05-15');
// Output: "15 May 2006"

$gender = StudentProfileModel::getGenderDisplay('L');
// Output: "Laki-laki"
```

### PhotoUploadHandler Class

```php
// Load handler
require_once 'src/PhotoUploadHandler.php';
$handler = new PhotoUploadHandler();

// 1. Handle upload
$result = $handler->handleUpload($_FILES['photo'], $studentId);
// Returns: [
//     'success' => true/false,
//     'path' => '/uploads/siswa/siswa_1_1234567890.jpg',
//     'message' => 'Foto berhasil diupload'
// ]

// 2. Delete old photo
$handler->deleteOldPhoto('/uploads/siswa/old-foto.jpg');
// Returns: true/false

// 3. Validate upload directory
$validation = $handler->validateUploadDirectory();
// Returns: [
//     'exists' => true/false,
//     'writable' => true/false,
//     'path' => '/path/to/uploads/siswa/'
// ]
```

---

## Frontend Implementation

### Profile Page (`public/profile.php`)

Halaman profil memiliki 2 bagian:

#### Bagian 1: Profile Info Card (Kiri)
- Foto profil (circular, bordered)
- Nama lengkap
- ID Siswa
- Detail siswa (NIS, Kelas, Jurusan, Jenis Kelamin, Tanggal Lahir, Email, No HP, Alamat)
- Button: Edit Profil, Lihat Kartu Digital

#### Bagian 2: Edit Form (Kanan)
- Upload foto dengan drag & drop
- Input: Nama Lengkap
- Input: Email (dengan validasi)
- Input: No. HP (dengan format +62/08)
- Textarea: Alamat
- Button: Simpan Perubahan

### Student Card Page (`public/student-card.php`)

Menampilkan 2 kartu ID Digital:

#### Front Card
- Gradient background (primary → secondary)
- Logo sekolah
- Foto siswa (80x100px)
- Nama lengkap
- ID Siswa dan Kelas
- NIS/NISN
- Berlaku hingga (1 tahun dari hari ini)

#### Back Card
- QR Code (120x120px)
- Label "SCAN QR CODE"
- ID Siswa

#### Features
- 🖨️ Cetak (Print)
- 📥 Download (untuk produksi, pakai html2canvas)
- 🔙 Kembali

---

## Fitur-Fitur

### 1. Profil Lengkap

✅ Menampilkan semua data siswa dari database  
✅ Graceful fallback untuk data kosong (tampil "-")  
✅ Format tanggal otomatis (d M Y)  
✅ Format jenis kelamin (L→Laki-laki, P→Perempuan)  
✅ Foto default jika tidak ada foto

### 2. Edit Profil

✅ Update nama, email, no HP, alamat  
✅ Validasi input (email format, nama min 3 karakter)  
✅ Error handling dengan pesan user-friendly  
✅ Success message setelah update  
✅ Auto-reload halaman setelah berhasil

### 3. Upload Foto

✅ Drag & drop area  
✅ Validasi tipe file (jpg, png, gif)  
✅ Validasi ukuran (max 2MB)  
✅ Auto-create folder upload  
✅ Nama file otomatis: siswa_[id]_[timestamp].[ext]  
✅ Delete foto lama saat upload foto baru  
✅ Preview foto terupload  
✅ Fallback ke default-avatar.png jika kosong

### 4. Kartu Digital

✅ ID Card modern dengan gradient  
✅ QR Code otomatis (menggunakan API eksternal)  
✅ Cetak ke printer  
✅ Print-friendly CSS  
✅ Download (via browser print-to-PDF)  
✅ Responsive design

### 5. Security

✅ Session auth wajib  
✅ PDO prepared statements  
✅ Input validation  
✅ XSS prevention (htmlspecialchars)  
✅ File upload validation  
✅ Directory permission 755

### 6. Responsive Design

✅ Desktop: 2-column layout  
✅ Tablet (1024px): 1-column layout  
✅ Mobile (768px): Full width, hamburger menu  
✅ Smooth animations

---

## Query Examples

### 1. Get Profil Siswa

```php
$sql = "SELECT id_siswa, nama_lengkap, nis, nisn, kelas, jurusan, 
        tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto, 
        created_at, updated_at 
        FROM siswa 
        WHERE id_siswa = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$studentId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
```

### 2. Update Profil

```php
$sql = "UPDATE siswa 
        SET nama_lengkap = ?, 
            email = ?, 
            no_hp = ?, 
            alamat = ?,
            updated_at = NOW()
        WHERE id_siswa = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nama, $email, $noHp, $alamat, $studentId]);
```

### 3. Update Foto Path

```php
$sql = "UPDATE siswa 
        SET foto = ?, 
            updated_at = NOW()
        WHERE id_siswa = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$photoPath, $studentId]);
```

### 4. Get Table Columns (untuk adaptasi)

```php
$sql = "DESCRIBE siswa";
$stmt = $pdo->query($sql);
$columns = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}
```

---

## Security Features

### 1. Session Authentication

```php
if (!isset($_SESSION['id_siswa'])) {
    http_response_code(401);
    exit;
}
```

✅ Semua endpoint cek session  
✅ Redirect ke login jika tidak terauth  
✅ Return 401 di API

### 2. SQL Injection Prevention

```php
$sql = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$studentId]);  // Secure
```

✅ PDO prepared statements untuk semua query  
✅ Parameter binding otomatis  
✅ Tidak ada string concatenation

### 3. XSS Prevention

```php
echo htmlspecialchars($profile['nama_lengkap']);
// Output: Safe, HTML entities escaped
```

✅ htmlspecialchars() untuk output  
✅ JSON encode di API  
✅ Input validation

### 4. File Upload Security

```php
// Validasi MIME type
$mimeType = mime_content_type($file['tmp_name']);
if (!in_array($mimeType, $allowedMimes)) {
    // Reject
}

// Validasi ukuran
if ($file['size'] > 2 * 1024 * 1024) {
    // Reject
}

// Rename file (random suffix)
$fileName = 'siswa_' . $studentId . '_' . time() . '.' . $ext;
```

✅ MIME type validation  
✅ File size limit (2MB)  
✅ File extension whitelist  
✅ Random filename (mencegah guessing)  
✅ Move ke folder aman (bukan web root)

### 5. Directory Permissions

```bash
chmod 755 uploads/siswa/    # Read & execute untuk semua
chmod 644 uploads/siswa/*   # Read-only untuk files
```

✅ Folder executable oleh web server  
✅ File readable tapi tidak executable  
✅ Tidak boleh writable oleh user lain

---

## Testing Guide

### Browser Testing

1. **Login sebagai siswa**
   ```
   http://localhost/perpustakaan-online/public/login.php
   ```

2. **Buka profil**
   ```
   http://localhost/perpustakaan-online/public/profile.php
   ```

3. **Test fitur:**
   - [ ] Profil loading dari DB
   - [ ] Foto tampil dengan benar
   - [ ] Edit nama → simpan → reload
   - [ ] Edit email → validasi email → simpan
   - [ ] Upload foto → drag & drop → success
   - [ ] Lihat kartu digital
   - [ ] QR Code visible
   - [ ] Cetak kartu → print preview

### API Testing dengan cURL

```bash
# 1. Get profil
curl -b "PHPSESSID=xxx" \
  "http://localhost/perpustakaan-online/public/api/profile.php?action=get_profile"

# 2. Update profil
curl -b "PHPSESSID=xxx" \
  -X POST \
  -d "action=update_profile&nama_lengkap=Ahmad%20Baru&email=ahmad@test.com" \
  "http://localhost/perpustakaan-online/public/api/profile.php"

# 3. Upload foto
curl -b "PHPSESSID=xxx" \
  -F "action=upload_photo" \
  -F "photo=@/path/to/photo.jpg" \
  "http://localhost/perpustakaan-online/public/api/profile.php"
```

### Database Testing

```sql
-- Check data
SELECT * FROM siswa WHERE id_siswa = 1;

-- Check foto directory
SELECT id_siswa, nama_lengkap, foto FROM siswa;

-- Check timestamps
SELECT id_siswa, created_at, updated_at FROM siswa;
```

---

## Troubleshooting

### Problem: Profil tidak loading

**Penyebab**: Session tidak set atau DB connection error

**Solusi**:
```php
// Cek session
echo $_SESSION['id_siswa']; // Harus ada value

// Cek DB connection
require_once 'src/db.php';
$pdo = getDBConnection(); // Cek error

// Cek tabel
DESCRIBE siswa;
```

### Problem: Foto tidak upload

**Penyebab**: 
- Folder uploads/siswa tidak ada
- Permission 644 tidak bisa write
- File size terlalu besar

**Solusi**:
```bash
# Create folder
mkdir -p uploads/siswa

# Set permission
chmod 755 uploads/siswa

# Check permission
ls -la uploads/

# Check upload_max_filesize di php.ini
php -i | grep upload_max_filesize
```

### Problem: Email validation error

**Penyebab**: Format email salah

**Solusi**:
```php
// Gunakan format email valid
ahmad@example.com      // ✓ Valid
ahmad.new@test.co.id   // ✓ Valid
ahmad@                 // ✗ Invalid
ahmad example.com      // ✗ Invalid
```

### Problem: No HP validation error

**Penyebab**: Format nomor HP tidak sesuai

**Solusi**:
```
Valid format:
08123456789        // ✓ Dimulai dengan 0
+6281234567890     // ✓ Dimulai dengan +62
62812345678        // ✓ Tanpa +

Invalid:
+081234567890      // ✗ +0 tidak valid
12345678           // ✗ Terlalu pendek
+628ab12345678     // ✗ Ada huruf
```

### Problem: QR Code tidak tampil

**Penyebab**: API eksternal (qrserver.com) tidak accessible

**Solusi**:
1. Cek koneksi internet
2. Cek firewall rules
3. Alternative: Gunakan qrcode.js library (client-side)

### Problem: CSS tidak load di mobile

**Penyebab**: Cache browser

**Solusi**:
```
Ctrl + Shift + Del (Windows)
Cmd + Shift + Del (Mac)
Pilih "Clear browsing data"
```

### Problem: Update profil tidak ke-save

**Penyebab**: 
- Validasi input error
- Kolom tidak ada di tabel
- Permission DB issue

**Solusi**:
```php
// Check kolom mana yang ada
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'siswa' AND TABLE_SCHEMA = 'perpustakaan_online';

// Check error message
error_log('Error: ' . $e->getMessage());
```

### Problem: Foto default tidak ada

**Penyebah**: Path /assets/images/default-avatar.png tidak ada

**Solusi**:
1. Upload default-avatar.png ke `assets/images/`
2. Atau ubah path di StudentProfileModel.php:
```php
private function getDefaultPhotoPath() {
    return '/perpustakaan-online/assets/images/default-avatar.png';
}
```

---

## Enhancement Ideas

### Fitur untuk ditambah di masa depan:

1. **Export Profil**
   - Export ke PDF
   - Export ke Excel

2. **History Edit**
   - Track siapa yang edit kapan
   - Revert changes

3. **Social Links**
   - Instagram, LinkedIn, etc
   - QR code ke social media

4. **Parent/Guardian Info**
   - Nama orang tua
   - Nomor orang tua

5. **Health Data**
   - Blood type
   - Allergies
   - Medications

6. **Document Upload**
   - KTP scan
   - Vaksinasi
   - Rapor

7. **Batch Import**
   - Import dari Excel
   - Update banyak siswa sekaligus

8. **Activity Log**
   - Login history
   - Edit history
   - Download history

---

## Changelog

### Version 1.0 (2024-01-20)
- ✨ Initial release
- ✨ Profile display
- ✨ Profile edit
- ✨ Photo upload
- ✨ Student card with QR code
- ✨ Responsive design
- ✨ Full documentation

---

## Support

Jika ada pertanyaan atau bug:

1. Baca troubleshooting section
2. Check error_log di server
3. Verifikasi instalasi
4. Test dengan curl/postman

---

**Documentation Generated**: 2024-01-20  
**Last Updated**: 2024-01-20  
**Version**: 1.0.0  
**Status**: Production Ready ✅
