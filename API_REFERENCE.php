<?php
/**
 * API ENDPOINTS REFERENCE
 * Semua endpoint untuk sistem peminjaman & pengembalian buku
 */

// ============================================================================
// 1. BORROW BOOK - Siswa meminjam buku
// ============================================================================

/*
ENDPOINT: POST api/borrow-book.php
METHOD: POST
AUTH: Required (Session)

REQUEST:
--------
body: {
  book_id: 123 (integer, required)
}

VALIDATION:
-----------
✓ User authenticated (session)
✓ book_id valid (integer > 0)
✓ Book exists in this school
✓ Book stock > 0
✓ Student hasn't borrowed this book yet (status borrowed/overdue)

PROCESS:
--------
1. Check book exists & has stock
2. Check student didn't borrow same book (not returned yet)
3. INSERT into borrows:
   - school_id: from session
   - book_id: from request
   - member_id: from session (student id)
   - borrowed_at: NOW()
   - due_at: NOW() + 7 DAYS
   - status: "borrowed"
4. UPDATE books: copies - 1
5. Commit transaction (rollback on error)

RESPONSE:
---------
Success (200):
{
  "success": true,
  "message": "Buku berhasil dipinjam!",
  "borrow_id": 456
}

Error:
{
  "success": false,
  "message": "Error message"
}

Possible Errors:
- User not authenticated (401)
- Invalid book_id (400)
- Book not found (404)
- Stock habis (400)
- Sudah pinjam buku ini (400)
- Database error (500)
*/


// ============================================================================
// 2. STUDENT REQUEST RETURN - Siswa ajukan pengembalian
// ============================================================================

/*
ENDPOINT: POST api/student-request-return.php
METHOD: POST
AUTH: Required (Session)

REQUEST:
--------
body: {
  borrow_id: 456 (integer, required)
}

VALIDATION:
-----------
✓ User authenticated (session)
✓ borrow_id valid (integer > 0)
✓ Borrow exists
✓ Borrow belongs to this student
✓ Borrow status = "borrowed" or "overdue" (not pending/returned)

PROCESS:
--------
1. Check borrow exists & belongs to student
2. Validate status = borrowed/overdue
3. UPDATE borrows:
   - status: "pending_return"
   - (returned_at: NOT changed, stays NULL)
4. Commit transaction

RESPONSE:
---------
Success (200):
{
  "success": true,
  "message": "Permintaan pengembalian telah dikirim ke admin"
}

Error:
{
  "success": false,
  "message": "Error message"
}

Possible Errors:
- User not authenticated (401)
- Invalid borrow_id (400)
- Borrow not found (404)
- Borrow status tidak valid (400) - jika sudah pending/returned
- Database error (500)
*/


// ============================================================================
// 3. ADMIN CONFIRM RETURN - Admin konfirmasi pengembalian
// ============================================================================

/*
ENDPOINT: POST api/admin-confirm-return.php
METHOD: POST
AUTH: Required (requireAuth - admin check)

REQUEST:
--------
body: {
  borrow_id: 456 (integer, required)
}

VALIDATION:
-----------
✓ User authenticated & authorized as admin
✓ borrow_id valid (integer > 0)
✓ Borrow exists in same school
✓ Borrow status = "pending_return" (not borrowed/overdue/returned)

PROCESS:
--------
1. BEGIN TRANSACTION
2. Check borrow exists & status = pending_return
3. Get book_id from borrow
4. UPDATE borrows:
   - status: "returned"
   - returned_at: NOW()
5. UPDATE books:
   - copies: copies + 1
6. COMMIT transaction (ROLLBACK on error)

RESPONSE:
---------
Success (200):
{
  "success": true,
  "message": "Pengembalian buku telah dikonfirmasi"
}

Error:
{
  "success": false,
  "message": "Error message"
}

Possible Errors:
- User not authenticated (401)
- Invalid borrow_id (400)
- Borrow not found (404)
- Borrow status not pending (400) - jika status sudah changed
- Database error (500)
*/


