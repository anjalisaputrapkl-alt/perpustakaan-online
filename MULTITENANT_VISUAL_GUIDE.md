# 🎯 Multi-Tenant Visual Guide & Quick Reference

---

## 📊 System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    PERPUSTAKAAN ONLINE                      │
│                  (Sistem Terpisah Per Sekolah)              │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │      SINGLE DATABASE INSTANCE       │
        │    perpustakaan_online (MySQL)      │
        └─────────────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                ▼             ▼             ▼
        ┌──────────────┐  ┌────────────┐  ┌────────────┐
        │  SEKOLAH A   │  │ SEKOLAH B  │  │ SEKOLAH C  │
        │ (school_id=4)│  │(school_id=5)│  │(school_id=6)│
        │              │  │            │  │            │
        │ 7 Buku       │  │ 10 Buku    │  │ 5 Buku     │
        │ 2 Member     │  │ 5 Member   │  │ 3 Member   │
        │ 4 Peminjaman │  │ 8 Peminjaman │ 2 Peminjaman│
        │              │  │            │  │            │
        │ ✅ ISOLATED  │  │✅ ISOLATED │  │✅ ISOLATED │
        └──────────────┘  └────────────┘  └────────────┘
```

---

## 🔐 Data Isolation Mechanism

### Saat User Login

```
┌─────────────────────────────────────┐
│    ADMIN SEKOLAH A LOGIN            │
│    Username: admin_a@skolah-a.com   │
│    Password: xxxxxxxx              │
└──────────────────┬──────────────────┘
                   │
                   ▼
        ┌──────────────────────────┐
        │ Validasi credentials     │
        │ Cek di tabel users       │
        └──────────┬───────────────┘
                   │
                   ▼
        ┌──────────────────────────────────┐
        │ SELECT school_id FROM users      │
        │ WHERE username = 'admin_a@...'   │
        │ RESULT: school_id = 4            │
        └──────────┬───────────────────────┘
                   │
                   ▼
        ┌──────────────────────────────────┐
        │ Buat SESSION                     │
        │ $_SESSION['user'] = [            │
        │   'id' => 10,                    │
        │   'name' => 'Admin A',           │
        │   'school_id' => 4  ← KEY        │
        │ ]                                │
        └──────────┬───────────────────────┘
                   │
                   ▼
        ┌──────────────────────────────────┐
        │ Redirect ke Dashboard            │
        │ Admin A HANYA BISA LIHAT DATA    │
        │ DENGAN school_id = 4             │
        └──────────────────────────────────┘
```

### Saat User Akses Data

```
ADMIN A BUKA HALAMAN PEMINJAMAN
│
├─ Halaman: public/borrows.php
│
├─ Code:
│  require auth.php;
│  requireAuth();  ← Check: User sudah login?
│
│  $sid = $_SESSION['user']['school_id'];  ← Ambil: 4
│
│  $stmt = $pdo->prepare(
│    'SELECT * FROM borrows
│     WHERE school_id = :sid'  ← Filter: school_id = 4
│  );
│  $stmt->execute(['sid' => $sid]);
│  $borrows = $stmt->fetchAll();
│
└─ Database Query:
   SELECT * FROM borrows
   WHERE school_id = 4

   RESULT:
   ┌─────┬────────────┬────────┬────────┐
   │ id  │ school_id  │ status │ ...    │
   ├─────┼────────────┼────────┼────────┤
   │ 1   │ 4          │ ...    │ ...    │
   │ 2   │ 4          │ ...    │ ...    │
   │ 3   │ 4          │ ...    │ ...    │
   │ 4   │ 4          │ ...    │ ...    │
   └─────┴────────────┴────────┴────────┘

   ✅ HANYA TAMPIL DATA SEKOLAH 4
   ❌ DATA SEKOLAH 5 & 6 TIDAK MUNCUL
```

---

## 🛡️ Security Layers

### Layer 1: Authentication

```
LOGIN FORM → Session Check → Redirect ke Login
              ✅ Authenticated → Continue
              ❌ Not Authenticated → Redirect
```

### Layer 2: Session-Based School ID

```
$sid = $_SESSION['user']['school_id']
       ▲
       │
       ├─ Dari database (aman)
       └─ Bukan dari URL parameter (aman)

❌ UNSAFE:
   $sid = $_GET['school_id'];  ← User bisa manipulasi

