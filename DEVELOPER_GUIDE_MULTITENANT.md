# 👨‍💻 Developer Guide - Implementasi Multi-Tenant

Panduan lengkap untuk developer yang ingin menambah fitur baru dengan memastikan multi-tenant isolation tetap terjaga.

---

## 📋 Checklist Ketika Membuat Feature Baru

### 1️⃣ Identifikasi Data Apa Yang Akan Disimpan

**Pertanyaan:**

- Apakah data ini spesifik per sekolah atau global?
- Apakah data ini berhubungan dengan user/member dari sekolah tertentu?

**Contoh:**

- ❌ Global: Versi aplikasi, timezone database
- ✅ Per Sekolah: Buku, member, peminjaman, settings sekolah

### 2️⃣ Tambahkan Kolom school_id Ke Table

```sql
-- Contoh: Membuat tabel baru `book_reviews`
CREATE TABLE `book_reviews` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `school_id` INT NOT NULL,        -- ← WAJIB
    `book_id` INT NOT NULL,
    `member_id` INT NOT NULL,
    `rating` INT,
    `review_text` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,

    -- Indeks untuk performa
    KEY `idx_school` (`school_id`),
    KEY `idx_school_book` (`school_id`, `book_id`),
    UNIQUE KEY `unique_review` (`school_id`, `member_id`, `book_id`)
);
```

### 3️⃣ Buat PHP File Controller/Page

**Template:**

```php
<?php
// ==========================================
// 1. Require auth dan database
// ==========================================
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';

// ==========================================
// 2. Ambil school_id dari session
// ==========================================
$user = $_SESSION['user'];
$sid = $user['school_id'];  // ← JANGAN dari GET/POST

if (!$sid) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

// ==========================================
// 3. Proses request
// ==========================================

// SELECT - FILTER SCHOOL_ID
$stmt = $pdo->prepare(
    'SELECT * FROM book_reviews
     WHERE school_id = :sid'  // ← WAJIB ADA
);
$stmt->execute(['sid' => $sid]);
$reviews = $stmt->fetchAll();

// INSERT - INCLUDE SCHOOL_ID
$stmt = $pdo->prepare(
    'INSERT INTO book_reviews (school_id, book_id, member_id, rating, review_text)
     VALUES (:sid, :bid, :mid, :rating, :text)'
);
$stmt->execute([
    'sid' => $sid,  // ← WAJIB dari session
    'bid' => $_POST['book_id'],
    'mid' => $_POST['member_id'],
    'rating' => $_POST['rating'],
    'text' => $_POST['review']
]);

// UPDATE - FILTER SCHOOL_ID
$stmt = $pdo->prepare(
    'UPDATE book_reviews
     SET rating = :rating
     WHERE id = :id AND school_id = :sid'  // ← WAJIB ADA school_id
);
$stmt->execute([
    'id' => $_POST['id'],
    'sid' => $sid,
    'rating' => $_POST['rating']
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Review not found']);
    exit;
}

// DELETE - FILTER SCHOOL_ID
$stmt = $pdo->prepare(
    'DELETE FROM book_reviews
     WHERE id = :id AND school_id = :sid'  // ← WAJIB ADA school_id
);
$stmt->execute([
    'id' => $_POST['id'],
    'sid' => $sid
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Review not found']);
    exit;
}

?>
```

### 4️⃣ Buat API Endpoint (Jika Diperlukan)

**File: `public/api/add-book-review.php`**

