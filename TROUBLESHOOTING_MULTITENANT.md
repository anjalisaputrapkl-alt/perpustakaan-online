# 🆘 Troubleshooting & FAQ - Multi-Tenant System

---

## ❓ Frequently Asked Questions

### Q1: Bagaimana jika sebuah user login dari 2 sekolah berbeda?

**A:** Sistem saat ini menggunakan single session per browser. Ketika user login:

1. Session lama di-destroy
2. Session baru dibuat dengan school_id terbaru
3. User hanya bisa akses data sekolah terbaru

**Implementasi:**

```php
// src/auth.php
session_destroy();  // Hapus session lama
session_start();    // Buat session baru
$_SESSION['user'] = [
    'id' => $user['id'],
    'school_id' => $user['school_id'],  // ← School terbaru
    ...
];
```

**Solusi untuk multi-school users:**

- Buat tombol "Switch School" di dashboard
- Atau gunakan separate accounts per sekolah
- Atau implement role-based dengan parent company account

---

### Q2: Bagaimana jika Ada duplikasi member NISN di sekolah berbeda?

**A:** Diperbolehkan! Sistem sudah design untuk ini.

**Database Constraint:**

```sql
-- Unique NISN hanya dalam satu sekolah, bukan global
UNIQUE KEY `unique_nisn_per_school` (`school_id`, `nisn`);
```

**Artinya:**

- Sekolah A: Bisa punya siswa NISN=0094234
- Sekolah B: Bisa punya siswa NISN=0094234 (different person)
- Sistem akan memisahkan mereka berdasarkan school_id

**Contoh:**

```sql
-- Dua member dengan NISN sama di sekolah berbeda
INSERT INTO members (school_id, name, nisn) VALUES (4, 'Anjali A', '0094234');
INSERT INTO members (school_id, name, nisn) VALUES (5, 'Anjali B', '0094234');

-- Saat query, data akan terpisah
SELECT * FROM members WHERE nisn='0094234' AND school_id=4;  -- Ambil Anjali A
SELECT * FROM members WHERE nisn='0094234' AND school_id=5;  -- Ambil Anjali B
```

---

### Q3: Bagaimana jika ada user dengan role admin yang ingin melihat semua sekolah?

**A:** Sistem tidak support super-admin multi-school per saat. Opsi:

1. **Buat Separate Account:**
   - Admin sekolah A: account admin_a@sekolah-a.com
   - Admin sekolah B: account admin_b@sekolah-b.com
   - Ganti browser tab / incognito untuk switch

2. **Implement Super Admin Role** (future enhancement):

   ```php
   // Tambahkan kolom role di users table
   ALTER TABLE users ADD COLUMN role ENUM('admin', 'super_admin');

   // Super admin tidak punya school_id fixed
   if ($user['role'] === 'super_admin') {
       // Bisa filter school_id dari parameter
       $sid = $_GET['school_id'] ?? 1;
   } else {
       // Regular admin hanya akses school_id mereka
       $sid = $_SESSION['user']['school_id'];
   }
   ```

3. **Implement Dashboard Aggregation:**
   ```php
   // Untuk reporting, buat API khusus
   if ($_SESSION['user']['role'] === 'super_admin') {
       // SELECT dari semua sekolah
       $stmt = $pdo->prepare('SELECT * FROM borrows');
   } else {
       // SELECT hanya sekolah mereka
       $stmt = $pdo->prepare('SELECT * FROM borrows WHERE school_id = :sid');
   }
   ```

---

### Q4: Bagaimana kalau ada bug di code dan accidentally terbuka data sekolah lain?

**A:** Beberapa lapisan proteksi:

1. **Database Level:**
   - Foreign key constraints memastikan data relasi valid
   - Tidak bisa insert borrow yang reference book dari sekolah lain

2. **Application Level:**
   - WHERE clause dengan school_id di setiap query
   - rowCount() validation untuk detect anomali

3. **Monitoring:**
   - Error logs dengan school_id untuk audit
   - Bisa trace siapa yang akses apa

**Emergency Recovery:**

