<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    header('Location: student-dashboard.php');
    exit;
}

// Get book details
$bookStmt = $pdo->prepare('SELECT * FROM books WHERE id = :id AND school_id = :school_id');
$bookStmt->execute(['id' => $book_id, 'school_id' => $user['school_id']]);
$book = $bookStmt->fetch();

if (!$book) {
    header('Location: student-dashboard.php');
    exit;
}

// Get ratings summary
$summaryStmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as r5,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as r4,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as r3,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as r2,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as r1
    FROM rating_buku 
    WHERE id_buku = :book_id
');
$summaryStmt->execute(['book_id' => $book_id]);
$summary = $summaryStmt->fetch();

$total_reviews = $summary['total_reviews'] ?: 0;
$avg_rating = round($summary['avg_rating'] ?: 0, 1);

// Get all comments
$commentsStmt = $pdo->prepare('
    SELECT r.*, u.name, u.role, s.foto
    FROM rating_buku r
    JOIN users u ON r.id_user = u.id
    LEFT JOIN siswa s ON u.nisn = s.nisn
    WHERE r.id_buku = :book_id
    ORDER BY r.created_at DESC
');
$commentsStmt->execute(['book_id' => $book_id]);
$comments = $commentsStmt->fetchAll();

$pageTitle = 'Rating & Komentar: ' . $book['title'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?> - SmartLibrary</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/book-rating-style.css">
</head>
<body>
    <?php include 'partials/student-sidebar.php'; ?>
    <?php include 'partials/student-header.php'; ?>

    <div class="main-container-rating">
        <div class="rating-container">
            <a href="student-dashboard.php" class="back-link">
                <iconify-icon icon="mdi:arrow-left"></iconify-icon> Kembali ke Dashboard
            </a>

            <!-- Book Summary -->
            <div class="book-header-card">
                <div class="book-img-wrapper">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="../img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Cover">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--accent-light);">
                            <iconify-icon icon="mdi:book" width="48" color="var(--accent)"></iconify-icon>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book-info-main">
                    <h1 class="book-title-h1"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="book-author-p">Oleh <?php echo htmlspecialchars($book['author'] ?: '-'); ?></p>
                    <span style="background: var(--accent-light); color: var(--accent); padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                        <?php echo htmlspecialchars($book['category'] ?: 'Umum'); ?>
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="rating-grid">
                <div class="summary-card">
                    <div class="avg-number"><?php echo $avg_rating; ?></div>
                    <div class="stars-display">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <iconify-icon icon="<?php echo $i <= round($avg_rating) ? 'mdi:star' : 'mdi:star-outline'; ?>"></iconify-icon>
                        <?php endfor; ?>
                    </div>
                    <div style="color: var(--text-gray); font-size: 14px;"><?php echo $total_reviews; ?> Ulasan</div>
                </div>

                <div class="bars-card">
                    <?php 
                    $rs = [5 => $summary['r5'], 4 => $summary['r4'], 3 => $summary['r3'], 2 => $summary['r2'], 1 => $summary['r1']];
                    foreach($rs as $s => $c): 
                        $p = $total_reviews > 0 ? ($c / $total_reviews) * 100 : 0;
                    ?>
                        <div class="rating-bar-item">
                            <span style="font-size: 13px; font-weight: 700; width: 15px;"><?php echo $s; ?></span>
                            <div class="bar-bg"><div class="bar-fill" style="width: <?php echo $p; ?>%"></div></div>
                            <span style="font-size: 12px; color: var(--text-muted); width: 25px; text-align: right;"><?php echo (int)$c; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- FORM SELECTION - RADIO BASED -->
            <div class="star-rating-form">
                <span class="rating-help-text">Klik bintang untuk memberi rating</span>
                <form id="ratingForm" action="api/submit-rating.php" method="POST">
                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                    
                    <div class="star-group">
                        <input type="radio" id="st5" name="rating" value="5">
                        <label for="st5" title="Sangat Bagus">★</label>
                        
                        <input type="radio" id="st4" name="rating" value="4">
                        <label for="st4" title="Bagus">★</label>
                        
                        <input type="radio" id="st3" name="rating" value="3">
                        <label for="st3" title="Cukup">★</label>
                        
                        <input type="radio" id="st2" name="rating" value="2">
                        <label for="st2" title="Buruk">★</label>
                        
                        <input type="radio" id="st1" name="rating" value="1">
                        <label for="st1" title="Sangat Buruk">★</label>
                    </div>

                    <textarea name="comment" class="comment-textarea" placeholder="Apa pendapatmu tentang buku ini? Berikan ulasan jujurmu..." required></textarea>
                    
                    <div style="text-align: right;">
                        <button type="submit" class="btn-submit-rating">
                            <iconify-icon icon="mdi:send-variant"></iconify-icon> Kirim Ulasan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Comments List -->
            <div class="comments-section" style="margin-top: 60px;">
                <h3 style="margin-bottom: 30px; display: flex; align-items: center; gap: 12px; font-size: 20px;">
                    <iconify-icon icon="mdi:comment-text-multiple-outline" color="var(--primary)"></iconify-icon>
                    Semua Ulasan
                </h3>
                <?php if (empty($comments)): ?>
                    <div style="text-align: center; padding: 40px; background: var(--card); border-radius: 20px; border: 1px dashed var(--border);">
                        <iconify-icon icon="mdi:comment-off-outline" width="48" color="var(--border)"></iconify-icon>
                        <p style="margin-top: 10px; color: var(--text-muted);">Belum ada ulasan untuk buku ini.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $cm): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <div class="user-avatar">
                                    <?php if (!empty($cm['foto']) && file_exists(__DIR__ . '/' . $cm['foto'])): ?>
                                        <img src="/perpustakaan-online/public/<?php echo htmlspecialchars($cm['foto']); ?>" alt="User">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($cm['name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 15px; color: var(--text);"><?php echo htmlspecialchars($cm['name']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo date('d M Y', strtotime($cm['created_at'])); ?></div>
                                </div>
                                <div class="comment-rating">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <iconify-icon icon="<?php echo $i <= $cm['rating'] ? 'mdi:star' : 'mdi:star-outline'; ?>"></iconify-icon>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div style="font-size: 14px; color: var(--text); line-height: 1.7;"><?php echo nl2br(htmlspecialchars($cm['komentar'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/book-rating-manage.js"></script>
</body>
</html>
