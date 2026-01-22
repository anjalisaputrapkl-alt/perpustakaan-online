<?php
/**
 * SISTEM PEMINJAMAN SISWA - DOKUMENTASI & KODE
 * 
 * File ini berisi query dan kode yang dapat digunakan di halaman
 * student-dashboard.php untuk menampilkan data peminjaman siswa.
 */

// ============================================================================
// 1. QUERY UNTUK MENAMPILKAN PEMINJAMAN SISWA (bagian atas student-dashboard)
// ============================================================================

// Tambahkan di session_start() area atau setelah user authentication

$pdo = require __DIR__ . '/../src/db.php';

// Jika user sudah login
if (isset($_SESSION['user'])) {
    $student_id = $_SESSION['user']['id'];
    $school_id = $_SESSION['user']['school_id'];

    // Update overdue status untuk siswa ini
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

    // Get all borrowing records untuk siswa ini
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
} else {
    $my_borrows = [];
    $active_borrows = 0;
    $overdue_count = 0;
    $returned_count = 0;
}

// ============================================================================
// 2. STRUKTUR HTML UNTUK MENAMPILKAN PEMINJAMAN (Tambah di student-dashboard)
// ============================================================================

/*

<!-- STATISTICS SECTION -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px;">
    <div style="background: var(--card); border: 1px solid var(--border); padding: 16px; border-radius: 8px;">
        <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 8px 0;">Sedang Dipinjam</p>
        <p style="font-size: 24px; font-weight: 700; color: var(--primary); margin: 0;"><?php echo $active_borrows; ?></p>
    </div>
    <div style="background: var(--card); border: 1px solid var(--border); padding: 16px; border-radius: 8px;">
        <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 8px 0;">Terlambat</p>
        <p style="font-size: 24px; font-weight: 700; color: var(--danger); margin: 0;"><?php echo $overdue_count; ?></p>
    </div>
    <div style="background: var(--card); border: 1px solid var(--border); padding: 16px; border-radius: 8px;">
        <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 8px 0;">Sudah Dikembalikan</p>
        <p style="font-size: 24px; font-weight: 700; color: var(--success); margin: 0;"><?php echo $returned_count; ?></p>
    </div>
</div>

<!-- BORROW TABLE SECTION -->
<div style="background: var(--card); border: 1px solid var(--border); padding: 20px; border-radius: 12px;">
    <h2 style="font-size: 16px; font-weight: 700; margin: 0 0 16px 0;">Peminjaman Saya</h2>
    
    <?php if (empty($my_borrows)): ?>
        <div style="text-align: center; padding: 40px 20px;">
            <iconify-icon icon="mdi:book-off" style="font-size: 48px; color: var(--text-muted); margin-bottom: 12px;"></iconify-icon>
            <p style="color: var(--text-muted); font-size: 14px;">Anda belum meminjam buku apapun</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border); background: #f9fafb;">
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Judul Buku</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Pengarang</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Tanggal Pinjam</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Jatuh Tempo</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_borrows as $borrow): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px; font-size: 13px; color: var(--text);">
                                <strong><?php echo htmlspecialchars($borrow['title']); ?></strong>
                            </td>
                            <td style="padding: 12px; font-size: 13px; color: var(--text-muted);">
                                <?php echo htmlspecialchars($borrow['author'] ?? '-'); ?>
                            </td>
                            <td style="padding: 12px; font-size: 13px; color: var(--text); text-align: center;">
                                <?php echo date('d/m/Y', strtotime($borrow['borrowed_at'])); ?>
                            </td>
                            <td style="padding: 12px; font-size: 13px; color: var(--text); text-align: center;">
                                <?php echo $borrow['due_at'] ? date('d/m/Y', strtotime($borrow['due_at'])) : '-'; ?>
                            </td>
                            <td style="padding: 12px; font-size: 13px; text-align: center;">
                                <?php if ($borrow['status'] === 'overdue'): ?>
                                    <span style="display: inline-block; padding: 6px 12px; background: rgba(239, 68, 68, 0.1); color: #dc2626; border-radius: 6px; font-size: 12px; font-weight: 600;">Terlambat</span>
                                <?php elseif ($borrow['status'] === 'returned'): ?>
                                    <span style="display: inline-block; padding: 6px 12px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 6px; font-size: 12px; font-weight: 600;">Dikembalikan</span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 6px 12px; background: rgba(58, 127, 242, 0.1); color: #3a7ff2; border-radius: 6px; font-size: 12px; font-weight: 600;">Dipinjam</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

*/

// ============================================================================
// 3. JAVASCRIPT YANG SUDAH ADA DI student-dashboard.php SUDAH BENAR
// ============================================================================

/*
Kode ini sudah ada di student-dashboard.php dan TIDAK PERLU DIUBAH:

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
*/

// ============================================================================
// 4. IMPLEMENTASI LENGKAP (SIAP COPY-PASTE KE student-dashboard.php)
// ============================================================================

/*

LETAKKAN DI AWAL FILE student-dashboard.php (SETELAH session_start()):

<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$student_id = $user['id'];

// ===================== QUERY PEMINJAMAN SISWA =====================
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

// Get all borrowing records untuk siswa ini
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
// ===================== END QUERY PEMINJAMAN SISWA =====================

// ... REST OF THE CODE ...

*/

?>
