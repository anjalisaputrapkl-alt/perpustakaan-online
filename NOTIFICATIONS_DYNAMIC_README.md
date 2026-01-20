# 📋 MODUL NOTIFIKASI DINAMIS - DOKUMENTASI

## Gambaran Singkat

Modul Notifikasi Dinamis adalah sistem otomatis yang **mengambil data dari tabel existing** (`peminjaman` dan `buku`) dan **menghasilkan notifikasi real-time** tanpa memerlukan tabel tambahan. Notifikasi di-generate on-the-fly berdasarkan kondisi data yang ada.

## ✨ Fitur

✅ **4 Jenis Notifikasi Otomatis:**
1. **Buku Hampir Jatuh Tempo** - Buku akan dikembalikan dalam 3 hari ke depan
2. **Buku Sudah Jatuh Tempo** - Buku sudah melewati tanggal pengembalian
3. **Buku Berhasil Dikembalikan** - Pencatat pengembalian dalam 3 hari terakhir
4. **Buku Baru Ditambahkan** - Buku baru dalam 7 hari terakhir

✅ **Tanpa Tabel Baru** - Menggunakan data existing dari `peminjaman` dan `buku`

✅ **Real-Time** - Notifikasi di-generate setiap kali halaman diakses

✅ **Responsif** - UI modern yang menyesuaikan dengan desktop, tablet, dan mobile

✅ **Smart Fallback** - Jika kolom timestamp belum ada, tetap berjalan dengan fallback

---

## 📂 Struktur File

```
perpustakaan-online/
├── src/
│   └── NotificationsService.php          ← Service untuk generate notifikasi
│
├── public/
│   ├── notifications.php                 ← Halaman utama (UI)
│   └── api/
│       └── notifications-dynamic.php     ← API endpoint
```

---

## 🔧 Instalasi

**Tidak perlu import database!** Cukup copy file:

```bash
# Copy file ke project
cp src/NotificationsService.php perpustakaan-online/src/
cp public/api/notifications-dynamic.php perpustakaan-online/public/api/
# Update notifications.php sudah otomatis (sudah di-modify)
```

---

## 📡 API Endpoint

**Base URL:** `/perpustakaan-online/public/api/notifications-dynamic.php`

### 1. GET - Daftar Semua Notifikasi

```http
GET /api/notifications-dynamic.php?action=list&sort=latest
```