```php
<?php
header('Content-Type: application/json');

require __DIR__ . '/../../src/auth.php';
requireAuth();

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    // ✅ Ambil school_id dari session
    $user = $_SESSION['user'];
    $sid = $user['school_id'];

    // Ambil data dari POST
    $book_id = (int) $_POST['book_id'] ?? 0;
    $member_id = (int) $_POST['member_id'] ?? 0;
    $rating = (int) $_POST['rating'] ?? 0;
    $review = $_POST['review'] ?? '';

    // ✅ Validasi: book harus dari sekolah ini
    $bookStmt = $pdo->prepare(
        'SELECT id FROM books
         WHERE id = :id AND school_id = :sid'  // ← Validasi school_id
    );
    $bookStmt->execute([
        'id' => $book_id,
        'sid' => $sid
    ]);

    if ($bookStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }

    // ✅ Validasi: member harus dari sekolah ini
    $memberStmt = $pdo->prepare(
        'SELECT id FROM members
         WHERE id = :id AND school_id = :sid'  // ← Validasi school_id
    );
    $memberStmt->execute([
        'id' => $member_id,
        'sid' => $sid
    ]);

    if ($memberStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Member not found']);
        exit;
    }

    // ✅ Insert dengan school_id dari session
    $stmt = $pdo->prepare(
        'INSERT INTO book_reviews (school_id, book_id, member_id, rating, review_text)
         VALUES (:sid, :bid, :mid, :rating, :text)'
    );
    $stmt->execute([
        'sid' => $sid,  // ← Dari session, bukan user input
        'bid' => $book_id,
        'mid' => $member_id,
        'rating' => $rating,
        'text' => $review
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Review berhasil ditambahkan',
        'review_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## 🔍 Code Review Checklist

### Sebelum Commit, Pastikan:

#### ✅ Authentication

- [ ] Semua page controller punya `require __DIR__ . '/../src/auth.php'`
- [ ] Semua page controller punya `requireAuth()`
- [ ] Semua API endpoint punya `requireAuth()`

#### ✅ School ID

- [ ] `$sid = $_SESSION['user']['school_id']` dideklarasikan
- [ ] Tidak ada `$sid = $_GET['school_id']`
- [ ] Tidak ada `$sid = $_POST['school_id']`

#### ✅ Database Queries

- [ ] SELECT queries: Ada `WHERE school_id = :sid`
- [ ] INSERT queries: Ada `:sid` di VALUES
- [ ] UPDATE queries: Ada `WHERE ... AND school_id = :sid`
- [ ] DELETE queries: Ada `WHERE ... AND school_id = :sid`

#### ✅ Data Validation

- [ ] Setelah SELECT, pastikan `rowCount() > 0`
- [ ] Setelah UPDATE/DELETE, pastikan `rowCount() > 0`
- [ ] Return 404 jika `rowCount() === 0`

#### ✅ Prepared Statements

- [ ] Tidak ada string concatenation (`'... WHERE id = ' . $id`)
- [ ] Semua user input pakai prepared statements
- [ ] Gunakan `:parameter` bukan `?` untuk clarity

#### ✅ Foreign Keys

- [ ] Jika referensi tabel lain, validasi school_id dulu
- [ ] Contoh: Sebelum insert review, validate book ada dan school_id match

---

## 📊 Real-World Scenarios

### Scenario 1: Tambah Kolom Ke Table Existing

**Problem:** Ingin tambah kolom `last_accessed` ke table `books`

**Solusi:**

```sql
ALTER TABLE `books` ADD COLUMN `last_accessed` TIMESTAMP NULL;
```

**Code Impact:**

```php
// Tidak perlu ubah code, school_id sudah ada
// Hanya pastikan query yang dimodifikasi tetap filter school_id

// ❌ SALAH
UPDATE books SET last_accessed = NOW() WHERE id = :id;

// ✅ BENAR
UPDATE books SET last_accessed = NOW()
WHERE id = :id AND school_id = :sid;
```

### Scenario 2: Tambah JOIN Query

**Problem:** Ingin tampilkan reviews dengan info member & buku

**Solusi:**

```php
// ✅ BENAR - Filter school_id di table utama
$stmt = $pdo->prepare(
    'SELECT br.*, b.title, m.name
     FROM book_reviews br
     JOIN books b ON br.book_id = b.id
     JOIN members m ON br.member_id = m.id
     WHERE br.school_id = :sid'  // ← Filter di table utama
);
$stmt->execute(['sid' => $sid]);

// ✅ ALTERNATIF - Filter di semua table (lebih safe)
$stmt = $pdo->prepare(
    'SELECT br.*, b.title, m.name
     FROM book_reviews br
     JOIN books b ON br.book_id = b.id AND b.school_id = :sid
     JOIN members m ON br.member_id = m.id AND m.school_id = :sid
     WHERE br.school_id = :sid'
);
$stmt->execute(['sid' => $sid]);
```

### Scenario 3: Aggregation Query

**Problem:** Ingin hitung total reviews per sekolah

**Solusi:**

```php
// ✅ BENAR
$stmt = $pdo->prepare(
    'SELECT school_id, COUNT(*) as review_count
     FROM book_reviews
     WHERE school_id = :sid  // ← Filter school_id
     GROUP BY school_id'
);
$stmt->execute(['sid' => $sid]);
$result = $stmt->fetch();

