<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/FavoriteModel.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$studentId = $user['id'];

// Initialize variables
$categories = [];
$books = [];
$favorites = [];
$selectedCategory = '';
$errorMessage = '';
$successMessage = '';

try {
    $model = new FavoriteModel($pdo);

    // Get all categories
    $categories = $model->getCategories();

    // Get all books (default)
    $books = $model->getBooksByCategory(null);

    // Get favorites
    $favorites = $model->getFavorites($studentId);

} catch (Exception $e) {
    $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
}

$pageTitle = 'Koleksi Favorit';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Favorit - Perpustakaan Digital</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/favorites-style.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <!-- Navigation Sidebar -->
    <?php include 'partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container-main">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:heart" width="28" height="28"></iconify-icon>
                Koleksi Favorit
            </h1>
            <p>Simpan dan kelola buku-buku pilihan Anda</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <iconify-icon icon="mdi:check-circle" width="16" height="16"></iconify-icon>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Favorites Grid Section -->
        <div>
            <!-- Header dengan Stats -->


            <!-- Search, Filter, dan Sort Bar -->
            <?php if (!empty($favorites)): ?>
            <div class="favorites-controls">
                <!-- Search Bar -->
                <div class="control-group search-group">
                    <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari judul buku favorit...">
                    <button class="btn-clear-search" id="clearSearchBtn" style="display: none;" onclick="clearSearch()">
                        <iconify-icon icon="mdi:close" width="18" height="18"></iconify-icon>
                    </button>
                </div>

                <!-- Filter dan Sort Controls -->
                <div class="control-group filter-sort-group">
                    <select id="categoryFilter" class="filter-select" onchange="applyFilters()">
                        <option value="">Semua Kategori</option>
                    </select>

                    <select id="sortSelect" class="sort-select" onchange="applySorting()">
                        <option value="original">Urutan Awal</option>
                        <option value="a-z">A → Z</option>
                        <option value="z-a">Z → A</option>
                        <option value="newest">Terbaru</option>
                    </select>

                    <button class="btn-clear-filters" id="clearFiltersBtn" style="display: none;" onclick="clearAllFilters()">
                        <iconify-icon icon="mdi:filter-off" width="18" height="18"></iconify-icon>
                        Hapus Filter
                    </button>
                </div>
            </div>

            <!-- Stats Results -->
            <div class="filter-stats">
                <span id="resultsCount">Menampilkan <span id="activeCount"><?php echo count($favorites); ?></span> dari <span id="totalCount"><?php echo count($favorites); ?></span> buku</span>
            </div>
            <?php endif; ?>

            <?php if (empty($favorites)): ?>
                <div class="empty-state"
                    style="background: var(--card); border-radius: 12px; border: 1px solid var(--border); padding: 60px 40px; text-align: center;">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:heart-outline"></iconify-icon>
                    </div>
                    <h3>Belum ada favorit</h3>
                    <p>Mulai tambahkan buku favorit Anda sekarang!</p>
                </div>
            <?php else: ?>
                <div class="books-grid" id="favoritesList">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="book-card-vertical" data-favorite-id="<?php echo $fav['id_favorit']; ?>" data-book-id="<?php echo $fav['id_buku']; ?>">
                            <div class="book-cover-container">
                                <?php if ($fav['cover']): ?>
                                    <img src="../img/covers/<?php echo htmlspecialchars($fav['cover']); ?>"
                                        alt="<?php echo htmlspecialchars($fav['judul']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon>
                                    </div>
                                <?php endif; ?>

                                <button class="btn-love loved"
                                    onclick="toggleFavorite(event, <?php echo $fav['id_buku']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $fav['judul'])); ?>')">
                                    <iconify-icon icon="mdi:heart"></iconify-icon>
                                </button>
                            </div>

                            <div class="book-card-body">
                                <div class="book-category"><?php echo htmlspecialchars($fav['buku_kategori'] ?? 'Umum'); ?></div>
                                <div class="book-title" title="<?php echo htmlspecialchars($fav['judul']); ?>"><?php echo htmlspecialchars($fav['judul']); ?></div>
                                <div class="book-author"><?php echo htmlspecialchars($fav['penulis'] ?? '-'); ?></div>
                                
                                <div class="book-card-footer">
                                    <div class="shelf-info">
                                        <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon> 
                                        <span style="font-weight: 700;"><?php echo $fav['avg_rating'] ? round($fav['avg_rating'], 1) : '0'; ?></span>
                                        <span style="opacity: 0.6; margin-left: 2px;">(<?php echo (int)$fav['total_reviews']; ?>)</span>
                                        <?php if(!empty($fav['lokasi_rak'])): ?>
                                            <span style="opacity: 0.6; font-size: 10px; margin-left: auto;">• Rak <?= htmlspecialchars($fav['shelf'] ?? '-') ?> / <?= htmlspecialchars($fav['row_number'] ?? '-') ?> / <?= htmlspecialchars($fav['lokasi_rak']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn-icon-sm" onclick="viewDetail(<?php echo $fav['id_buku']; ?>)" title="Detail">
                                            <iconify-icon icon="mdi:eye"></iconify-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                        <button class="modal-btn modal-btn-close" onclick="closeBookModal()">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.allFavorites = <?php echo json_encode($favorites); ?>;
    </script>
    <script src="../assets/js/favorites-manage.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>