**Parameter:**
- `action` = `list` (required)
- `sort` = `latest` atau `oldest` (optional, default: `latest`)

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id_notifikasi": "overdue_5",
      "id_siswa": 1,
      "judul": "⚠️ Pemrograman Web sudah jatuh tempo!",
      "pesan": "Buku \"Pemrograman Web\" seharusnya dikembalikan pada 19 Jan 2026...",
      "jenis_notifikasi": "telat",
      "tanggal": "2026-01-19 12:00:00",
      "status_baca": 0
    },
    {
      "id_notifikasi": "upcoming_3",
      "id_siswa": 1,
      "judul": "📚 Database Design akan jatuh tempo",
      "pesan": "Buku \"Database Design\" harus dikembalikan pada 21 Jan 2026.",
      "jenis_notifikasi": "pengembalian",
      "tanggal": "2026-01-21 00:00:00",
      "status_baca": 0
    }
  ],
  "total": 5,
  "unread": 3
}
```

### 2. GET - Statistik Notifikasi

```http
GET /api/notifications-dynamic.php?action=stats
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total": 5,
    "unread": 3,
    "overdue": 1,
    "warning": 0,
    "return": 1,
    "info": 0,
    "success": 1,
    "newbooks": 1
  }
}
```

### 3. GET - Hitung Belum Dibaca

```http
GET /api/notifications-dynamic.php?action=unread_count
```

**Response (200):**
```json
{
  "success": true,
  "unread_count": 3
}
```

---

## 🛠️ Backend - NotificationsService

### Class: `NotificationsService`

**Constructor:**
```php
$service = new NotificationsService($pdo);
```

### Methods

#### 1. `getAllNotifications($studentId)`
Mengambil semua notifikasi dari semua sumber.

```php
$notifs = $service->getAllNotifications(1);
// Returns: array of all notifications
```

#### 2. `getUpcomingNotifications($studentId)`
Notifikasi: Buku hampir jatuh tempo (< 3 hari)

```php
$upcoming = $service->getUpcomingNotifications(1);
```

**Syarat Query:**
- `status = 'dipinjam'`
- `tanggal_kembali < 3 hari dari hari ini`
- `tanggal_kembali > hari ini`

#### 3. `getOverdueNotifications($studentId)`
Notifikasi: Buku sudah jatuh tempo

```php
$overdue = $service->getOverdueNotifications(1);
```

**Syarat Query:**
- `status = 'dipinjam'`
- `tanggal_kembali < hari ini`

#### 4. `getReturnedNotifications($studentId)`
Notifikasi: Buku berhasil dikembalikan (3 hari terakhir)

```php
$returned = $service->getReturnedNotifications(1);
```

**Syarat Query:**
- `status = 'dikembalikan'`
- `tanggal_dikembalikan >= 3 hari lalu`
- `tanggal_dikembalikan IS NOT NULL`

#### 5. `getNewBooksNotifications($studentId)`
Notifikasi: Buku baru (7 hari terakhir)

```php
$newbooks = $service->getNewBooksNotifications(1);
```

**Syarat Query:**
- Ambil dari tabel `buku`
- `created_at` atau `waktu_input` dalam 7 hari terakhir
- **Smart Fallback:** Jika kolom timestamp tidak ada, tampilkan 5 buku terbaru (berdasarkan ID)

#### 6. `getStatistics($studentId)`
Ambil statistik ringkasan semua notifikasi.

```php
$stats = $service->getStatistics(1);
// Returns: [
//   'total' => 5,
//   'unread' => 3,
//   'overdue' => 1,
//   'warning' => 0,
//   'return' => 1,
//   'info' => 0,
//   'success' => 1,
//   'newbooks' => 1
// ]
```

### Static Helper Methods

#### `formatDate($date)`
Format tanggal relatif (misal "2 jam lalu")

```php
echo NotificationsService::formatDate('2026-01-20 10:00:00');
// Output: "2 jam lalu"
```

#### `getIcon($type)`
Ambil icon Iconify untuk jenis notifikasi

```php
echo NotificationsService::getIcon('telat');
// Output: "mdi:alert-circle"
```

**Mapping:**
| Jenis | Icon |
|-------|------|
| `telat` | `mdi:alert-circle` |
| `peringatan` | `mdi:alert-triangle` |
| `pengembalian` | `mdi:package-variant-closed` |
| `info` | `mdi:information` |
| `sukses` | `mdi:check-circle` |
| `buku` | `mdi:book-open-page-variant` |

#### `getLabel($type)`
Ambil label readable untuk jenis notifikasi

```php
echo NotificationsService::getLabel('telat');
// Output: "Keterlambatan"
```

#### `getBadgeClass($type)`
Ambil CSS class untuk badge styling

```php
echo NotificationsService::getBadgeClass('telat');
// Output: "notification-badge-overdue"
```

---

## 🎨 Frontend - Halaman Notifikasi

**URL:** `/perpustakaan-online/public/notifications.php`

### Fitur UI

✅ **Sidebar Dinamis** - Link aktif otomatis berdasarkan halaman

✅ **Header Sticky** - User info dan logout button

✅ **Statistics Cards** - 7 stat cards untuk berbagai jenis notifikasi
- Total Notifikasi
- Belum Dibaca
- Keterlambatan (merah)
- Peringatan (kuning)
- Pengembalian (biru)
- Informasi (cyan)
- Buku Baru (hijau)

✅ **Filter Bar** - Urutkan Terbaru/Terlama

✅ **Notification Cards** - Setiap kartu dengan:
- Icon berbeda per jenis
- Warna border berbeda
- Badge label
- Waktu relatif (Baru saja, 2 jam lalu, dll)
- Responsive untuk mobile

✅ **Empty State** - Pesan menyenangkan jika tidak ada notifikasi

### Kode Integrasi

```php
<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsService.php';

$user = $_SESSION['user'];
$studentId = $user['id'];

