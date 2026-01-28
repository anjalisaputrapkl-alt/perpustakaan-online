<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$is_authenticated = !empty($_SESSION['user']);

if ($is_authenticated) {
    $pdo = require __DIR__ . '/../src/db.php';
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];

    // Recent borrows
    $stmt = $pdo->prepare("SELECT b.title, m.name, br.borrowed_at as timestamp, 'borrow' as type FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        JOIN members m ON br.member_id = m.id 
        WHERE br.school_id = :sid AND br.returned_at IS NULL 
        ORDER BY br.borrowed_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $recent_borrows = $stmt->fetchAll();

    // Recent returns
    $stmt = $pdo->prepare("SELECT b.title, m.name, br.returned_at as timestamp, 'return' as type FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        JOIN members m ON br.member_id = m.id 
        WHERE br.school_id = :sid AND br.returned_at IS NOT NULL 
        ORDER BY br.returned_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $recent_returns = $stmt->fetchAll();

    // New members
    $stmt = $pdo->prepare("SELECT name as title, '' as name, created_at as timestamp, 'member' as type FROM members 
        WHERE school_id = :sid 
        ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $new_members = $stmt->fetchAll();

    // New books
    $stmt = $pdo->prepare("SELECT title, '' as name, created_at as timestamp, 'book' as type FROM books 
        WHERE school_id = :sid 
        ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $new_books = $stmt->fetchAll();

    // Merge and sort all activities
    $all_activities = array_merge($recent_borrows, $recent_returns, $new_members, $new_books);
    usort($all_activities, function ($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Perpustakaan</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/index.css">
</head>

<body>

    <?php if ($is_authenticated): ?>
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <div class="app">

            <div class="topbar">
                <strong>Dashboard Perpustakaan</strong>
            </div>

            <div class="content">

                <div class="main">

                    <div class="stats">
                        <div class="stat" data-stat-type="books" data-tooltip="Total seluruh buku yang sudah terdaftar di perpustakaan">
                            <small>Total Buku</small><strong class="stat-value" id="stat-books">-</strong>
                        </div>
                        <div class="stat" data-stat-type="members" data-tooltip="Total seluruh anggota perpustakaan yang terdaftar">
                            <small>Total Anggota</small><strong class="stat-value" id="stat-members">-</strong>
                        </div>
                        <div class="stat" data-stat-type="borrowed" data-tooltip="Total buku yang sedang dipinjam oleh anggota">
                            <small>Dipinjam</small><strong class="stat-value" id="stat-borrowed">-</strong>
                        </div>
                        <div class="stat alert" data-stat-type="overdue" data-tooltip="Total peminjaman yang sudah melewati batas waktu pengembalian">
                            <small>Terlambat</small><strong class="stat-value" id="stat-overdue">-</strong>
                        </div>
                    </div>

                    <div class="charts">
                        <div class="chart-box">
                            <h2>Peminjaman per Bulan</h2>
                            <canvas id="borrowChart" width="400" height="200"></canvas>
                        </div>
                        <div class="chart-box">
                            <h2>Status Buku</h2>
                            <canvas id="statusChart" width="400" height="300"></canvas>
                        </div>
                    </div>

                    <div class="activity-section">
                        <h2><iconify-icon icon="mdi:clipboard-list"
                                style="vertical-align: middle; margin-right: 8px;"></iconify-icon>Aktivitas Terbaru</h2>

                        <div class="activity-tabs">
                            <button class="activity-tab active btn-sm" data-tab="all"><iconify-icon
                                    icon="mdi:shuffle-variant" style="vertical-align: middle;"></iconify-icon>
                                Semua</button>
                            <button class="activity-tab btn-sm" data-tab="borrows"><iconify-icon icon="mdi:book-open"
                                    style="vertical-align: middle;"></iconify-icon> Peminjaman</button>
                            <button class="activity-tab btn-sm" data-tab="returns"><iconify-icon icon="mdi:inbox"
                                    style="vertical-align: middle;"></iconify-icon> Pengembalian</button>
                            <button class="activity-tab btn-sm" data-tab="members"><iconify-icon icon="mdi:account-multiple"
                                    style="vertical-align: middle;"></iconify-icon> Anggota Baru</button>
                            <button class="activity-tab btn-sm" data-tab="books"><iconify-icon icon="mdi:library"
                                    style="vertical-align: middle;"></iconify-icon> Buku Baru</button>
                        </div>

                        <!-- All Activities Tab -->
                        <div class="activity-content active" id="all-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($all_activities)): ?>
                                        <?php foreach ($all_activities as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <?php
                                                        switch ($activity['type']) {
                                                            case 'borrow':
                                                                echo '<iconify-icon icon="mdi:book-open" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Dipinjam oleh ' . htmlspecialchars($activity['name']);
                                                                break;
                                                            case 'return':
                                                                echo '<iconify-icon icon="mdi:inbox" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Dikembalikan oleh ' . htmlspecialchars($activity['name']);
                                                                break;
                                                            case 'member':
                                                                echo '<iconify-icon icon="mdi:account-multiple" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Anggota baru terdaftar';
                                                                break;
                                                            case 'book':
                                                                echo '<iconify-icon icon="mdi:library" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Buku baru ditambahkan';
                                                                break;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada aktivitas terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Borrows Tab -->
                        <div class="activity-content" id="borrows-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($recent_borrows)): ?>
                                        <?php foreach ($recent_borrows as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <iconify-icon icon="mdi:book-open"
                                                            style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Dipinjam
                                                        oleh
                                                        <?= htmlspecialchars($activity['name']) ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada peminjaman terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Returns Tab -->
                        <div class="activity-content" id="returns-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($recent_returns)): ?>
                                        <?php foreach ($recent_returns as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <iconify-icon icon="mdi:inbox"
                                                            style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Dikembalikan
                                                        oleh
                                                        <?= htmlspecialchars($activity['name']) ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada pengembalian terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Members Tab -->
                        <div class="activity-content" id="members-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($new_members)): ?>
                                        <?php foreach ($new_members as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <iconify-icon icon="mdi:account-multiple"
                                                            style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Anggota
                                                        baru terdaftar
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada anggota baru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Books Tab -->
                        <div class="activity-content" id="books-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($new_books)): ?>
                                        <?php foreach ($new_books as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <iconify-icon icon="mdi:library"
                                                            style="vertical-align: middle; margin-right: 4px;"></iconify-icon>Buku
                                                        baru ditambahkan
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada buku baru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <script src="../assets/js/stats-modal.js"></script>
        <script src="../assets/js/index.js"></script>

        <!-- Modal untuk Stats -->
        <div class="modal-overlay" id="statsModal">
            <div class="modal-container">
                <div class="modal-header">
                    <h2>Detail Data</h2>
                    <button class="modal-close" type="button">×</button>
                </div>
                <div class="modal-body">
                    <div class="modal-loading">Memuat data...</div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                console.log("📌 DOM Loaded → Load Dashboard Stats");
                initLoadDashboardStats();
                // Also initialize modal manager
                if (typeof modalManager !== 'undefined') {
                    modalManager.init();
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>