✅ SAFE:
   $sid = $_SESSION['user']['school_id'];  ← Tidak bisa dimanipulasi
```

### Layer 3: WHERE Clause Filter

```
QUERY: SELECT * FROM books WHERE id=:id AND school_id=:sid

       ┌─────────────────────┐
       │ Check 1: id match?  │
       │ Check 2: school_id  │
       │ match?              │
       │                     │
       │ BOTH HARUS MATCH    │
       └─────────────────────┘

RESULT:
✅ FOUND & SAME SCHOOL → Return data
❌ FOUND & DIFF SCHOOL → Return 404
❌ NOT FOUND → Return 404
```

### Layer 4: API Validation

```
API ENDPOINT: POST /api/approve-borrow.php

INPUT: borrow_id = 100

VALIDATION:
1. Check authentication → requireAuth()
2. Get school_id → $_SESSION['user']['school_id'] = 4
3. Query: UPDATE borrows
           SET status='approved'
           WHERE id=100 AND school_id=4
4. Check result → rowCount() === 0 → 404
                → rowCount() > 0 → Success

RESULT:
✅ borrow_id 100 milik school 4 → APPROVE
❌ borrow_id 100 milik school 5 → 404 ERROR
❌ borrow_id 100 tidak ada → 404 ERROR
```

---

## 📋 Query Patterns

### ✅ CORRECT PATTERNS

#### 1. SELECT (Read)

```php
$stmt = $pdo->prepare(
    'SELECT * FROM books
     WHERE school_id = :sid'
);
$stmt->execute(['sid' => $sid]);
$books = $stmt->fetchAll();

// Result: Hanya buku dari sekolah ini
```

#### 2. INSERT (Create)

```php
$stmt = $pdo->prepare(
    'INSERT INTO books (school_id, title, author, isbn)
     VALUES (:sid, :title, :author, :isbn)'
);
$stmt->execute([
    'sid' => $sid,  // ← dari session
    'title' => $_POST['title'],
    'author' => $_POST['author'],
    'isbn' => $_POST['isbn']
]);

// Result: Book otomatis terikat ke sekolah ini
```

#### 3. UPDATE (Edit)

```php
$stmt = $pdo->prepare(
    'UPDATE books
     SET title = :title, author = :author
     WHERE id = :id AND school_id = :sid'
);
$stmt->execute([
    'id' => $book_id,
    'sid' => $sid,
    'title' => $_POST['title'],
    'author' => $_POST['author']
]);

if ($stmt->rowCount() === 0) {
    // Not found atau sekolah beda
    http_response_code(404);
    exit;
}

// Result: Hanya update book dari sekolah ini
```

#### 4. DELETE (Remove)

```php
$stmt = $pdo->prepare(
    'DELETE FROM books
     WHERE id = :id AND school_id = :sid'
);
$stmt->execute(['id' => $book_id, 'sid' => $sid]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit;
}

// Result: Hanya delete book dari sekolah ini
```

---

## ❌ INCORRECT PATTERNS

### Mistake 1: No WHERE school_id

```php
// ❌ WRONG
$stmt = $pdo->prepare('UPDATE books SET title=:t WHERE id=:id');
$stmt->execute(['t' => $title, 'id' => $id]);

// 🚨 PROBLEM: Bisa update book dari sekolah lain!

// ✅ FIX
$stmt = $pdo->prepare('UPDATE books SET title=:t WHERE id=:id AND school_id=:sid');
$stmt->execute(['t' => $title, 'id' => $id, 'sid' => $sid]);
```

### Mistake 2: school_id dari user input

```php
// ❌ WRONG
$sid = $_GET['school_id'];  // User bisa ubah ke 5, 6, 7, ...
$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id=:sid');
$stmt->execute(['sid' => $sid]);

// 🚨 PROBLEM: User bisa lihat data sekolah lain!

// ✅ FIX
$sid = $_SESSION['user']['school_id'];  // Dari server, tidak bisa diubah
$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id=:sid');
$stmt->execute(['sid' => $sid]);
```

### Mistake 3: No validation after JOIN

```php
// ❌ WRONG
$stmt = $pdo->prepare(
    'INSERT INTO borrows (school_id, book_id, member_id)
     VALUES (:sid, :bid, :mid)'
);
$stmt->execute(['sid' => $sid, 'bid' => $bid, 'mid' => $mid]);

// 🚨 PROBLEM: book_id dari sekolah A, member_id dari sekolah B bisa ter-insert!