// Jika ada filter tambahan (book_id):
$stmt = $pdo->prepare(
    'SELECT book_id, COUNT(*) as review_count
     FROM book_reviews
     WHERE school_id = :sid AND book_id = :bid  // ← Filter school_id WAJIB
     GROUP BY book_id'
);
$stmt->execute(['sid' => $sid, 'bid' => $book_id]);
```

### Scenario 4: Subquery

**Problem:** Ambil member yang punya review terbanyak

**Solusi:**

```php
// ✅ BENAR - Filter school_id di subquery juga
$stmt = $pdo->prepare(
    'SELECT m.*, (
        SELECT COUNT(*) FROM book_reviews
        WHERE member_id = m.id AND school_id = :sid  // ← Filter di subquery
    ) as review_count
     FROM members m
     WHERE m.school_id = :sid  // ← Filter di query utama
     ORDER BY review_count DESC
     LIMIT 10'
);
$stmt->execute(['sid' => $sid]);
```

---

## 🚨 Common Mistakes & How to Fix

### Mistake 1: Forgot WHERE school_id

```php
// ❌ SALAH - Bisa return data sekolah lain
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute(['id' => $book_id]);

// ✅ BENAR
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id AND school_id = :sid');
$stmt->execute(['id' => $book_id, 'sid' => $sid]);
```

### Mistake 2: school_id dari user input

```php
// ❌ SALAH - Bisa dimanipulasi
$sid = $_GET['school_id'];

// ✅ BENAR
$sid = $_SESSION['user']['school_id'];
```

### Mistake 3: No validation after JOIN

```php
// ❌ SALAH - Tidak validate book & member dari sekolah sama
$stmt = $pdo->prepare(
    'INSERT INTO book_reviews (school_id, book_id, member_id, rating)
     VALUES (:sid, :bid, :mid, :rating)'
);
$stmt->execute([...]);  // Bisa insert book dari sekolah A, member dari sekolah B

// ✅ BENAR - Validate dulu
$bookStmt = $pdo->prepare(
    'SELECT id FROM books WHERE id = :id AND school_id = :sid'
);
$bookStmt->execute(['id' => $bid, 'sid' => $sid]);
if ($bookStmt->rowCount() === 0) die('Book not found');

$memberStmt = $pdo->prepare(
    'SELECT id FROM members WHERE id = :id AND school_id = :sid'
);
$memberStmt->execute(['id' => $mid, 'sid' => $sid]);
if ($memberStmt->rowCount() === 0) die('Member not found');

// Sekarang aman insert
```

### Mistake 4: Concatenation instead of prepared statement

```php
// ❌ SALAH - SQL Injection vulnerability
$id = $_POST['id'];
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ' . $id);

// ✅ BENAR
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute(['id' => $id]);
```

### Mistake 5: No rowCount validation

```php
// ❌ SALAH - Tidak tahu apakah berhasil
$stmt = $pdo->prepare('UPDATE books SET title = :title WHERE id = :id AND school_id = :sid');
$stmt->execute(['title' => $title, 'id' => $id, 'sid' => $sid]);

// ✅ BENAR
$stmt = $pdo->prepare('UPDATE books SET title = :title WHERE id = :id AND school_id = :sid');
$stmt->execute(['title' => $title, 'id' => $id, 'sid' => $sid]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}
```

---

## 🧪 Testing Multi-Tenant Implementation

### Unit Test Template

```php
<?php
// tests/MultiTenantTest.php

class MultiTenantTest {

    private $pdo;
    private $sid1 = 4;  // School A
    private $sid2 = 5;  // School B

    public function setUp() {
        $this->pdo = require __DIR__ . '/../src/db.php';
    }

    // Test: Select dengan school_id filter
    public function testSelectFiltersBySchool() {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM books WHERE school_id = :sid'
        );
        $stmt->execute(['sid' => $this->sid1]);
        $books = $stmt->fetchAll();