```sql
-- Jika ada suspicion data leak, bisa audit:
SELECT * FROM borrows WHERE school_id !=
    (SELECT school_id FROM members WHERE id = member_id);

-- Jika ada cross-school relationships (shouldn't exist):
SELECT * FROM books b
WHERE NOT EXISTS (
    SELECT 1 FROM members m
    WHERE m.school_id = b.school_id
    AND m.id = (SELECT member_id FROM borrows WHERE book_id = b.id)
);
```

---

### Q5: Kalau add fitur baru, apa yang harus diperhatian untuk multi-tenant?

**A:** Lihat checklist di `DEVELOPER_GUIDE_MULTITENANT.md`, tapi ringkas:

✅ **MUST DO:**

- [ ] Table punya kolom `school_id`
- [ ] Foreign key ke `schools` table
- [ ] Setiap SELECT filter `WHERE school_id = :sid`
- [ ] Setiap INSERT include `:sid`
- [ ] Setiap UPDATE/DELETE filter `WHERE ... AND school_id = :sid`
- [ ] Ambil `$sid` dari `$_SESSION['user']['school_id']`, bukan user input

❌ **MUST NOT:**

- [ ] Jangan ambil school_id dari GET/POST
- [ ] Jangan lupa WHERE clause school_id
- [ ] Jangan pakai string concatenation
- [ ] Jangan forgot rowCount() validation

---

## 🔴 Troubleshooting Common Issues

### Issue 1: "Data sekolah A muncul saat login sekolah B"

**Root Causes:**

1. Query lupa WHERE school_id
2. school_id di-hardcode ke nilai lama
3. Session tidak ter-update setelah login

**Diagnosis:**

```php
// Check 1: Session sudah updated?
var_dump($_SESSION['user']['school_id']);  // Should be 5 untuk school B

// Check 2: Query ada WHERE school_id?
// Buka Network tab, lihat query yang dikirim ke DB
// Seharusnya ada "WHERE school_id = 5"

// Check 3: Cek error log
tail -f /path/to/error.log
// Catat query yang di-run
```

**Fix:**

1. Search file punya `WHERE` clause lengkap
2. Pastikan `$sid = $_SESSION['user']['school_id']` di awal
3. Clear browser cache & session

```bash
# Clear PHP session files
rm -rf /path/to/session_files/*

# Restart browser
# Re-login
```

---

### Issue 2: "Update record tapi tidak berubah"

**Root Causes:**

1. WHERE clause filter school_id, record dari sekolah lain
2. rowCount() === 0 tapi tidak error

**Diagnosis:**

```php
// Check rowCount
$stmt = $pdo->prepare('UPDATE books SET title=:title WHERE id=:id AND school_id=:sid');
$stmt->execute(['title' => $title, 'id' => $id, 'sid' => $sid]);

echo "Rows affected: " . $stmt->rowCount();  // Should > 0
```

**Fix:**

```php
// Setelah execute, validate
if ($stmt->rowCount() === 0) {
    // Record tidak ditemukan (wrong school_id atau id not exist)
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}
```

---

### Issue 3: "Borrow dari book sekolah A, member sekolah B"

**Root Causes:**

- Lupa validate related records dari school_id sama

**Diagnosis:**

```sql
-- Find cross-school references
SELECT b.id, b.school_id as book_sid, br.id, br.school_id as borrow_sid
FROM book_reviews br
JOIN books b ON br.book_id = b.id
WHERE b.school_id != br.school_id;  -- Should return 0 rows
```

**Fix:**

```php
// BEFORE insert borrow, validate book & member
$book = $pdo->prepare('SELECT id FROM books WHERE id=:id AND school_id=:sid')
    ->execute(['id' => $bid, 'sid' => $sid])->fetch();

$member = $pdo->prepare('SELECT id FROM members WHERE id=:mid AND school_id=:sid')
    ->execute(['id' => $mid, 'sid' => $sid])->fetch();

if (!$book || !$member) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// NOW SAFE to insert
```

---

### Issue 4: "Performance slow untuk besar school_id"

**Root Causes:**

- Table besar, query tanpa index
- Nested loop joins

