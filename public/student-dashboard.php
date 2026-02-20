<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];

// Get member_id dengan auto-create jika belum ada
$memberHelper = new MemberHelper($pdo);
$member_id = $memberHelper->getMemberId($user);

// Get damage fines for this member
$damageController = new DamageController($pdo, $school_id);
$memberDamageFines = $damageController->getByMember($member_id);
$totalMemberDenda = 0;
$pendingMemberDenda = 0;
foreach ($memberDamageFines as $fine) {
    $totalMemberDenda += $fine['fine_amount'];
    if ($fine['status'] === 'pending') {
        $pendingMemberDenda += $fine['fine_amount'];
    }
}

// ===================== STATISTIK DASHBOARD =====================
// 1. Total Buku di sekolah ini
try {
    $totalBooksStmt = $pdo->prepare('SELECT COUNT(*) as total FROM books WHERE school_id = :school_id');
    $totalBooksStmt->execute(['school_id' => $school_id]);
    $totalBooks = (int) ($totalBooksStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    $totalBooks = 0;
}

// 2. Jumlah buku yang sedang dipinjam anggota ini
try {
    $borrowCountStmt = $pdo->prepare(
        'SELECT COUNT(*) as total FROM borrows 
         WHERE school_id = :school_id 
         AND member_id = :member_id 
         AND returned_at IS NULL'
    );
    $borrowCountStmt->execute(['school_id' => $school_id, 'member_id' => $member_id]);
    $borrowCount = (int) ($borrowCountStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    $borrowCount = 0;
}

// 3. Denda tertunda (overdue fines) dari keterlambatan peminjaman
// Menghitung denda keterlambatan (bukan damage fine, melainkan denda dari due date yang terlewat)
try {
    $lateFinesStmt = $pdo->prepare(
        'SELECT COUNT(*) as total FROM borrows 
         WHERE school_id = :school_id 
         AND member_id = :member_id 
         AND returned_at IS NULL 
         AND due_at < NOW()'
    );
    $lateFinesStmt->execute(['school_id' => $school_id, 'member_id' => $member_id]);
    $overdueCount = (int) ($lateFinesStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    $overdueCount = 0;
}

// 4. Get student's loan limit (max_pinjam)
try {
    $maxPinjamStmt = $pdo->prepare('SELECT max_pinjam FROM members WHERE id = :member_id');
    $maxPinjamStmt->execute(['member_id' => $member_id]);
    $maxPinjam = (int) ($maxPinjamStmt->fetch(PDO::FETCH_ASSOC)['max_pinjam'] ?? 2);
} catch (Exception $e) {
    $maxPinjam = 2;
}
// ===================== END STATISTIK DASHBOARD =====================

// ===================== QUERY PEMINJAMAN ANGGOTA =====================
// Update overdue status
$pdo->prepare(
    'UPDATE borrows SET status = "overdue"
     WHERE school_id = :school_id 
     AND member_id = :member_id
     AND returned_at IS NULL 
     AND due_at < NOW()'
)->execute(['school_id' => $school_id, 'member_id' => $member_id]);

// Get all borrowing records untuk anggota ini
$borrowStmt = $pdo->prepare(
    'SELECT b.id, b.borrowed_at, b.due_at, b.returned_at, b.status, 
            bk.id as book_id, bk.title, bk.author
     FROM borrows b
     JOIN books bk ON b.book_id = bk.id
     WHERE b.school_id = :school_id 
     AND b.member_id = :member_id
     ORDER BY b.borrowed_at DESC'
);
$borrowStmt->execute(['school_id' => $school_id, 'member_id' => $member_id]);
$my_borrows = $borrowStmt->fetchAll();

// Calculate statistics
$active_borrows = count(array_filter($my_borrows, fn($b) => $b['status'] !== 'returned'));
$overdue_count = count(array_filter($my_borrows, fn($b) => $b['status'] === 'overdue'));
$returned_count = count(array_filter($my_borrows, fn($b) => $b['status'] === 'returned'));
// ===================== END QUERY PEMINJAMAN ANGGOTA =====================

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query to get books with availability status and current borrower
$query = 'SELECT bk.*, 
                 curr_b.id as current_borrow_id, curr_b.due_at as borrower_due_at,
                 m.name as borrower_name,
                 (SELECT AVG(rating) FROM rating_buku WHERE id_buku = bk.id) as avg_rating,
                 (SELECT COUNT(*) FROM rating_buku WHERE id_buku = bk.id) as total_reviews
          FROM books bk
          LEFT JOIN borrows curr_b ON bk.id = curr_b.book_id AND curr_b.returned_at IS NULL
          LEFT JOIN members m ON curr_b.member_id = m.id
          WHERE bk.school_id = :school_id';
$params = ['school_id' => $school_id];

if (!empty($search)) {
    $query .= ' AND (title LIKE :search OR author LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $query .= ' AND category = :category';
    $params['category'] = $category;
}

// Sort options
switch ($sort) {
    case 'oldest':
        $query .= ' ORDER BY created_at ASC';
        break;
    case 'popular':
        $query .= ' ORDER BY view_count DESC';
        break;
    default: // newest
        $query .= ' ORDER BY created_at DESC';
}

$query .= ' LIMIT 100';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch (Exception $e) {
    $books = [];
}

// Get categories for filter
try {
    $catStmt = $pdo->prepare('SELECT DISTINCT category FROM books WHERE school_id = :school_id AND category IS NOT NULL AND category != "" ORDER BY category');
    $catStmt->execute(['school_id' => $school_id]);
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}

// Tambahkan default categories untuk option yang komprehensif
$defaultCategories = ['Fiksi', 'Non-Fiksi', 'Referensi', 'Biografi', 'Sejarah', 'Seni & Budaya', 'Teknologi', 'Pendidikan', 'Anak-anak', 'Komik', 'Majalah', 'Lainnya'];

// Merge dengan database categories untuk menampilkan semua opsi
$categories = array_unique(array_merge($categories, $defaultCategories));
sort($categories);



// Daftar semua buku sekolah (untuk ditampilkan ketika anggota klik 'Total Buku')
try {
    $booksAvailStmt = $pdo->prepare('SELECT bk.*, 
                                            curr_b.id as current_borrow_id, curr_b.due_at as borrower_due_at,
                                            m.name as borrower_name,
                                            (SELECT AVG(rating) FROM rating_buku WHERE id_buku = bk.id) as avg_rating,
                                            (SELECT COUNT(*) FROM rating_buku WHERE id_buku = bk.id) as total_reviews
                                     FROM books bk
                                     LEFT JOIN borrows curr_b ON bk.id = curr_b.book_id AND curr_b.returned_at IS NULL
                                     LEFT JOIN members m ON curr_b.member_id = m.id
                                     WHERE bk.school_id = :school_id 
                                     ORDER BY bk.created_at DESC');
    $booksAvailStmt->execute(['school_id' => $school_id]);
    $books_available = $booksAvailStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $books_available = [];
}

// Top viewed books (to represent 'buku yang sedang dilihat' apabila tidak ada tracking per-user)
try {
    $topViewedStmt = $pdo->prepare('SELECT id, title, author, cover_image, view_count FROM books WHERE school_id = :school_id ORDER BY view_count DESC LIMIT 10');
    $topViewedStmt->execute(['school_id' => $school_id]);
    $top_viewed_books = $topViewedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_viewed_books = [];
}

// Set dynamic page title
$userRole = $_SESSION['user']['role'] ?? 'student';
$roleLabel = 'Anggota';
if ($userRole === 'teacher') $roleLabel = 'Guru';
elseif ($userRole === 'employee') $roleLabel = 'Karyawan';

$pageTitle = 'Dashboard ' . $roleLabel;
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perpustakaan - <?php echo $pageTitle; ?></title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>

</head>

<body>
    <!-- Navigation Sidebar -->
    <?php include 'partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Anggota Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <div class="content-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Total Denda -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.4s ease-out;">
                    <h3><iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon> Denda Anda</h3>
                    <div
                        style="padding: 12px; background-color: <?php echo $pendingMemberDenda > 0 ? 'color-mix(in srgb, var(--danger) 5%, transparent)' : 'color-mix(in srgb, var(--success) 5%, transparent)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? 'var(--danger)' : 'var(--success)'; ?>; border: 1px solid <?php echo $pendingMemberDenda > 0 ? 'color-mix(in srgb, var(--danger) 15%, transparent)' : 'color-mix(in srgb, var(--success) 15%, transparent)'; ?>;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Denda Tertunda</div>
                        <div
                            style="font-size: 18px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-bottom: 8px;">
                            Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                        <?php if ($pendingMemberDenda > 0): ?>
                            <p style="font-size: 11px; color: var(--text-muted); margin: 0; line-height: 1.5;">Denda dari
                                kerusakan buku saat peminjaman. Silakan hubungi admin untuk detail.</p>
                        <?php else: ?>
                            <p style="font-size: 11px; color: var(--success); margin: 0; font-weight: 500;">✓ Tidak ada denda tertunda</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Category Filter -->
                <?php if (!empty($categories)): ?>
                <?php endif; ?>

                <!-- Library News -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.1s both;">
                    <h3><iconify-icon icon="mdi:bullhorn-variant" width="16" height="16"></iconify-icon> Info Perpus</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px;">
                            <div style="font-size: 10px; color: var(--primary); font-weight: 700; margin-bottom: 2px;">BARU DATANG</div>
                            <div style="font-size: 12px; font-weight: 600; color: var(--text);">5 Koleksi buku fiksi baru bulan Februari!</div>
                        </div>
                        <div style="padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px;">
                            <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">PENGUMUMAN</div>
                            <div style="font-size: 12px; font-weight: 500; color: var(--text-muted);">Kembalikan buku tepat waktu untuk menghindari denda.</div>
                        </div>
                    </div>
                </div>

                <!-- Trending Books -->
                <?php if (!empty($top_viewed_books)): ?>
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.2s both;">
                    <h3><iconify-icon icon="mdi:trending-up" width="16" height="16"></iconify-icon> Buku Terpopuler</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach (array_slice($top_viewed_books, 0, 3) as $pop_book): ?>
                            <div style="display: flex; gap: 12px; align-items: center; cursor: pointer;" onclick="openBookModal(<?php echo htmlspecialchars(json_encode($pop_book)); ?>)">
                                <div style="width: 45px; height: 60px; border-radius: 6px; overflow: hidden; flex-shrink: 0; background: var(--bg); border: 1px solid var(--border);">
                                    <?php if (!empty($pop_book['cover_image'])): ?>
                                        <img src="../img/covers/<?php echo htmlspecialchars($pop_book['cover_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted);"><iconify-icon icon="mdi:book" width="20"></iconify-icon></div>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($pop_book['title']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo (int)$pop_book['view_count']; ?> pembaca</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Access -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.3s both;">
                    <h3><iconify-icon icon="mdi:link-variant" width="16" height="16"></iconify-icon> Akses Cepat</h3>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <a href="favorites.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: var(--text); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='var(--bg)'; this.style.color='var(--primary)';" onmouseout="this.style.background='transparent'; this.style.color='var(--text)';">
                            <iconify-icon icon="mdi:heart-outline" width="18"></iconify-icon> Buku Favorit Saya
                        </a>
                        <a href="student-borrowing-history.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: var(--text); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='var(--bg)'; this.style.color='var(--primary)';" onmouseout="this.style.background='transparent'; this.style.color='var(--text)';">
                            <iconify-icon icon="mdi:history" width="18"></iconify-icon> Riwayat Pinjam
                        </a>
                    </div>
                </div>

                <!-- Jam Operasional -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.4s both;">
                    <div style="padding: 15px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%); border-radius: 15px; color: white;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <iconify-icon icon="mdi:clock-time-four-outline" width="20"></iconify-icon>
                            <span style="font-size: 13px; font-weight: 700;">Jam Operasional</span>
                        </div>
                        <div style="font-size: 12px; opacity: 0.9; line-height: 1.6;">
                            Senin - Jumat: 07:30 - 15:30<br>
                            Sabtu & Libur: Tutup
                        </div>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 5px;">
                            <div style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%;"></div>
                            <span style="font-size: 11px; font-weight: 600;">Sedang Buka</span>
                        </div>
                    </div>
                </div>

            </aside>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Modern Search Bar with Category Dropdown -->
                <!-- KPI Cards -->
                <div class="kpi-grid" role="list">
                    <a class="kpi-card" href="javascript:void(0)" onclick="showTotalBooksModal()" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Total Buku</div>
                            <div class="kpi-value"><?php echo $totalBooks; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:book-open-variant" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="javascript:void(0)" onclick="showCurrentBorrowsModal()" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Sedang Dipinjam</div>
                            <div class="kpi-value"><?php echo $borrowCount; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:clock-outline" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="javascript:void(0)" onclick="showOverdueBorrowsModal()" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Terlambat / Overdue</div>
                            <div class="kpi-value" style="color: var(--danger);"><?php echo $overdueCount ?? $overdue_count ?? $overdue_borrows ?? 0; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: color-mix(in srgb, var(--danger) 10%, transparent); color: var(--danger);">
                            <iconify-icon icon="mdi:alert-circle-outline" width="20" height="20"></iconify-icon>
                        </div>
                    </a>
                </div>

                <form method="get" class="modern-search-bar-form" onsubmit="return false;">
                    <!-- Search Bar (Dominant) -->
                    <div class="search-bar-wrapper">
                        <div class="search-bar-container">
                            <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                            <input type="text" name="search" class="modern-search-input"
                                placeholder="Cari buku…"
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>

                    <!-- Category Dropdown - Select Element -->
                    <select id="categorySelect" class="category-dropdown-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Sort Dropdown -->
                    <select id="sortSelect" class="category-dropdown-select">
                        <option value="newest">Terbaru</option>
                        <option value="rating">Rating Tertinggi</option>
                    </select>

                    <input type="hidden" name="category" id="categoryInput" value="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>">
                </form>

                <!-- Books Grid -->
                <div class="books-grid">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                            <?php 
                                $is_available = empty($book['current_borrow_id']); 
                                $is_teacher_only = ($book['access_level'] ?? 'all') === 'teacher_only';
                            ?>
                            <div class="book-card-vertical" data-book-id="<?php echo $book['id']; ?>">
                                <div class="book-cover-container">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                            alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($is_teacher_only): ?>
                                        <div class="stock-badge-overlay" style="
                                            background: color-mix(in srgb, var(--warning) 15%, transparent);
                                            color: var(--warning);
                                            border: 1px solid color-mix(in srgb, var(--warning) 30%, transparent);
                                        ">
                                            Khusus Guru
                                        </div>
                                    <?php else: ?>
                                        <div class="stock-badge-overlay" style="
                                            background: <?= $is_available ? 'color-mix(in srgb, var(--success) 15%, transparent)' : 'color-mix(in srgb, var(--danger) 15%, transparent)' ?>;
                                            color: <?= $is_available ? 'var(--success)' : 'var(--danger)' ?>;
                                            border: 1px solid <?= $is_available ? 'color-mix(in srgb, var(--success) 30%, transparent)' : 'color-mix(in srgb, var(--danger) 30%, transparent)' ?>;
                                        ">
                                            <?= $is_available ? 'Tersedia' : 'Dipinjam' ?>
                                        </div>
                                    <?php endif; ?>


                                    <button class="btn-love"
                                        onclick="toggleFavorite(event, <?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                        <iconify-icon icon="mdi:heart-outline"></iconify-icon>
                                    </button>
                                </div>

                                <div class="book-card-body">
                                    <div class="book-category"><?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?></div>
                                    <div class="book-title" title="<?php echo htmlspecialchars($book['title']); ?>"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="book-author"><?php echo htmlspecialchars($book['author'] ?? '-'); ?></div>
                                    
                                    <?php if (!$is_available): ?>
                                        <p style="font-size: 10px; color: var(--danger); margin: -8px 0 8px 0;">Oleh: <?php echo htmlspecialchars($book['borrower_name']); ?></p>
                                    <?php endif; ?>

                                    <div class="book-card-footer">
                                        <div class="shelf-info">
                                            <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon> 
                                            <span style="font-weight: 700;"><?php echo $book['avg_rating'] ? round($book['avg_rating'], 1) : '0'; ?></span>
                                            <span style="opacity: 0.6; margin-left: 2px;">(<?php echo (int)$book['total_reviews']; ?>)</span>
                                            <?php if(!empty($book['lokasi_rak'])): ?>
                                                <span style="opacity: 0.6; font-size: 10px; margin-left: auto;">• Rak <?= htmlspecialchars($book['shelf'] ?? '-') ?> / <?= htmlspecialchars($book['row_number'] ?? '-') ?> / <?= htmlspecialchars($book['lokasi_rak']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="action-buttons">
                                          <button class="btn-icon-sm" onclick="openBookModal(<?php echo htmlspecialchars(json_encode($book)); ?>)" title="Detail">
                                             <iconify-icon icon="mdi:eye"></iconify-icon>
                                          </button>
                                          <a href="book-rating.php?id=<?php echo $book['id']; ?>" class="btn-icon-sm" title="Rating & Review" style="color: var(--primary);">
                                             <iconify-icon icon="mdi:star-outline"></iconify-icon>
                                          </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><iconify-icon icon="mdi:book-search-outline" width="64"
                                    height="64"></iconify-icon></div>
                            <h3>Buku Tidak Ditemukan</h3>
                            <p>Coba ubah filter atau cari dengan kata kunci yang berbeda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Book Detail Modal -->
    <div class="modal" id="bookModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Buku</h2>
                <button class="modal-close" onclick="closeBookModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-book-left">
                    <div class="modal-book-cover">
                        <img id="modalBookCover" src="" alt="Cover" style="display: none;">
                        <iconify-icon id="modalBookIcon" icon="mdi:book-open-variant" width="80"
                            height="80"></iconify-icon>
                    </div>
                    <h3 class="modal-book-title" id="modalBookTitle">-</h3>
                </div>

                <div class="modal-book-info">
                    <div class="modal-book-meta">
                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Pengarang</span>
                            <span class="modal-book-item-value" id="modalBookAuthor">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Kategori</span>
                            <span class="modal-book-item-value" id="modalBookCategory">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">ISBN</span>
                            <span class="modal-book-item-value" id="modalBookISBN">-</span>
                        </div>



                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Lokasi / Rak</span>
                            <span class="modal-book-item-value" id="modalBookShelf">-</span>
                        </div>



                    </div>

                    <div class="modal-actions">
                        <a id="modalRatingBtn" href="#" class="modal-btn modal-btn-borrow" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; background: rgba(58, 127, 242, 0.1); border: 1px solid var(--primary); color: var(--primary);">
                            <iconify-icon icon="mdi:star-outline"></iconify-icon> Rating & Komentar
                        </a>
                        <button class="modal-btn modal-btn-close" onclick="closeBookModal()">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.currentBorrowCount = <?php echo (int)$borrowCount; ?>;
        window.allBooks = <?php echo json_encode($books); ?>;
        window.BOOKS_AVAILABLE_SERVER = <?php echo json_encode(array_values(array_map(function($b){ 
            return [
                'id' => $b['id'],
                'title' => $b['title'] ?? '', 
                'author' => $b['author'] ?? '-', 
                'category' => $b['category'] ?? '-', 
                'current_borrow_id' => $b['current_borrow_id'],
                'borrower_name' => $b['borrower_name'],
                'borrower_due_at' => $b['borrower_due_at']
            ]; 
        }, $books_available ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        window.BOOKS_AVAILABLE_FALLBACK = <?php echo json_encode(array_values(array_map(function($b){ 
            return [
                'id' => $b['id'],
                'title' => $b['title'] ?? '', 
                'author' => $b['author'] ?? '-', 
                'category' => $b['category'] ?? '-', 
                'current_borrow_id' => $b['current_borrow_id'],
                'borrower_name' => $b['borrower_name'],
                'borrower_due_at' => $b['borrower_due_at']
            ]; 
        }, $books ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        window.BOOKS_AVAILABLE = (Array.isArray(window.BOOKS_AVAILABLE_SERVER) && window.BOOKS_AVAILABLE_SERVER.length > 0) ? window.BOOKS_AVAILABLE_SERVER : window.BOOKS_AVAILABLE_FALLBACK;
        
        window.STUDENT_CURRENT_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ 
            return [
                'title' => $r['title'] ?? '', 
                'borrowed_at' => $r['borrowed_at'], 
                'due_at' => $r['due_at'], 
                'status' => $r['status']
            ]; 
        }, array_filter($my_borrows, function($r){ return is_null($r['returned_at']); }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        window.STUDENT_ACTIVE_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ 
            return [
                'title' => $r['title'] ?? '', 
                'borrowed_at' => $r['borrowed_at'], 
                'due_at' => $r['due_at'], 
                'status' => $r['status']
            ]; 
        }, array_filter($my_borrows, function($r){ return is_null($r['returned_at']) && $r['status'] !== 'overdue'; }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        window.STUDENT_OVERDUE_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ 
            return [
                'title' => $r['title'] ?? '', 
                'borrowed_at' => $r['borrowed_at'], 
                'due_at' => $r['due_at'], 
                'status' => $r['status']
            ]; 
        }, array_filter($my_borrows, function($r){ return $r['status'] === 'overdue'; }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
    </script>
    <script src="../assets/js/student-dashboard-manage.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>