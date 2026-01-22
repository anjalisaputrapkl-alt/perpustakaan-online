# DOKUMENTASI SISTEM NOTIFIKASI

## 1. SETUP DATABASE

```sql
-- Jalankan query di: sql/notifications_table.sql
-- Atau copy-paste SQL berikut:

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM(
        'borrow',
        'return_request',
        'return_confirm',
        'late_warning',
        'info',
        'new_book'
    ) NOT NULL DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_student_school (student_id, school_id),
    KEY idx_read_status (is_read),
    KEY idx_type (type),
    KEY idx_created_at (created_at),
    KEY idx_student_unread (student_id, is_read, created_at DESC),
    KEY idx_student_type (student_id, type, created_at DESC),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 2. STRUKTUR HELPER CLASS

File: `src/NotificationsHelper.php`

Menyediakan fungsi-fungsi:
- `createNotification()` - Buat 1 notifikasi
- `broadcastNotification()` - Broadcast ke multiple users
- `getNotifications()` - Fetch notifikasi with filters
- `getStatistics()` - Get summary statistics
- `markAsRead()` - Mark 1 notifikasi as read
- `markAllAsRead()` - Mark semua as read
- `checkAndCreateLateWarnings()` - Auto-create late warnings
- `deleteOldNotifications()` - Cleanup old data

## 3. TIPE NOTIFIKASI

### A. BORROW (Saat siswa meminjam buku)
**Trigger:** `api/borrow-book.php`
**Title:** "Peminjaman Berhasil"
**Message:** "Anda telah meminjam buku [judul]. Harap dikembalikan sebelum [due_date]."

### B. RETURN_REQUEST (Saat siswa klik "Ajukan Pengembalian")
**Trigger:** `api/student-request-return.php`
**Title:** "Permintaan Pengembalian Dikirim"
**Message:** "Permintaan pengembalian untuk buku [judul] menunggu konfirmasi admin."

### C. RETURN_CONFIRM (Saat admin konfirmasi pengembalian)
**Trigger:** `api/admin-confirm-return.php`
**Title:** "Pengembalian Disetujui"
**Message:** "Admin telah mengonfirmasi pengembalian buku [judul]. Terima kasih!"

### D. LATE_WARNING (Otomatis jika terlambat)
**Trigger:** `api/notif-check-late.php` (manual atau cron)
**Title:** "Peringatan Keterlambatan"
**Message:** "Anda terlambat mengembalikan buku [judul]. Segera ajukan pengembalian."

### E. NEW_BOOK (Saat admin menambah buku baru)
**Trigger:** `api/notif-broadcast-new-book.php`
**Title:** "Buku Baru Tersedia"
**Message:** "Buku [judul] telah ditambahkan di perpustakaan."
**Broadcast:** Ke semua siswa di sekolah

### F. INFO (Custom info)
**Trigger:** Manual via helper
**Title:** Custom
**Message:** Custom

## 4. API ENDPOINTS

### A. Fetch Notifikasi
```
GET /public/api/notif-fetch.php
Parameters:
  - type: filter by type (optional)
  - limit: jumlah data (default 10, max 50)
  - offset: pagination offset (default 0)
  - unread_only: 1 untuk hanya unread (default 0)

Response:
{
    "success": true,
    "data": [...notifikasi array...],
    "stats": {
        "total": 15,
        "unread": 3,
        "borrow": 2,
        "return_request": 1,
        "return_confirm": 0,
        "late_warning": 2,
        "info": 5,
        "new_book": 8
    },
    "pagination": {
        "limit": 10,
        "offset": 0
    }
}
```

### B. Mark Notification As Read
```
POST /public/api/notif-mark-read.php
Parameters:
  - notification_id: ID notifikasi (required, atau)
  - mark_all: 1 (untuk mark semua as read)

Response:
{
    "success": true,
    "message": "Notifikasi telah ditandai sebagai dibaca"
}
```

### C. Check & Create Late Warnings
```
GET/POST /public/api/notif-check-late.php
Parameters:
  - student_id: optional (jika kosong check semua)

Response:
{
    "success": true,
    "count": 5,
    "message": "5 late warning notifikasi berhasil dibuat"
}
```

### D. Broadcast New Book Notification
```
POST /public/api/notif-broadcast-new-book.php
Parameters:
  - book_id: ID buku (required)
  - book_title: Judul buku (optional, auto-fetch jika kosong)