// ============================================================================
// FLOW DIAGRAM
// ============================================================================

/*

COMPLETE FLOW:

Student Dashboard
    ↓
(1) POST api/borrow-book.php {book_id: 123}
    ↓
    ├─ Validate ✓
    ├─ INSERT borrows (status: borrowed)
    ├─ UPDATE books (copies -1)
    └─ Return success
    ↓
Muncul di "Peminjaman Saya" (status: Dipinjam)
    ↓
(2) POST api/student-request-return.php {borrow_id: 456}
    ↓
    ├─ Validate ✓
    ├─ UPDATE borrows (status: pending_return)
    └─ Return success
    ↓
Status berubah jadi "Menunggu Konfirmasi"
    
---

Admin Dashboard
    ↓
Lihat "Permintaan Pengembalian Menunggu Konfirmasi"
    ↓
(3) POST api/admin-confirm-return.php {borrow_id: 456}
    ↓
    ├─ Validate ✓
    ├─ BEGIN TRANSACTION
    ├─ UPDATE borrows (status: returned, returned_at: NOW())
    ├─ UPDATE books (copies +1)
    ├─ COMMIT
    └─ Return success
    ↓
Data pindah ke "Riwayat Pengembalian Buku"
Status: Dikembalikan, Stok +1
*/


// ============================================================================
// CURL EXAMPLES
// ============================================================================

/*

1. BORROW BOOK:
curl -X POST http://localhost/perpustakaan-online/public/api/borrow-book.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "book_id=123"

2. REQUEST RETURN:
curl -X POST http://localhost/perpustakaan-online/public/api/student-request-return.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "borrow_id=456"

3. CONFIRM RETURN:
curl -X POST http://localhost/perpustakaan-online/public/api/admin-confirm-return.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "borrow_id=456"
*/


// ============================================================================
// STATUS CODES
// ============================================================================

/*

200 OK
- Request successful
- Response: JSON with success: true/false

400 Bad Request
- Invalid parameters
- Validation failed (stok habis, sudah pinjam, etc)

401 Unauthorized
- User not authenticated
- Session invalid

404 Not Found
- Resource not found (book, borrow, etc)

405 Method Not Allowed
- Wrong HTTP method (GET instead of POST)

500 Internal Server Error
- Database error
- Server error
*/


// ============================================================================
// JAVASCRIPT USAGE
// ============================================================================

/*

1. BORROW BOOK (di student-dashboard.php):

function borrowBook(bookId, bookTitle) {
  if (!confirm('Apakah Anda ingin meminjam ' + bookTitle + '?')) {
    return;
  }

  fetch('api/borrow-book.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'book_id=' + bookId
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Buku berhasil dipinjam! Silakan ambil di perpustakaan.');
        location.reload();
      } else {
        alert(data.message || 'Gagal meminjam buku');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Terjadi kesalahan');
    });
}

2. REQUEST RETURN (di student-dashboard.php):

function requestReturn(borrowId) {
  if (!confirm('Apakah Anda ingin mengajukan pengembalian buku ini?')) {
    return;
  }

  fetch('api/student-request-return.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'borrow_id=' + borrowId
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Permintaan pengembalian telah dikirim ke admin!');
        location.reload();
      } else {
        alert(data.message || 'Gagal mengajukan pengembalian');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Terjadi kesalahan');
    });
}

3. CONFIRM RETURN (di borrows.php):

function confirmReturn(borrowId) {
  if (!confirm('Konfirmasi pengembalian buku ini?')) {
    return;
  }

  fetch('api/admin-confirm-return.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'borrow_id=' + borrowId
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Pengembalian buku telah dikonfirmasi!');
        location.reload();
      } else {
        alert(data.message || 'Gagal mengkonfirmasi pengembalian');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Terjadi kesalahan');
    });
}
*/

?>