// ✅ FIX
// Validate book
$book = $pdo->prepare(
    'SELECT id FROM books WHERE id=:id AND school_id=:sid'
)->execute(['id' => $bid, 'sid' => $sid])->fetch();
if (!$book) die('Book not found');

// Validate member
$member = $pdo->prepare(
    'SELECT id FROM members WHERE id=:id AND school_id=:sid'
)->execute(['id' => $mid, 'sid' => $sid])->fetch();
if (!$member) die('Member not found');

// NOW safe to insert
$stmt = $pdo->prepare(
    'INSERT INTO borrows (school_id, book_id, member_id)
     VALUES (:sid, :bid, :mid)'
);
$stmt->execute(['sid' => $sid, 'bid' => $bid, 'mid' => $mid]);
```

### Mistake 4: String concatenation instead of prepared statement

```php
// ❌ WRONG - SQL INJECTION RISK
$id = $_POST['id'];
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ' . $id);
// Kalau $id = "1 OR 1=1", akan select semua data!

// ✅ FIX
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute(['id' => $id]);
```

---

## 🔄 Complete User Journey

### SCENARIO: Siswa A dari Sekolah A Meminjam Buku

```
STEP 1: SISWA A LOGIN
┌──────────────────────────────────┐
│ Login ke sistem                  │
│ Username: anjali_saputra         │
│ Password: xxxxxxxx               │
│                                  │
│ Backend:                         │
│ - Check credentials              │
│ - Get school_id = 4              │
│ - Create session dengan sid=4    │
└────────────┬─────────────────────┘
             │
STEP 2: SISWA A LIHAT DAFTAR BUKU
┌────────────┴──────────────────────┐
│ Browser: /public/student-dashboard│
│                                    │
│ Backend:                           │
│ - requireAuth() ✅                │
│ - $sid = 4                        │
│ - Query books: WHERE school_id=4  │
│ - Show: 7 buku dari sekolah A     │
│         (buku sekolah B tidak     │
│          muncul)                  │
└────────────┬─────────────────────┘
             │
STEP 3: SISWA A PINJAM BUKU ID=3
┌────────────┴─────────────────────┐
│ Click: "Pinjam Buku"             │
│ POST /api/borrow-book.php        │
│ Data: book_id=3                  │
│                                   │
│ Backend:                          │
│ - requireAuth() ✅               │
│ - $sid = 4                       │
│ - Validate: book_id=3 ada?       │
│   Query: SELECT id FROM books    │
│   WHERE id=3 AND school_id=4     │
│   Result: ✅ Found (buku sekolah│
│   A)                             │
│ - Insert borrow:                 │
│   INSERT INTO borrows            │
│   (school_id, book_id, member_id,│
│    status)                       │
│   VALUES (4, 3, 1, 'borrowed')   │
│ - Response: Success              │
└────────────┬─────────────────────┘
             │
STEP 4: ADMIN SEKOLAH A LIHAT PEMINJAMAN
┌────────────┴──────────────────────┐
│ Admin A login                      │
│ Session dengan school_id=4         │
│                                    │
│ Halaman: /public/borrows.php      │
│                                    │
│ Backend:                           │
│ - requireAuth() ✅                │
│ - $sid = 4                        │
│ - Query: SELECT FROM borrows      │
│   WHERE school_id=4               │
│ - Show: 4 peminjaman dari sekolah │
│   A (peminjaman sekolah B tidak   │
│   muncul)                         │
│                                    │
│ Admin A klik "Terima Peminjaman"  │
│ POST /api/approve-borrow.php      │
│ Data: borrow_id=5                 │
│                                    │
│ Backend:                           │
│ - requireAuth() ✅                │
│ - $sid = 4                        │
│ - UPDATE borrows                  │
│   SET status='approved'           │
│   WHERE id=5 AND school_id=4      │
│ - Result: ✅ Success              │
│   (borrow_id 5 dari sekolah A)    │
│                                    │
│ JIKA TRY UPDATE borrow_id=8       │
│ (milik sekolah B):                │
│ - UPDATE WHERE id=8 AND school_id=│
│   4                               │
│ - Result: rowCount()=0 (not found)│
│ - Response: 404 Error ❌           │
│   (Admin B tidak bisa update)      │
└────────────────────────────────────┘
```

---

## 📊 Database Structure Visualization

### Tables & Relationships

```
SCHOOLS (Global)
│
├─→ BOOKS
│   ├─ id (PK)
│   ├─ school_id (FK)  ← Terikat ke sekolah
│   ├─ title
│   └─ ...
│
├─→ MEMBERS
│   ├─ id (PK)
│   ├─ school_id (FK)  ← Terikat ke sekolah
│   ├─ name
│   ├─ nisn
│   └─ ...
│
├─→ BORROWS
│   ├─ id (PK)
│   ├─ school_id (FK)  ← Terikat ke sekolah
│   ├─ book_id (FK)    ← Must match school_id
│   ├─ member_id (FK)  ← Must match school_id
│   ├─ status
│   └─ ...
│
└─→ BOOK_DAMAGE_FINES
    ├─ id (PK)
    ├─ school_id (FK)  ← Terikat ke sekolah
    ├─ borrow_id (FK)  ← Must match school_id
    └─ ...
