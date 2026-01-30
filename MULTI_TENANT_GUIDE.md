# 📚 Panduan Multi-Tenant System - Perpustakaan Online

## 🎯 Ringkasan

Sistem Perpustakaan Online sudah dilengkapi dengan **Multi-Tenant Architecture** yang memungkinkan **setiap sekolah memiliki data yang sepenuhnya terpisah dan terisolasi**. Ketika pengguna dari sekolah berbeda melakukan peminjaman, data mereka secara otomatis dipisahkan ke sekolah masing-masing.

---

## 🏗️ Arsitektur Multi-Tenant

### Prinsip Dasar

- **Satu Database, Banyak Sekolah**: Semua sekolah menggunakan database yang sama, tetapi data dipisahkan menggunakan kolom `school_id`
- **Data Isolation**: Setiap query yang mengakses data pengguna wajib memfilter berdasarkan `school_id`
- **Session-Based Security**: `school_id` diambil dari session user, bukan dari parameter URL

### Alur Kerja

```
User Login → Session dibuat dengan school_id →
Query di filter dengan school_id →
Data hanya dari sekolah itu yang ditampilkan
```

---

## 📊 Struktur Database

### Tabel Utama dengan school_id

#### 1. **schools** - Daftar Sekolah

```sql
CREATE TABLE `schools` (
  `id` int(11) PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `status` enum('trial', 'active', 'suspended'),
  `created_at` timestamp
);
```

#### 2. **books** - Koleksi Buku Per Sekolah

```sql
CREATE TABLE `books` (
  `id` int(11) PRIMARY KEY,
  `school_id` int(11) NOT NULL,  -- ← Terikat ke sekolah
  `title` varchar(255),
  `author` varchar(255),
  `isbn` varchar(100),
  `copies` int(11),
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`)
);
```

#### 3. **members** - Member/Siswa Per Sekolah

```sql
CREATE TABLE `members` (
  `id` int(11) PRIMARY KEY,
  `school_id` int(11) NOT NULL,  -- ← Terikat ke sekolah
  `name` varchar(255),
  `nisn` varchar(20),
  `email` varchar(255),
  `status` enum('active', 'inactive'),
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`)
);
```

#### 4. **borrows** - Catatan Peminjaman Per Sekolah

```sql
CREATE TABLE `borrows` (
  `id` int(11) PRIMARY KEY,
  `school_id` int(11) NOT NULL,  -- ← Terikat ke sekolah
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `borrowed_at` datetime,
  `due_at` datetime,
  `returned_at` datetime,
  `status` enum('borrowed', 'returned', 'overdue', 'pending_return'),
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`),
  KEY `idx_school_status` (`school_id`, `status`)
);
```

### Indeks Optimasi

Semua tabel memiliki indeks untuk filtering berdasarkan `school_id`:

```sql
ALTER TABLE `borrows`
  ADD KEY `idx_borrows_school` (`school_id`);

ALTER TABLE `members`
  ADD KEY `idx_members_school_status` (`school_id`, `status`);
```

---

## 🔐 Implementasi di Backend

### 1. Mendapatkan school_id dari Session

**File: `src/auth.php`**

```php
<?php
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /perpustakaan-online/?login_required=1');
        exit;
    }
}

// Di dalam setiap halaman yang butuh autentikasi:
require __DIR__ . '/../src/auth.php';
requireAuth();

$user = $_SESSION['user'];
$sid = $user['school_id'];  // ← school_id dari session
?>
```

### 2. Filter Query Berdasarkan school_id

**Contoh di `public/borrows.php`:**

```php
<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];  // ← Ambil dari session

// ✅ Query dengan filter school_id
$stmt = $pdo->prepare(
    'SELECT b.*, bk.title, m.name AS member_name
     FROM borrows b
     JOIN books bk ON b.book_id = bk.id
     JOIN members m ON b.member_id = m.id
     WHERE b.school_id = :sid  -- ← Filter wajib ada
     ORDER BY b.borrowed_at DESC'
);
$stmt->execute(['sid' => $sid]);
$borrows = $stmt->fetchAll();

// ✅ Update overdue juga dengan filter school_id
$pdo->prepare(
    'UPDATE borrows
     SET status="overdue"
     WHERE school_id=:sid AND returned_at IS NULL AND due_at < NOW()'
)->execute(['sid' => $sid]);
?>
```

### 3. API Endpoints dengan Validasi school_id

**Contoh di `public/api/approve-borrow.php`:**

```php
<?php
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];  // ← school_id dari session

