<?php
/**
 * REFERENSI QUERY SISTEM PEMINJAMAN SISWA
 * 
 * Salin-tempel query ini ke file yang sesuai
 */

// ============================================================================
// 1. QUERY: Update Stok Buku (SUDAH TERINTEGRASI di borrow-book.php)
// ============================================================================

/*
UPDATE books SET copies = copies - 1 WHERE id = :book_id
*/

// ============================================================================
// 2. QUERY: Insert Peminjaman Siswa (SUDAH TERINTEGRASI di borrow-book.php)
// ============================================================================

/*
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, status)
VALUES (:school_id, :book_id, :member_id, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), "borrowed")
*/

// ============================================================================
// 3. QUERY: Ambil Data Peminjaman Siswa (UNTUK DASHBOARD SISWA)
// ============================================================================

/*
SELECT b.id, b.borrowed_at, b.due_at, b.returned_at, b.status, 
       bk.id as book_id, bk.title, bk.author, bk.cover_image
FROM borrows b
JOIN books bk ON b.book_id = bk.id
WHERE b.school_id = :school_id 
AND b.member_id = :member_id
ORDER BY b.borrowed_at DESC
*/

// ============================================================================
// 4. QUERY: Update Status Overdue Otomatis (UNTUK DASHBOARD SISWA)
// ============================================================================

/*
UPDATE borrows SET status = "overdue"
WHERE school_id = :school_id 
AND member_id = :member_id
AND returned_at IS NULL 
AND due_at < NOW()
*/

// ============================================================================
// 5. QUERY: Validasi - Cek Apakah Siswa Sudah Meminjam Buku yang Sama
// ============================================================================

/*
SELECT id FROM borrows 
WHERE school_id = :school_id 
AND member_id = :member_id 
AND book_id = :book_id 
AND (status = "borrowed" OR status = "overdue")
*/

// ============================================================================
// 6. QUERY: Validasi - Cek Stok Buku
// ============================================================================

/*
SELECT id, copies FROM books 
WHERE id = :book_id AND school_id = :school_id
*/

// ============================================================================
// 7. QUERY: Hitung Peminjaman Aktif Siswa
// ============================================================================

/*
SELECT COUNT(*) as active_count FROM borrows 
WHERE school_id = :school_id 
AND member_id = :member_id 
AND status != 'returned'
*/

// ============================================================================
// 8. QUERY: Hitung Peminjaman Terlambat Siswa
// ============================================================================

/*
SELECT COUNT(*) as overdue_count FROM borrows 
WHERE school_id = :school_id 
AND member_id = :member_id 
AND status = 'overdue'
*/

// ============================================================================
// 9. PHP CODE: Ambil Data Peminjaman (SIAP COPY-PASTE)
// ============================================================================

/*
$pdo = require __DIR__ . '/../src/db.php';

// Update overdue status
$pdo->prepare(
    'UPDATE borrows SET status = "overdue"
     WHERE school_id = :school_id 
     AND member_id = :member_id
     AND returned_at IS NULL 
     AND due_at < NOW()'
)->execute([
    'school_id' => $school_id,
    'member_id' => $student_id
]);

// Get all borrowing records
$borrowStmt = $pdo->prepare(
    'SELECT b.id, b.borrowed_at, b.due_at, b.returned_at, b.status, 
            bk.id as book_id, bk.title, bk.author, bk.cover_image
     FROM borrows b
     JOIN books bk ON b.book_id = bk.id
     WHERE b.school_id = :school_id 
     AND b.member_id = :member_id
     ORDER BY b.borrowed_at DESC'
);
$borrowStmt->execute([
    'school_id' => $school_id,
    'member_id' => $student_id
]);
$my_borrows = $borrowStmt->fetchAll();

// Calculate statistics
$active_borrows = count(array_filter($my_borrows, fn($b) => $b['status'] !== 'returned'));
$overdue_count = count(array_filter($my_borrows, fn($b) => $b['status'] === 'overdue'));
$returned_count = count(array_filter($my_borrows, fn($b) => $b['status'] === 'returned'));
*/

// ============================================================================
// 10. FLOW PROSES LENGKAP PEMINJAMAN
// ============================================================================

/*
FLOW DIAGRAM:

Siswa klik tombol "Pinjam"
    ↓
borrowBook() function dipanggil (di student-dashboard.php)
    ↓
POST ke api/borrow-book.php
    ↓
Validasi:
    - Cek user dari session ✓
    - Cek book_id valid ✓
    - Cek stok buku > 0 ✓
    - Cek siswa belum pinjam buku yang sama ✓
    ↓
Jika semua validasi OK:
    - Insert ke tabel borrows (due_at = NOW() + 7 HARI) ✓
    - Update books SET copies = copies - 1 ✓
    - Return success JSON ✓
    ↓
JavaScript alert("Berhasil") dan location.reload()
    ↓
Dashboard siswa refresh dan tampilkan peminjaman baru
    ↓
Peminjaman muncul di halaman admin (borrows.php)
*/

// ============================================================================
// 11. INTEGRASI DI HALAMAN ADMIN (borrows.php)
// ============================================================================

/*
Data peminjaman siswa OTOMATIS muncul di admin borrows.php karena:

1. Saat siswa pinjam buku → insert ke tabel borrows
2. Admin membuka halaman admin/borrows.php
3. Query SELECT dari borrows dengan JOIN books dan members
4. Data siswa yang terdaftar di tabel members menampilkan nama siswa
5. Status "borrowed", "overdue", "returned" otomatis di-update

Jadi TIDAK ADA PERUBAHAN di halaman admin, semuanya terintegrasi otomatis!
*/

// ============================================================================
// 12. STATUS UPDATE OTOMATIS
// ============================================================================

/*
Status diupdate otomatis di:

a) Dashboard Siswa:
   - Saat membuka halaman: UPDATE borrows SET status='overdue' if due_at < NOW()
   
b) Halaman Admin:
   - Saat membuka halaman: UPDATE borrows SET status='overdue' if due_at < NOW()
   
c) Saat Admin klik "Konfirmasi Pengembalian":
   - UPDATE borrows SET returned_at=NOW(), status='returned' WHERE id=:id

Jadi siswa otomatis melihat status "Terlambat" jika melebihi due_at!
*/

?>
