# Quick Reference - Modul Notifikasi Siswa

## 🚀 Quick Start (5 Menit)

### 1. Import Database
```bash
mysql -u root -p perpustakaan_online < sql/migrations/notifications.sql
```

### 2. Akses Halaman
```
http://localhost/perpustakaan-online/public/notifications.php
```

### 3. Test API
```bash
# Ambil daftar notifikasi
curl "http://localhost/perpustakaan-online/public/api/notifications.php?action=list"

# Hitung notifikasi belum dibaca
curl "http://localhost/perpustakaan-online/public/api/notifications.php?action=unread_count"
```

---

## 📂 File Structure

```
perpustakaan-online/
├── public/
│   ├── notifications.php              ← Halaman utama notifikasi
│   └── api/
│       └── notifications.php          ← API endpoint
├── src/
│   └── NotificationsModel.php         ← Model/Business Logic
├── sql/
│   └── migrations/
│       └── notifications.sql          ← Database schema
└── NOTIFICATIONS_MODULE_README.md     ← Full documentation
```

---

## 🔌 API Endpoints

| Method | Endpoint | Action | Deskripsi |
|--------|----------|--------|-----------|
| GET | `?action=list` | Daftar | Ambil semua notifikasi |
| GET | `?action=list&sort=unread` | Filter | Notifikasi belum dibaca |
| GET | `?action=detail&id=1` | Detail | Ambil 1 notifikasi + mark read |
| POST | `?action=mark_read` | Update | Tandai 1 notifikasi dibaca |
| POST | `?action=mark_all_read` | Update | Tandai semua dibaca |
| POST | `?action=delete` | Delete | Hapus notifikasi |
| GET | `?action=unread_count` | Count | Hitung belum dibaca |
| GET | `?action=stats` | Stats | Statistik semua notifikasi |

---

## 💾 Database

### Struktur Tabel
```sql
CREATE TABLE notifikasi (
    id_notifikasi INT PRIMARY KEY AUTO_INCREMENT,
    id_siswa INT,
    judul VARCHAR(255),
    pesan TEXT,
    jenis_notifikasi ENUM('telat', 'peringatan', 'pengembalian', 'info', 'sukses', 'buku', 'default'),
    tanggal DATETIME,
    status_baca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Insert Notifikasi
```php
$pdo->prepare("
    INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi)
    VALUES (?, ?, ?, ?)
")->execute([
    $studentId,
    'Judul Notifikasi',
    'Pesan detail notifikasi',
    'info'  // telat, peringatan, pengembalian, info, sukses, buku
]);
```

---

## 💻 JavaScript Usage

### Get Notifikasi
```javascript
fetch('api/notifications.php?action=list&sort=latest')
  .then(r => r.json())
  .then(data => console.log(data.data));
```

### Mark as Read
```javascript
fetch('api/notifications.php?action=mark_read', {
  method: 'POST',
  body: new URLSearchParams({id: 1})
}).then(r => r.json()).then(d => location.reload());
```

### Delete
```javascript
fetch('api/notifications.php?action=delete', {
  method: 'POST',
  body: new URLSearchParams({id: 1})
}).then(r => r.json()).then(d => location.reload());
```

### Count Unread
```javascript
fetch('api/notifications.php?action=unread_count')
  .then(r => r.json())
  .then(data => {
    document.getElementById('badge').innerText = data.unread_count;
  });
```

---

## 🎯 Jenis Notifikasi & Icon

| Jenis | Icon | Warna | Kegunaan |
|-------|------|-------|----------|
| telat | 🔴 mdi:alert-circle | Red (#fee2e2) | Keterlambatan pengembalian |
| peringatan | ⚠️ mdi:alert-triangle | Yellow (#fef3c7) | Peringatan/denda |
| pengembalian | 📦 mdi:package-variant-closed | Cyan (#cffafe) | Reminder pengembalian |
| info | ℹ️ mdi:information | Cyan (#e0f2fe) | Informasi sistem |
| sukses | ✓ mdi:check-circle | Green (#d1fae5) | Aksi berhasil |
| buku | 📚 mdi:book | Purple (#ede9fe) | Katalog/buku baru |
| default | 🔔 mdi:bell | Gray | Default fallback |

---

## ⚙️ PHP Model Methods

### NotificationsModel Class

```php
// Ambil notifikasi
$notifications = $model->getNotifications($studentId, 'latest');

// Detail notifikasi
$notification = $model->getNotificationDetail($notifId, $studentId);

// Update status baca
$model->updateNotificationStatus($notifId, $studentId, 1);

// Tandai semua dibaca
$model->markAllAsRead($studentId);

// Hitung belum dibaca
$unread = $model->countUnread($studentId);

// Statistik
$stats = $model->getStatistics($studentId);

// Hapus
$model->deleteNotification($notifId, $studentId);

// Helper: Format tanggal
$formatted = NotificationsModel::formatDate('2024-01-15 14:30:00');

// Helper: Get icon
$icon = NotificationsModel::getIcon('telat');

// Helper: Get label
$label = NotificationsModel::getLabel('telat');

// Helper: Get CSS class
$class = NotificationsModel::getBadgeClass('telat');
```

---

## 🔐 Security Features

✅ Session-based authentication  
✅ PDO prepared statements  
✅ HTML escaping (htmlspecialchars)  
✅ Input validation (numeric ID)  
✅ Row ownership verification  
✅ ENUM type enforcement  

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| 401 Unauthorized | Login dulu, cek session |
| Database error | Cek db.php, import migrations |
| API returns 400 | Parameter/ID format salah |
| Notifikasi tidak update | Clear cache, refresh page |
| CSS tidak load | Cek file path, clear browser cache |

---

## 📋 Checklist Implementasi

- [ ] Import `notifications.sql` ke database
- [ ] Copy file ke folder yang benar
- [ ] Test akses `notifications.php`
- [ ] Test API endpoints
- [ ] Update navbar dengan badge unread count
- [ ] Integrasikan insert notifikasi di workflow buku (telat, return, dll)
- [ ] Setup cron job untuk notifikasi otomatis
- [ ] Test di mobile view
- [ ] Cek security (session, injection)

---

## 📌 Notes

- Sidebar sudah dinamis (auto mark active page)
- Sama design dengan dashboard dan borrowing-history
- Responsive untuk mobile
- Timezone: Gunakan NOW() di MySQL
- Testing data: sudah ada sample di migrations.sql

---

**Status:** ✅ Production Ready  
**Version:** 1.0.0  
**Last Updated:** January 2024