**Diagnosis:**

```sql
-- Check index
SHOW INDEX FROM borrows;  -- Should have idx_borrows_school

-- Explain query
EXPLAIN SELECT * FROM borrows WHERE school_id = 4 AND status = 'borrowed';

-- Should use index, not full table scan
```

**Fix:**

```sql
-- Add missing index
ALTER TABLE borrows ADD KEY `idx_school_status` (`school_id`, `status`);

-- Re-run query, should be fast
```

---

### Issue 5: "Cross-school borrow approval"

**Problem:** Admin sekolah B bisa approve borrow sekolah A

**Root Cause:** API lupa WHERE school_id

**Diagnosis:**

```php
// Check approve-borrow.php line ~48
var_dump($_SESSION['user']['school_id']);  // Should filter dengan ini

// Check query
// Jika query: UPDATE borrows SET status=:status WHERE id=:id
// ❌ WRONG - tidak ada school_id filter
// Harus: UPDATE borrows SET status=:status WHERE id=:id AND school_id=:sid
```

**Fix:**

```php
// File: public/api/approve-borrow.php
$sid = $_SESSION['user']['school_id'];

$stmt = $pdo->prepare(
    'UPDATE borrows
     SET status="approved", due_at=:due_at
     WHERE id=:id AND school_id=:sid'  // ← ADD THIS
);
$stmt->execute([
    'id' => $borrow_id,
    'sid' => $sid,
    'due_at' => $due_date
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Borrow not found']);
    exit;
}
```

---

## 🧪 Testing Checklist

### Before Deploy

```
[ ] Multi-school scenario test
  [ ] Create 2 school accounts
  [ ] Login school A, verify only school A data visible
  [ ] Login school B, verify only school B data visible
  [ ] No cross-school data leak

[ ] SQL Query audit
  [ ] Search codebase untuk "SELECT *" tanpa WHERE school_id
  [ ] Search untuk "UPDATE" tanpa WHERE school_id
  [ ] Search untuk "DELETE" tanpa WHERE school_id
  [ ] Search untuk "$_GET['school_id']" atau "$_POST['school_id']"
  [ ] All should return 0 results

[ ] Security test
  [ ] Try URL manipulation: ?school_id=5
  [ ] Try API POST: borrow_id milik school lain
  [ ] Try SQL injection: ?id=1 OR 1=1
  [ ] All should fail safely

[ ] Performance test
  [ ] Check INDEX di semua school_id columns
  [ ] Query dengan large dataset harus <1s
  [ ] No N+1 query problems
```

---

## 📋 Audit Query

Run this untuk verify sistem sudah multi-tenant clean:

```sql
-- Check semua table ada school_id
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE COLUMN_NAME = 'school_id'
AND TABLE_SCHEMA = 'perpustakaan_online'
ORDER BY TABLE_NAME;

-- Check foreign keys ke schools
SELECT TABLE_NAME, CONSTRAINT_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE COLUMN_NAME = 'school_id'
AND REFERENCED_TABLE_NAME = 'schools'
AND TABLE_SCHEMA = 'perpustakaan_online';

-- Check indexes
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE COLUMN_NAME = 'school_id'
AND TABLE_SCHEMA = 'perpustakaan_online'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Check unique constraints (should be school_id aware)
SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'perpustakaan_online'
AND COLUMN_NAME IN ('nisn', 'isbn', 'email')
ORDER BY TABLE_NAME;
```

---

## 📞 Support & Escalation

### Jika Menemukan Issue

1. **Check Documentation**
   - MULTI_TENANT_GUIDE.md
   - DEVELOPER_GUIDE_MULTITENANT.md

2. **Run Diagnostic**

   ```bash
   # Check error logs
   tail -100 /path/to/error.log | grep -i 'school\|multitenant'

   # Check recent queries
   # (Database activity log jika available)
   ```

3. **Escalate if needed**
   - Document the issue
   - Provide reproduction steps
   - Include relevant query logs
   - Include session info

---

**Last Updated: 30 Januari 2026**
**Status: Stable & Tested ✅**