// ✅ Wajib validasi: borrow harus milik school_id ini
$stmt = $pdo->prepare(
    'UPDATE borrows
     SET status="approved", due_at=:due_at
     WHERE id=:id AND school_id=:sid AND status="pending_confirmation"'
);
$stmt->execute([
    'id' => $borrow_id,
    'sid' => $sid,  -- ← Filter school_id di WHERE clause
    'due_at' => $due_date
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Borrow not found']);
    exit;
}
?>
```

**Contoh di `public/api/borrow-book.php`:**

```php
<?php
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$student = $_SESSION['user'];
$school_id = $student['school_id'];

// ✅ Validasi: buku harus dari sekolah yang sama
$bookStmt = $pdo->prepare(
    'SELECT id, title, copies FROM books
     WHERE id = :book_id AND school_id = :school_id'  -- ← Wajib check school_id
);
$bookStmt->execute([
    'book_id' => $book_id,
    'school_id' => $school_id
]);
$book = $bookStmt->fetch();

if (!$book) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
    exit;
}

// ✅ Validasi: member harus dari sekolah yang sama
$memberStmt = $pdo->prepare(
    'SELECT id FROM members
     WHERE nisn = :nisn AND school_id = :school_id'  -- ← Wajib check school_id
);
$memberStmt->execute([
    'nisn' => $nisn,
    'school_id' => $school_id
]);
$member = $memberStmt->fetch();

if (!$member) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Member tidak ditemukan']);
    exit;
}
?>
```

---

## 🔄 Alur Peminjaman Multi-Tenant

### Skenario: User dari Sekolah A dan Sekolah B

#### Sekolah A (school_id = 4)

- **Books**: Memiliki 7 buku dengan `school_id = 4`
- **Members**: Memiliki 2 siswa dengan `school_id = 4`
- **Borrows**: Semua peminjaman dengan `school_id = 4`

#### Sekolah B (school_id = 5)

- **Books**: Memiliki 10 buku dengan `school_id = 5`
- **Members**: Memiliki 5 siswa dengan `school_id = 5`
- **Borrows**: Semua peminjaman dengan `school_id = 5`

### Ketika Admin Sekolah A Login

```
Session['school_id'] = 4

Halaman borrows.php:
├─ Query: SELECT FROM borrows WHERE school_id = 4
├─ Hasil: Hanya peminjaman sekolah A
└─ UI: Hanya data sekolah A yang ditampilkan

Ketika klik tombol "Setujui Peminjaman":
├─ API: approve-borrow.php
├─ Validasi: UPDATE WHERE id=:id AND school_id=4
├─ Keamanan: Tidak bisa approval peminjaman sekolah B
└─ Status: Hanya peminjaman sekolah A yang ter-update
```

### Ketika Admin Sekolah B Login

```
Session['school_id'] = 5

Halaman borrows.php:
├─ Query: SELECT FROM borrows WHERE school_id = 5
├─ Hasil: Hanya peminjaman sekolah B
└─ UI: Hanya data sekolah B yang ditampilkan

