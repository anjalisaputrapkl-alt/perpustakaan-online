# Modul Notifikasi Siswa - Dokumentasi Lengkap

## 📋 Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Instalasi](#instalasi)
3. [Struktur Database](#struktur-database)
4. [API Reference](#api-reference)
5. [Frontend](#frontend)
6. [Fitur-Fitur](#fitur-fitur)
7. [Penggunaan JavaScript](#penggunaan-javascript)
8. [Troubleshooting](#troubleshooting)

---

## 📌 Pengenalan

Modul Notifikasi Siswa adalah sistem notifikasi terintegrasi untuk platform perpustakaan digital. Sistem ini memungkinkan siswa untuk menerima dan mengelola notifikasi mengenai:

- **Keterlambatan Pengembalian Buku** - Alert ketika buku belum dikembalikan sesuai tenggat
- **Peringatan & Denda** - Notifikasi tentang denda yang dikenakan
- **Pengembalian Buku** - Reminder untuk mengembalikan buku
- **Informasi Sistem** - Update tentang perpustakaan
- **Peminjaman Berhasil** - Konfirmasi peminjaman
- **Katalog Baru** - Notifikasi buku baru tersedia

---

## 🚀 Instalasi

### Langkah 1: Import Database
```bash
# Gunakan MySQL CLI
mysql -u root -p perpustakaan_online < sql/migrations/notifications.sql

# Atau gunakan phpMyAdmin:
# 1. Buka phpMyAdmin
# 2. Pilih database perpustakaan_online
# 3. Tab "Import"
# 4. Upload file sql/migrations/notifications.sql
```

### Langkah 2: Verifikasi File
```
perpustakaan-online/
├── public/
│   ├── notifications.php          ✓ Halaman utama notifikasi
│   └── api/
│       └── notifications.php      ✓ API endpoint
├── src/
│   └── NotificationsModel.php     ✓ Model class
└── sql/
    └── migrations/
        └── notifications.sql      ✓ Database migration
```

### Langkah 3: Test Instalasi
```php
// Buka browser ke:
http://localhost/perpustakaan-online/public/notifications.php

// Atau test API dengan:
curl "http://localhost/perpustakaan-online/public/api/notifications.php?action=list"
```

---

## 🗄️ Struktur Database

### Tabel: `notifikasi`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| `id_notifikasi` | INT | Primary Key, Auto Increment |
| `id_siswa` | INT | Foreign Key ke tabel siswa |
| `judul` | VARCHAR(255) | Judul notifikasi |
| `pesan` | TEXT | Isi pesan notifikasi |
| `jenis_notifikasi` | ENUM | Jenis: telat, peringatan, pengembalian, info, sukses, buku, default |
| `tanggal` | DATETIME | Waktu notifikasi dibuat |
| `status_baca` | TINYINT(1) | 0 = belum dibaca, 1 = sudah dibaca |
| `created_at` | TIMESTAMP | Waktu create otomatis |
| `updated_at` | TIMESTAMP | Waktu update terakhir |

### Index
- `idx_siswa` - Quick lookup by student ID
- `idx_status` - Filter notifikasi belum dibaca
- `idx_jenis` - Filter by notification type
- `idx_tanggal` - Sort by date
- `ft_search` - Full text search pada judul dan pesan

### Contoh Data
```sql
-- Notifikasi keterlambatan
INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi, tanggal, status_baca)
VALUES (1, 'Buku Telat Dikembalikan', 'Buku Clean Code belum dikembalikan...', 'telat', NOW(), 0);

-- Notifikasi info
INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi, tanggal, status_baca)
VALUES (1, 'Perpustakaan Tutup', 'Perpustakaan akan ditutup tanggal 25 Januari...', 'info', NOW(), 0);
```

---

## 📡 API Reference

### Base URL
```
http://localhost/perpustakaan-online/public/api/notifications.php
```

### Autentikasi
Semua endpoint memerlukan user login (`$_SESSION['user']` harus ada)

---

### 1. GET - Daftar Notifikasi
```http
GET /api/notifications.php?action=list&sort=latest
```

**Parameters:**
- `action` (required): `list`
- `sort` (optional): `latest`, `oldest`, `unread` (default: `latest`)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id_notifikasi": 1,
      "id_siswa": 1,
      "judul": "Buku Telat Dikembalikan",
      "pesan": "Buku 'Clean Code' belum dikembalikan...",
      "jenis_notifikasi": "telat",
      "tanggal": "2024-01-10 14:30:00",
      "status_baca": 0
    }
  ],
  "stats": {
    "total": 10,
    "unread": 3,
    "overdue": 2,
    "warning": 1,
    "return": 2,
    "info": 2
  },
  "total": 10
}
```

**JavaScript Example:**
```javascript
fetch('api/notifications.php?action=list&sort=latest')
  .then(response => response.json())
  .then(data => {
    console.log('Notifikasi:', data.data);
    console.log('Statistik:', data.stats);
  });
```

---

### 2. GET - Detail Notifikasi
```http
GET /api/notifications.php?action=detail&id=1
```

**Parameters:**
- `action` (required): `detail`
- `id` (required): ID notifikasi

**Response:**
```json
{
  "success": true,
  "data": {
    "id_notifikasi": 1,
    "id_siswa": 1,
    "judul": "Buku Telat Dikembalikan",
    "pesan": "Buku 'Clean Code' belum dikembalikan...",
    "jenis_notifikasi": "telat",
    "tanggal": "2024-01-10 14:30:00",
    "status_baca": 1
  }
}
```

**Catatan:** Saat mengambil detail, status otomatis diubah menjadi "sudah dibaca" (1)

---

### 3. POST - Tandai Sebagai Dibaca
```http
POST /api/notifications.php?action=mark_read
```

**Body:**
```
id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Notifikasi ditandai sebagai dibaca"
}
```

**JavaScript Example:**
```javascript
const formData = new FormData();
formData.append('id', 1);

fetch('api/notifications.php?action=mark_read', {
  method: 'POST',
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

---

### 4. POST - Tandai Semua Sebagai Dibaca
```http
POST /api/notifications.php?action=mark_all_read
```

**Body:** (kosong, tidak ada parameter)

**Response:**
```json
{
  "success": true,
  "message": "Semua notifikasi ditandai sebagai dibaca"
}
```

---

### 5. POST - Hapus Notifikasi
```http
POST /api/notifications.php?action=delete
```

**Body:**
```
id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Notifikasi dihapus"
}
```

---

### 6. GET - Hitung Belum Dibaca
```http
GET /api/notifications.php?action=unread_count
```

**Response:**
```json
{
  "success": true,
  "unread_count": 3
}
```

**Kegunaan:** Untuk badge di navbar atau header

---

### 7. GET - Statistik
```http
GET /api/notifications.php?action=stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 10,
    "unread": 3,
    "overdue": 2,
    "warning": 1,
    "return": 2,
    "info": 2
  }
}
```

---

## 🎨 Frontend

### Halaman: `public/notifications.php`

#### Fitur-Fitur:
1. ✅ Responsive design (mobile-friendly)
2. ✅ Sidebar navigasi dinamis
3. ✅ Header dengan user info
4. ✅ Statistics cards (6 tipe notifikasi)
5. ✅ Filter/sort notifikasi (Latest, Oldest, Unread)
6. ✅ Notification cards dengan icon berbeda
7. ✅ Action buttons (Mark as Read, Delete)
8. ✅ Empty state
9. ✅ Loading animations

#### Layout
```
┌─────────────────────────────────────────────────┐
│  SIDEBAR          HEADER (AS Library)           │
│                                                 │
│  - Dashboard      Notifikasi | User | Logout   │
│  - Riwayat                                      │
│  - Notifikasi     ┌─────────────────────────┐   │
│  - Favorites      │ Notifikasi Page         │   │
│  - Profile        │ Stats Cards (6)         │   │
│  - Help           │ Filter Bar              │   │
│  - Settings       │ Notification Cards      │   │
│  - Logout         │ (List)                  │   │
│                   └─────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

#### Warna per Jenis Notifikasi:
- **Telat** (Red): #fee2e2
- **Peringatan** (Yellow): #fef3c7
- **Pengembalian** (Cyan): #cffafe
- **Info** (Cyan): #e0f2fe
- **Sukses** (Green): #d1fae5
- **Buku** (Purple): #ede9fe

---

## ✨ Fitur-Fitur

### 1. Notifikasi Real-time
Notifikasi ditampilkan dengan timestamp yang dinamis:
- "Baru saja" (< 1 menit)
- "5 menit lalu"
- "2 jam lalu"
- "3 hari lalu"
- Format lengkap (> 1 minggu)

### 2. Status Baca/Belum Dibaca
- Card dengan background berbeda untuk unread
- Dot indicator di judul untuk unread
- Statistics counter untuk unread count

### 3. Badge Jenis Notifikasi
Setiap notifikasi punya badge dengan label jenis dan warna berbeda

### 4. Search & Filter
- Filter by date: Latest, Oldest
- Filter by status: Unread (khusus)
- Future: Full-text search

### 5. Quick Actions
- ✓ Mark as Read (1-click)
- ✗ Delete (with confirmation)

### 6. Empty State
Jika tidak ada notifikasi, tampilkan icon dan pesan yang menarik

---

## 💻 Penggunaan JavaScript

### Fetch Notifikasi
```javascript
// Get semua notifikasi
async function getNotifications() {
  try {
    const response = await fetch('api/notifications.php?action=list&sort=latest');
    const data = await response.json();
    
    if (data.success) {
      console.log('Notifikasi:', data.data);
      console.log('Total unread:', data.stats.unread);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Mark as Read
```javascript
async function markAsRead(notificationId) {
  const formData = new FormData();
  formData.append('id', notificationId);
  
  try {
    const response = await fetch('api/notifications.php?action=mark_read', {
      method: 'POST',
      body: formData
    });
    const data = await response.json();
    
    if (data.success) {
      // Refresh atau update UI
      location.reload();
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Mark All as Read
```javascript
async function markAllAsRead() {
  try {
    const response = await fetch('api/notifications.php?action=mark_all_read', {
      method: 'POST'
    });
    const data = await response.json();
    
    if (data.success) {
      location.reload();
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Delete Notification
```javascript
async function deleteNotification(notificationId) {
  if (!confirm('Yakin ingin menghapus notifikasi ini?')) return;
  
  const formData = new FormData();
  formData.append('id', notificationId);
  
  try {
    const response = await fetch('api/notifications.php?action=delete', {
      method: 'POST',
      body: formData
    });
    const data = await response.json();
    
    if (data.success) {
      location.reload();
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Update Badge Unread Count
```javascript
async function updateBadge() {
  const response = await fetch('api/notifications.php?action=unread_count');
  const data = await response.json();
  
  if (data.success) {
    const badge = document.getElementById('notif-badge');
    badge.textContent = data.unread_count;
    badge.style.display = data.unread_count > 0 ? 'block' : 'none';
  }
}

// Update setiap 30 detik
setInterval(updateBadge, 30000);
```

---

## 🛠️ Troubleshooting

### Problem: Notifikasi tidak muncul

**Solusi:**
1. Cek apakah user sudah login
   ```php
   if (!isset($_SESSION['user'])) {
       die('Silakan login terlebih dahulu');
   }
   ```

2. Cek apakah tabel notifikasi sudah dibuat
   ```sql
   SHOW TABLES LIKE 'notifikasi';
   ```

3. Cek apakah ada data notifikasi untuk user
   ```sql
   SELECT * FROM notifikasi WHERE id_siswa = [ID_SISWA];
   ```

### Problem: API Error 401 Unauthorized

**Solusi:**
1. Pastikan user sudah login
2. Cek session di browser
3. Clear cookies dan login ulang

### Problem: Database connection error

**Solusi:**
1. Pastikan file `src/db.php` ada dan benar
2. Cek credentials database
3. Test connection dengan:
   ```php
   $pdo = require __DIR__ . '/../src/db.php';
   echo ($pdo) ? 'Connected' : 'Failed';
   ```

### Problem: Notifikasi tidak terupdate saat membaca

**Solusi:**
1. Pastikan JavaScript function `markAsRead()` dipanggil
2. Cek console browser untuk error
3. Pastikan session valid

### Problem: CORS Error di JavaScript

**Solusi:**
1. Gunakan relative path: `/api/notifications.php` (bukan absolute)
2. Pastikan fetch request dari same origin
3. Cek CORS headers jika cross-origin

---

## 📚 Contoh Penggunaan Backend

### Insert Notifikasi dari Script Lain
```php
// Di mana pun di aplikasi (misal saat buku dikembalikan telat)

require_once __DIR__ . '/src/NotificationsModel.php';

$pdo = require __DIR__ . '/src/db.php';
$model = new NotificationsModel($pdo);

// Insert notifikasi via SQL langsung
$pdo->prepare("
    INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi)
    VALUES (?, ?, ?, ?)
")->execute([
    1,  // id_siswa
    'Buku Telat Dikembalikan',  // judul
    'Buku Clean Code belum dikembalikan. Denda: Rp 5.000/hari',  // pesan
    'telat'  // jenis
]);
```

### Cron Job untuk Notifikasi Otomatis
```php
<?php
// cron-notifications.php - jalankan setiap jam

$pdo = require __DIR__ . '/src/db.php';

// Cek buku yang telat
$stmt = $pdo->query("
    SELECT DISTINCT b.member_id
    FROM borrows b
    WHERE b.status = 'borrowed' AND b.due_at < NOW()
");

foreach ($stmt->fetchAll() as $row) {
    // Insert notifikasi untuk member yang telat
    $pdo->prepare("
        INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi)
        VALUES (?, ?, ?, ?)
    ")->execute([
        $row['member_id'],
        'Buku Telat Dikembalikan',
        'Ada buku yang belum dikembalikan melebihi tanggal tenggat.',
        'telat'
    ]);
}

echo "✓ Notifikasi telat berhasil dibuat";
```

---

## 🔐 Keamanan

✅ **Implemented:**
- Session check untuk autentikasi
- PDO prepared statements (SQL injection prevention)
- htmlspecialchars() untuk XSS prevention
- Input validation untuk numeric ID
- ENUM untuk jenis notifikasi (no injection)
- Row ownership check (member hanya bisa akses notifikasi mereka)

---

## 📞 Support

Untuk pertanyaan atau error, cek:
1. Browser console (F12)
2. Server error log
3. Database query log
4. Session/cookie status

---

**Version:** 1.0.0  
**Last Updated:** January 2024  
**Author:** Library Admin