$service = new NotificationsService($pdo);
$notifications = $service->getAllNotifications($studentId);
$stats = $service->getStatistics($studentId);
?>
```

---

## 🎨 Styling - Jenis Notifikasi

| Jenis | Warna | CSS Class | Icon |
|-------|-------|-----------|------|
| **Keterlambatan** | Merah (#ef4444) | `notification-badge-overdue` | ⚠️ |
| **Peringatan** | Kuning (#f59e0b) | `notification-badge-warning` | 🔔 |
| **Pengembalian** | Biru (#0b3d61) | `notification-badge-return` | 📦 |
| **Info** | Cyan (#0891b2) | `notification-badge-info` | ℹ️ |
| **Sukses** | Hijau (#10b981) | `notification-badge-success` | ✅ |
| **Buku Baru** | Hijau (#10b981) | `notification-badge-book` | 📚 |

---

## 🔍 Query Detail

### Query: Buku Hampir Jatuh Tempo

```sql
SELECT 
    CONCAT('upcoming_', p.id_peminjaman) as id_notifikasi,
    p.id_siswa,
    CONCAT('📚 ', b.judul, ' akan jatuh tempo') as judul,
    CONCAT('Buku "', b.judul, '" harus dikembalikan pada ', DATE_FORMAT(p.tanggal_kembali, '%d %b %Y'), '.') as pesan,
    'pengembalian' as jenis_notifikasi,
    p.tanggal_kembali as tanggal
FROM peminjaman p
JOIN buku b ON p.id_buku = b.id_buku
WHERE p.id_siswa = ?
    AND p.status = 'dipinjam'
    AND DATE(p.tanggal_kembali) <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    AND DATE(p.tanggal_kembali) > CURDATE()
ORDER BY p.tanggal_kembali ASC;
```

### Query: Buku Sudah Jatuh Tempo

```sql
SELECT 
    CONCAT('overdue_', p.id_peminjaman) as id_notifikasi,
    p.id_siswa,
    CONCAT('⚠️ ', b.judul, ' sudah jatuh tempo!') as judul,
    CONCAT('Buku "', b.judul, '" seharusnya dikembalikan pada ', DATE_FORMAT(p.tanggal_kembali, '%d %b %Y'), '...') as pesan,
    'telat' as jenis_notifikasi,
    p.tanggal_kembali as tanggal
FROM peminjaman p
JOIN buku b ON p.id_buku = b.id_buku
WHERE p.id_siswa = ?
    AND p.status = 'dipinjam'
    AND DATE(p.tanggal_kembali) < CURDATE()
ORDER BY p.tanggal_kembali ASC;
```

### Query: Buku Berhasil Dikembalikan

```sql
SELECT 
    CONCAT('returned_', p.id_peminjaman) as id_notifikasi,
    p.id_siswa,
    CONCAT('✅ ', b.judul, ' telah dikembalikan') as judul,
    CONCAT('Terima kasih telah mengembalikan "', b.judul, '"...') as pesan,
    'sukses' as jenis_notifikasi,
    p.tanggal_dikembalikan as tanggal
FROM peminjaman p
JOIN buku b ON p.id_buku = b.id_buku
WHERE p.id_siswa = ?
    AND p.status = 'dikembalikan'
    AND p.tanggal_dikembalikan IS NOT NULL
    AND DATE(p.tanggal_dikembalikan) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
ORDER BY p.tanggal_dikembalikan DESC;
```

### Query: Buku Baru Ditambahkan

```sql
SELECT 
    CONCAT('newbook_', b.id_buku) as id_notifikasi,
    {studentId} as id_siswa,
    CONCAT('🆕 ', b.judul, ' - Buku Baru!') as judul,
    CONCAT('Buku "', b.judul, '" telah ditambahkan ke perpustakaan...') as pesan,
    'buku' as jenis_notifikasi,
    b.created_at as tanggal  -- atau waktu_input