```

---

## ✅ Implementation Checklist

### Saat Membuat Feature Baru

```
┌─────────────────────────────────────────┐
│ 1. DESIGN TABLE SCHEMA                  │
├─────────────────────────────────────────┤
│ ☐ Tambah kolom school_id INT NOT NULL  │
│ ☐ Tambah FOREIGN KEY ke schools table  │
│ ☐ Tambah INDEX di school_id            │
│ ☐ Unique constraints aware of school_id│
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 2. IMPLEMENT PHP CONTROLLER             │
├─────────────────────────────────────────┤
│ ☐ require __DIR__ . '/../src/auth.php'  │
│ ☐ requireAuth()                         │
│ ☐ $sid = $_SESSION['user']['school_id']│
│                                         │
│ FOR SELECT:                             │
│ ☐ WHERE school_id = :sid                │
│                                         │
│ FOR INSERT:                             │
│ ☐ :sid in VALUES clause                 │
│                                         │
│ FOR UPDATE:                             │
│ ☐ WHERE ... AND school_id = :sid        │
│                                         │
│ FOR DELETE:                             │
│ ☐ WHERE ... AND school_id = :sid        │
│                                         │
│ FOR ALL CRUD:                           │
│ ☐ Check rowCount() after query          │
│ ☐ Return 404 if rowCount() === 0        │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 3. CODE REVIEW                          │
├─────────────────────────────────────────┤
│ ☐ No $sid = $_GET['school_id']          │
│ ☐ No string concatenation               │
│ ☐ All queries use prepared statements   │
│ ☐ All CRUD has school_id filter         │
│ ☐ Data validation after JOIN            │
│ ☐ Error handling dengan 404 responses   │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 4. TEST                                 │
├─────────────────────────────────────────┤
│ ☐ Test dengan school A login            │
│ ☐ Test dengan school B login            │
│ ☐ Verify only own school data visible   │
│ ☐ Try cross-school access → Must fail   │
│ ☐ Try SQL injection → Must be safe      │
│ ☐ Check performance (use indices)       │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 5. DEPLOY                               │
├─────────────────────────────────────────┤
│ ☐ Database migrations applied           │
│ ☐ Indices created                       │
│ ☐ Foreign keys verified                 │
│ ☐ Monitoring set up                     │
│ ☐ Backup strategy ready                 │
└─────────────────────────────────────────┘
```

---

## 🚀 Quick Start Reference

### For Backend Developers

```php
// TEMPLATE YANG AMAN
<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];  // ← Selalu ambil dari sini

// SELECT
$stmt = $pdo->prepare(
    'SELECT * FROM books WHERE school_id = :sid'
);
$stmt->execute(['sid' => $sid]);

// INSERT
$stmt = $pdo->prepare(
    'INSERT INTO books (school_id, title) VALUES (:sid, :title)'
);
$stmt->execute(['sid' => $sid, 'title' => $_POST['title']]);

// UPDATE
$stmt = $pdo->prepare(
    'UPDATE books SET title=:title WHERE id=:id AND school_id=:sid'
);
$stmt->execute(['title' => $_POST['title'], 'id' => $id, 'sid' => $sid]);

// DELETE
$stmt = $pdo->prepare(
    'DELETE FROM books WHERE id=:id AND school_id=:sid'
);
$stmt->execute(['id' => $id, 'sid' => $sid]);

// VALIDATION
if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false]);
    exit;
}
?>
```

---

**Last Updated: 30 Januari 2026**
**Status: Complete & Production Ready ✅**