        foreach ($books as $book) {
            assert($book['school_id'] === $this->sid1,
                "Found book dari sekolah lain!");
        }
    }

    // Test: Insert dengan school_id otomatis
    public function testInsertIncludesSchoolId() {
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (school_id, title, author)
             VALUES (:sid, :title, :author)'
        );
        $stmt->execute([
            'sid' => $this->sid1,
            'title' => 'Test Book',
            'author' => 'Test Author'
        ]);

        $bookId = $this->pdo->lastInsertId();

        // Verify inserted
        $verify = $this->pdo->prepare(
            'SELECT school_id FROM books WHERE id = :id'
        );
        $verify->execute(['id' => $bookId]);
        $result = $verify->fetch();

        assert($result['school_id'] === $this->sid1,
            "Book tidak ter-assign ke sekolah yang benar!");
    }

    // Test: Update dengan school_id validation
    public function testUpdateRequiresSchoolId() {
        // Insert book di school A
        $insertStmt = $this->pdo->prepare(
            'INSERT INTO books (school_id, title) VALUES (:sid, :title)'
        );
        $insertStmt->execute(['sid' => $this->sid1, 'title' => 'Book A']);
        $bookId = $this->pdo->lastInsertId();

        // Try update dengan school B (HARUS GAGAL)
        $updateStmt = $this->pdo->prepare(
            'UPDATE books SET title = :title
             WHERE id = :id AND school_id = :sid'
        );
        $updateStmt->execute([
            'title' => 'Updated',
            'id' => $bookId,
            'sid' => $this->sid2  // ← School B
        ]);

        // Harus 0 rows affected
        assert($updateStmt->rowCount() === 0,
            "Update berhasil dengan school_id berbeda (SECURITY BUG!)");

        // Verify title tidak berubah
        $verify = $this->pdo->prepare(
            'SELECT title FROM books WHERE id = :id'
        );
        $verify->execute(['id' => $bookId]);
        $result = $verify->fetch();

        assert($result['title'] === 'Book A',
            "Title berhasil diubah dengan school_id berbeda!");
    }

    // Test: Cross-school access prevention
    public function testCrossSchoolAccessPrevention() {
        // Insert review di school A
        $insertStmt = $this->pdo->prepare(
            'INSERT INTO book_reviews (school_id, book_id, rating)
             VALUES (:sid, :bid, :rating)'
        );
        $insertStmt->execute([
            'sid' => $this->sid1,
            'bid' => 100,
            'rating' => 5
        ]);
        $reviewId = $this->pdo->lastInsertId();

        // Try delete dengan school B (HARUS GAGAL)
        $deleteStmt = $this->pdo->prepare(
            'DELETE FROM book_reviews WHERE id = :id AND school_id = :sid'
        );
        $deleteStmt->execute([
            'id' => $reviewId,
            'sid' => $this->sid2  // ← School B
        ]);

        // Harus 0 rows affected
        assert($deleteStmt->rowCount() === 0,
            "Delete berhasil dengan school_id berbeda (SECURITY BUG!)");

        // Verify record masih ada
        $verify = $this->pdo->prepare(
            'SELECT id FROM book_reviews WHERE id = :id'
        );
        $verify->execute(['id' => $reviewId]);

        assert($verify->rowCount() > 0,
            "Record terhapus dengan school_id berbeda!");
    }
}
?>
```

### Manual Testing Checklist

```
[ ] Login dengan admin sekolah A
  [ ] Buka halaman books → hanya tampil buku sekolah A
  [ ] Buka halaman borrows → hanya tampil peminjaman sekolah A
  [ ] Klik approve borrow → hanya bisa approve sekolah A
  [ ] Coba URL manipulasi ?school_id=5 → tetap tampil data sekolah A

[ ] Login dengan admin sekolah B
  [ ] Buka halaman books → hanya tampil buku sekolah B
  [ ] Buka halaman borrows → hanya tampil peminjaman sekolah B
  [ ] Klik approve borrow → hanya bisa approve sekolah B
  [ ] Coba URL manipulasi ?school_id=4 → tetap tampil data sekolah B

[ ] Cross-school access test
  [ ] Ambil borrow_id dari sekolah A
  [ ] Login dengan admin sekolah B
  [ ] Try approve borrow_id itu → HARUS error 404
  [ ] Try delete dengan API → HARUS error 404

[ ] SQL Injection test
  [ ] Try SQL: ?id=1 OR 1=1 → HARUS safe (pakai prepared stmt)
  [ ] Try SQL: ?school_id=1 UNION SELECT ... → Tidak ada effect
```

---

## 📚 Reference Links

- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [OWASP Multi-Tenancy](https://owasp.org/www-community/attacks/Multi-Tenancy)
- [Database Normalization](https://www.postgresql.org/docs/current/ddl.html)

---

**Dokumentasi dibuat: 30 Januari 2026**
**Untuk: Developer Perpustakaan Online**