FROM buku b
WHERE DATE(b.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY b.created_at DESC
LIMIT 10;
```

---

## ⚙️ Konfigurasi Database

### Kolom Wajib - Tabel `peminjaman`

| Kolom | Tipe | Ket |
|-------|------|-----|
| `id_peminjaman` | INT | PK |
| `id_siswa` | INT | FK |
| `id_buku` | INT | FK |
| `status` | ENUM('dipinjam','dikembalikan') | Required |
| `tanggal_kembali` | DATETIME | Required |
| `tanggal_dikembalikan` | DATETIME | Nullable |

### Kolom Wajib - Tabel `buku`

| Kolom | Tipe | Ket |
|-------|------|-----|
| `id_buku` | INT | PK |
| `judul` | VARCHAR | Required |
| `penulis` | VARCHAR | Nullable |
| `kategori` | VARCHAR | Nullable |
| `created_at` / `waktu_input` | DATETIME | Optional (fallback jika tidak ada) |

---

## 🛡️ Security

✅ **Session Check** - Semua endpoint wajib login

✅ **PDO Prepared Statements** - Semua query pakai binding parameters

✅ **XSS Prevention** - Output di-escape dengan `htmlspecialchars()`

✅ **Error Handling** - Try-catch di semua method

✅ **Input Validation** - ID student dari $_SESSION (trusted)

---

## 🧪 Testing

### Test di Browser

```
http://localhost/perpustakaan-online/public/notifications.php
```

### Test API dengan cURL

```bash
# Ambil semua notifikasi
curl "http://localhost/perpustakaan-online/public/api/notifications-dynamic.php?action=list"

# Ambil statistik
curl "http://localhost/perpustakaan-online/public/api/notifications-dynamic.php?action=stats"

# Ambil unread count
curl "http://localhost/perpustakaan-online/public/api/notifications-dynamic.php?action=unread_count"
```

---

## 📊 Contoh Response JSON

```json
{
  "success": true,
  "data": [
    {
      "id_notifikasi": "overdue_5",
      "id_siswa": 1,
      "judul": "⚠️ Pemrograman Web sudah jatuh tempo!",
      "pesan": "Buku \"Pemrograman Web\" seharusnya dikembalikan pada 19 Jan 2026. Silakan kembalikan segera untuk menghindari denda.",
      "jenis_notifikasi": "telat",
      "tanggal": "2026-01-19 12:00:00",
      "status_baca": 0
    },
    {
      "id_notifikasi": "upcoming_3",
      "id_siswa": 1,
      "judul": "📚 Database Design akan jatuh tempo",
      "pesan": "Buku \"Database Design\" harus dikembalikan pada 21 Jan 2026.",
      "jenis_notifikasi": "pengembalian",
      "tanggal": "2026-01-21 00:00:00",
      "status_baca": 0
    },
    {
      "id_notifikasi": "newbook_42",
      "id_siswa": 1,
      "judul": "🆕 Clean Code - Buku Baru!",
      "pesan": "Buku \"Clean Code\" karya Robert Martin telah ditambahkan ke perpustakaan. Kategori: Programming.",
      "jenis_notifikasi": "buku",
      "tanggal": "2026-01-20 10:30:00",
      "status_baca": 0
    }
  ],
  "total": 8,
  "unread": 5
}
```

---

## 🚀 Pengembangan Lebih Lanjut

**Ide untuk Enhancement:**

1. **Email Notifications** - Kirim email saat ada notif penting
2. **Real-Time Updates** - WebSocket untuk notif instant
3. **Notification Preferences** - Siswa bisa setting tipe notif mana saja yang di-sukai
4. **Archive/Dismiss** - Simpan notifikasi lama
5. **Admin Panel** - Dashboard untuk lihat notifikasi semua siswa
6. **Cron Job** - Push notifikasi scheduled (misal: pengingat sehari sebelum due date)

---

## 📝 Catatan

- ✅ **Tanpa Tabel Baru** - Menggunakan data existing `peminjaman` dan `buku`
- ✅ **Real-Time** - Notifikasi di-generate setiap kali diakses
- ✅ **Safe Fallback** - Jika kolom timestamp tidak ada, tetap jalan
- ✅ **Plug-and-Play** - Cukup copy 2 file, tidak perlu config
- ✅ **Secure** - Session auth + prepared statements
- ✅ **Responsive** - Mobile-friendly UI
- ✅ **Clean Code** - Well-documented dan easy to maintain

---

## 📞 Support

Jika ada error, check:

1. Session user sudah login ✓
2. Database connection aktif ✓
3. Tabel `peminjaman` dan `buku` ada ✓
4. File NotificationsService.php di `src/` ✓
5. File notifications.php di `public/` ✓
