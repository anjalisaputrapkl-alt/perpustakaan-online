<?php
/**
 * SISTEM 2-LEVEL RETURN FLOW - DOKUMENTASI LENGKAP
 * 
 * Sistem pengembalian buku 2 tahap:
 * 1. Siswa mengajukan pengembalian
 * 2. Admin mengkonfirmasi pengembalian
 */

// ============================================================================
// ALUR SISTEM
// ============================================================================

/*

TAHAP 1: SISWA MENGAJUKAN PENGEMBALIAN
=====================================

1. Siswa membuka "Peminjaman Saya" di dashboard
2. Melihat tabel dengan status:
   - Dipinjam (tombol "Ajukan Pengembalian")
   - Terlambat (tombol "Ajukan Pengembalian")
   - Menunggu Konfirmasi (tidak ada tombol, hanya text "Menunggu admin")
   - Dikembalikan (tidak ada tombol)

3. Klik tombol "Ajukan Pengembalian"
4. Konfirmasi popup
5. Fetch ke api/student-request-return.php:
   - POST borrow_id
   - Validasi: borrow exists, belongs to student, status = borrowed/overdue
   - UPDATE borrows SET status = "pending_return"
   - Stok buku TIDAK berubah
   - Return JSON success

6. Alert popup "Permintaan dikirim ke admin"
7. Page reload - status menjadi "Menunggu Konfirmasi"


TAHAP 2: ADMIN MENGKONFIRMASI PENGEMBALIAN
==========================================

1. Admin membuka halaman borrows.php admin
2. Lihat 3 section:
   - Permintaan Pengembalian Menunggu Konfirmasi (status: pending_return)
   - Daftar Peminjaman Aktif (status: borrowed/overdue)
   - Riwayat Pengembalian Buku (status: returned)

3. Di section "Permintaan Pengembalian", ada tombol "Konfirmasi Pengembalian" (warna cyan/biru)
4. Klik tombol
5. Konfirmasi popup
6. Fetch ke api/admin-confirm-return.php:
   - POST borrow_id
   - Validasi: borrow exists, school_id match, status = pending_return
   - Transaction:
     - UPDATE borrows SET returned_at = NOW(), status = "returned"
     - UPDATE books SET copies = copies + 1
   - Return JSON success

7. Alert popup "Pengembalian dikonfirmasi!"
8. Page reload - data pindah ke "Riwayat Pengembalian Buku"


STATUS BORROW:
==============
- "borrowed" → Dipinjam (biru) - bisa ajukan pengembalian
- "overdue" → Terlambat (merah) - bisa ajukan pengembalian
- "pending_return" → Menunggu Konfirmasi (kuning/orange) - menunggu admin
- "returned" → Dikembalikan (hijau) - selesai

*/

// ============================================================================
// FILE-FILE YANG DIBUAT/DIUBAH
// ============================================================================

/*

1. api/student-request-return.php (BARU)
   - Handle permintaan pengembalian dari siswa
   - Update status: borrowed/overdue → pending_return
   - Stok buku TIDAK berubah

2. api/admin-confirm-return.php (BARU)
   - Handle konfirmasi pengembalian dari admin
   - Update status: pending_return → returned
   - Isi returned_at = NOW()
   - Tambah stok buku +1

3. public/student-dashboard.php (DIUBAH)
   - Tambah query peminjaman siswa
   - Tambah tabel "Peminjaman Saya" dengan status
   - Tambah tombol "Ajukan Pengembalian" untuk status borrowed/overdue
   - Tambah CSS: .btn-return-request
   - Tambah JS: function requestReturn(borrowId)

4. public/borrows.php (DIUBAH)
   - Update statistik: tambah pending_return count
   - Tambah section "Permintaan Pengembalian Menunggu Konfirmasi"
   - Update filter aktif: exclude pending_return dan returned
   - Tambah CSS: .status-pending, .btn-confirm-return
   - Tambah JS: function confirmReturn(borrowId)

*/

// ============================================================================
// QUERY DATABASE
// ============================================================================

/*

1. STUDENT REQUEST RETURN (student-request-return.php):

   // Check borrow exists dan belong to student
   SELECT id, status, book_id FROM borrows 
   WHERE id = :borrow_id 
   AND school_id = :school_id 
   AND member_id = :student_id

   // Update status
   UPDATE borrows SET status = "pending_return" 
   WHERE id = :borrow_id

2. ADMIN CONFIRM RETURN (admin-confirm-return.php):

   // Check borrow exists dan status pending_return
   SELECT id, book_id, status FROM borrows 
   WHERE id = :borrow_id 
   AND school_id = :school_id
   AND status = "pending_return"

   // Update borrow
   UPDATE borrows SET returned_at = NOW(), status = "returned" 
   WHERE id = :borrow_id

   // Update book stock
   UPDATE books SET copies = copies + 1 
   WHERE id = :book_id

3. STUDENT DASHBOARD (student-dashboard.php):

   // Get all borrows
   SELECT b.id, b.borrowed_at, b.due_at, b.returned_at, b.status, 
          bk.title, bk.author
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   WHERE b.school_id = :school_id 
   AND b.member_id = :member_id
   ORDER BY b.borrowed_at DESC

4. ADMIN BORROWS (borrows.php):

   // Get all borrows with pending_return
   SELECT b.*, bk.title, m.name AS member_name
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid
   ORDER BY b.borrowed_at DESC

   // Calculate pending count
   COUNT(*) WHERE status = "pending_return"

*/

// ============================================================================
// TESTING
// ============================================================================

/*

TEST FLOW 1: STUDENT REQUEST RETURN
1. Buka student-dashboard.php sebagai siswa
2. Lihat tabel "Peminjaman Saya"
3. Ada buku dengan status "Dipinjam"
4. Klik tombol "Ajukan Pengembalian"
5. Status berubah jadi "Menunggu Konfirmasi"
6. Stok buku masih sama (tidak berkurang)

TEST FLOW 2: ADMIN CONFIRM RETURN
1. Buka borrows.php sebagai admin
2. Lihat section "Permintaan Pengembalian Menunggu Konfirmasi"
3. Ada data dari siswa tadi
4. Klik tombol "Konfirmasi Pengembalian"
5. Data pindah ke "Riwayat Pengembalian Buku"
6. Stok buku bertambah 1
7. Status jadi "Dikembalikan"

*/

// ============================================================================
// FITUR KEAMANAN
// ============================================================================

/*

1. Student Request Return:
   ✓ Validasi user dari session
   ✓ Validasi borrow exists
   ✓ Validasi borrow belongs to student
   ✓ Validasi status = borrowed/overdue (tidak bisa ajukan jika sudah pending/returned)
   ✓ Response JSON dengan error handling

2. Admin Confirm Return:
   ✓ Validasi user authenticated (requireAuth)
   ✓ Validasi school_id match
   ✓ Validasi borrow exists
   ✓ Validasi status = pending_return (tidak bisa process jika status sudah changed)
   ✓ Transaction dengan rollback jika error
   ✓ Response JSON dengan error handling

*/

?>