Response:
{
    "success": true,
    "count": 250,
    "message": "250 notifikasi buku baru berhasil dikirim"
}
```

## 5. INTEGRASI DI FRONTEND

### A. Dashboard Header - Display Unread Count
```javascript
// Fetch unread count
fetch('api/notif-fetch.php?unread_only=1&limit=1')
    .then(r => r.json())
    .then(data => {
        // Display data.stats.unread di notification icon
        document.querySelector('.notif-badge').textContent = data.stats.unread;
    });
```

### B. Dashboard Header - Display Notification List
```javascript
// Fetch latest notifications
fetch('api/notif-fetch.php?limit=5')
    .then(r => r.json())
    .then(data => {
        // data.data = array notifikasi
        // data.stats = statistik
        // Render di notif dropdown
    });
```

### C. Mark As Read
```javascript
// Single notification
fetch('api/notif-mark-read.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'notification_id=123'
});

// Mark all
fetch('api/notif-mark-read.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'mark_all=1'
});
```

### D. Filter By Type
```javascript
// Fetch notifikasi keterlambatan saja
fetch('api/notif-fetch.php?type=late_warning&limit=10')
    .then(r => r.json())
    .then(data => {
        // data.data = array late_warning notifications
    });
```

## 6. SETUP CRON JOB (LATE WARNING AUTO-CHECK)

Jalankan setiap hari untuk check keterlambatan dan buat warning:

```bash
# Tambahkan ke crontab
0 0 * * * curl -X GET "https://perpustakaan.local/public/api/notif-check-late.php?school_id=1"
```

Atau setup di admin panel untuk manual trigger:
```php
// Di admin page untuk cek late warnings
<a href="api/notif-check-late.php" target="_blank">Cek Keterlambatan</a>
```

## 7. CONTOH IMPLEMENTASI DI FRONTEND

### Display Notification Counter
```html
<!-- Di header -->
<button onclick="toggleNotifications()" class="btn-notification">
    <iconify-icon icon="mdi:bell"></iconify-icon>
    <span class="notif-badge" id="notif-count">0</span>
</button>

<!-- Notification dropdown -->
<div class="notif-dropdown" id="notif-dropdown" style="display:none;">
    <div class="notif-header">
        <h3>Notifikasi</h3>
        <button onclick="markAllAsRead()">Mark all as read</button>
    </div>
    <div class="notif-list" id="notif-list">
        <!-- Will be populated by JS -->
    </div>
</div>
```

### Load Notifications
```javascript
function loadNotifications() {
    fetch('api/notif-fetch.php?limit=10')
        .then(r => r.json())
        .then(data => {
            // Update badge
            document.getElementById('notif-count').textContent = data.stats.unread;
            
            // Render notifikasi
            const html = data.data.map(n => `
                <div class="notif-item ${n.is_read ? '' : 'unread'}">
                    <strong>${n.title}</strong>
                    <p>${n.message}</p>
                    <small>${formatDate(n.created_at)}</small>
                    <button onclick="markAsRead(${n.id})">Mark as read</button>
                </div>
            `).join('');
            
            document.getElementById('notif-list').innerHTML = html;
        });
}

function markAsRead(notifId) {
    fetch('api/notif-mark-read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_id=' + notifId
    }).then(() => loadNotifications());
}

function markAllAsRead() {
    fetch('api/notif-mark-read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'mark_all=1'
    }).then(() => loadNotifications());
}

// Load on page load
loadNotifications();

// Reload every 30 seconds
setInterval(loadNotifications, 30000);
```

## 8. USAGE SUMMARY

**File yang sudah diupdate:**
- `api/borrow-book.php` ✅ Buat notifikasi saat borrow
- `api/student-request-return.php` ✅ Buat notifikasi saat request return
- `api/admin-confirm-return.php` ✅ Buat notifikasi saat confirm return

**File baru yang dibuat:**
- `src/NotificationsHelper.php` ✅ Helper class
- `api/notif-fetch.php` ✅ Fetch notifikasi
- `api/notif-mark-read.php` ✅ Mark as read
- `api/notif-check-late.php` ✅ Check late warnings
- `api/notif-broadcast-new-book.php` ✅ Broadcast new book
- `sql/notifications_table.sql` ✅ SQL table creation

**Sudah siap diintegrasikan ke UI yang sudah ada di notifications.php**

Query sudah ready, tinggal update frontend untuk panggil API endpoints!