Ketika klik tombol "Setujui Peminjaman":
├─ API: approve-borrow.php
├─ Validasi: UPDATE WHERE id=:id AND school_id=5
├─ Keamanan: Tidak bisa approval peminjaman sekolah A
└─ Status: Hanya peminjaman sekolah B yang ter-update
```

---

## ✅ Checklist - Pemisahan Otomatis

### Data Peminjaman (Borrows)

- ✅ **Insert**: `public/api/borrow-book.php` - Wajib include `school_id`
- ✅ **Insert**: `public/api/submit-borrow.php` - Wajib include `school_id`
- ✅ **Update**: `public/api/approve-borrow.php` - Filter `school_id` di WHERE
- ✅ **Update**: `public/api/reject-borrow.php` - Filter `school_id` di WHERE
- ✅ **Update**: `public/api/admin-confirm-return.php` - Filter `school_id` di WHERE
- ✅ **Select**: `public/borrows.php` - Filter `school_id` di WHERE
- ✅ **Select**: `public/api/borrowing-history.php` - Filter `school_id` di WHERE
- ✅ **Update**: `public/borrows.php` (update overdue) - Filter `school_id` di WHERE

### Data Buku (Books)

- ✅ **Select**: `public/books.php` - Filter `school_id` di WHERE
- ✅ **Select**: `public/api/get-book.php` - Filter `school_id` di WHERE
- ✅ **Insert**: `public/book-maintenance.php` - Wajib include `school_id`

### Data Member (Members)

- ✅ **Select**: `public/members.php` - Filter `school_id` di WHERE
- ✅ **Select**: `public/api/process-barcode.php` - Filter `school_id` di WHERE
- ✅ **Insert**: `src/MemberHelper.php` - Wajib include `school_id`

---

## 🛡️ Keamanan Multi-Tenant

### Prinsip Keamanan

1. **Jangan Percaya Parameter URL**

   ```php
   // ❌ TIDAK AMAN - school_id dari URL dapat dimanipulasi
   $sid = $_GET['school_id'];

   // ✅ AMAN - school_id dari session tidak bisa dimanipulasi
   $sid = $_SESSION['user']['school_id'];
   ```

2. **Selalu Validasi Di WHERE Clause**

   ```php
   // ❌ TIDAK AMAN - hanya check keberadaan data
   $stmt = $pdo->prepare('UPDATE borrows SET status=? WHERE id=?');

   // ✅ AMAN - juga check school_id
   $stmt = $pdo->prepare('UPDATE borrows SET status=? WHERE id=? AND school_id=?');
   ```

3. **Gunakan Prepared Statements**
   ```php
   // ✅ AMAN - prepared statement mencegah SQL injection
   $stmt = $pdo->prepare('SELECT * FROM borrows WHERE school_id=:sid');
   $stmt->execute(['sid' => $sid]);
   ```

---

## 📈 Monitoring Multi-Tenant

### MultiTenantManager Class

**File: `src/MultiTenantManager.php`**

```php
<?php
class MultiTenantManager {
    public function getSchool($school_id) {
        // Get complete school data dengan statistics
        $stmt = $this->pdo->prepare('
            SELECT s.*,
                   COUNT(DISTINCT b.id) as book_count,
                   COUNT(DISTINCT m.id) as student_count,
                   COUNT(DISTINCT bw.id) as borrow_count
            FROM schools s
            LEFT JOIN books b ON s.id = b.school_id
            LEFT JOIN members m ON s.id = m.school_id
            LEFT JOIN borrows bw ON s.id = bw.school_id
            WHERE s.id = :id
            GROUP BY s.id
        ');
        $stmt->execute(['id' => $school_id]);
        return $stmt->fetch();
    }
}
?>
```

---

## 🔍 Verification Queries

### Untuk Memverifikasi Pemisahan Data

```sql
-- Lihat berapa banyak buku per sekolah
SELECT school_id, COUNT(*) as total_books
FROM books
GROUP BY school_id;

-- Lihat berapa banyak member per sekolah
SELECT school_id, COUNT(*) as total_members
FROM members
GROUP BY school_id;

-- Lihat berapa banyak peminjaman per sekolah
SELECT school_id, COUNT(*) as total_borrows
FROM borrows
GROUP BY school_id;

-- Lihat status peminjaman per sekolah
SELECT school_id, status, COUNT(*) as total
FROM borrows
GROUP BY school_id, status;

-- Lihat detail peminjaman dengan isolasi sekolah
SELECT b.id, b.school_id, bk.title, m.name, b.status, b.borrowed_at
FROM borrows b
JOIN books bk ON b.book_id = bk.id
JOIN members m ON b.member_id = m.id
ORDER BY b.school_id, b.borrowed_at DESC;
```

---

## 📝 Best Practices untuk Development

### 1. Template Query Yang Aman

```php
// Selalu gunakan template ini:
$pdo->prepare(
    'SELECT * FROM [table]
     WHERE school_id = :sid [AND other conditions]'
)->execute(['sid' => $sid, 'other_param' => $value]);
```

### 2. Error Handling

```php
if ($stmt->rowCount() === 0) {
    // Data tidak ditemukan atau bukan milik sekolah ini
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    exit;
}
```

### 3. Logging

```php
error_log("[ACTION] school_id=$sid, user_id=$uid, action=approve_borrow");
```

### 4. Testing Multi-Tenant

```php
// Test dengan membuat 2 sekolah berbeda
// Login sebagai admin sekolah A
// Pastikan hanya data sekolah A yang terlihat
// Login sebagai admin sekolah B
// Pastikan hanya data sekolah B yang terlihat
// Pastikan tidak bisa approve peminjaman sekolah lain
```

---

## 🎓 Kesimpulan

✅ **Sistem Perpustakaan Online sudah dilengkapi dengan Multi-Tenant Architecture yang robust**

Setiap kali pengguna melakukan:

- ✅ **Login** → Session dibuat dengan `school_id` mereka
- ✅ **Melihat data** → Query otomatis difilter dengan `school_id` mereka
- ✅ **Mengubah data** → Wajib ada validasi `school_id` di WHERE clause
- ✅ **Melakukan transaksi** → Semua operasi terisolasi per sekolah

**Data terpisah sepenuhnya dan aman dari akses sekolah lain!**

---

## 📞 Pertanyaan Umum

**Q: Bagaimana jika user dari sekolah A mencoba akses data sekolah B?**
A: Sistem akan otomatis filtering berdasarkan `school_id` session user. Kalau `school_id` tidak match, akan return 404 atau 403.

**Q: Bagaimana jika ada SQL injection?**
A: Semua query menggunakan prepared statements, tidak ada string concatenation. SQL injection tidak mungkin.

**Q: Bagaimana jika admin ingin melihat semua sekolah?**
A: Biasanya ada akses super-admin yang tidak punya `school_id` spesifik, atau admin dengan role `admin_system` yang bisa override filter.

**Q: Bagaimana jika ada bug dalam filtering?**
A: Selalu ada `school_id` di WHERE clause, jadi worst case hanya ambil data salah satu sekolah, bukan data semua sekolah tercampur.

---

**Dokumentasi ini dibuat pada: 30 Januari 2026**
**Status: ✅ Multi-Tenant Implementation Complete**
