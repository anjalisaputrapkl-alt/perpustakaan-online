<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all members for this school
$stmt = $pdo->prepare(
    'SELECT m.*, 
            (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND status != "returned") as active_borrows
     FROM members m
     WHERE m.school_id = :sid
     ORDER BY m.name ASC'
);
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll();

// Get school info
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$stmt->execute(['sid' => $sid]);
$school = $stmt->fetch();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Siswa - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        /* Layout Structure */
        body {
            margin: 0;
            padding: 0;
        }

        .app {
            margin-left: 240px;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            min-height: 100vh;
        }

        .topbar {
            padding: 20px 24px;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            grid-column: 1;
        }

        .topbar strong {
            font-size: 20px;
            font-weight: 700;
        }

        .content {
            grid-column: 1;
            padding: 0;
            margin: 0;
            display: block;
        }

        .main {
            padding: 24px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Page Header */
        .page-header {
            margin: 0;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .page-subtitle {
            font-size: 14px;
            margin: 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 0;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            flex-shrink: 0;
        }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            font-size: 12px;
            margin: 0 0 4px 0;
        }

        .stat-value {
            font-size: 28px;
            margin: 0;
            line-height: 1;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0;
            margin: 0;
            background: transparent;
            border: none;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
        }

        .toolbar-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: var(--text-muted);
            color: white;
        }

        /* Students Grid */
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin: 0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
            grid-column: 1 / -1;
        }

        .empty-icon {
            font-size: 48px;
            opacity: 0.4;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .empty-text {
            font-size: 14px;
            margin: 0;
        }

        /* Barcode-specific custom styles */
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }

        .student-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .student-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .student-card:hover::before {
            opacity: 1;
        }

        .student-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 1;
        }

        .badge-active {
            background: var(--success-light);
            color: #065F46;
        }

        .badge-inactive {
            background: var(--danger-light);
            color: #991B1B;
        }

        .student-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(58, 127, 242, 0.3);
        }

        .student-info-header {
            flex: 1;
            min-width: 0;
        }

        .student-name {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 4px 0;
            line-height: 1.3;
            word-break: break-word;
        }

        .student-nisn {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0;
        }

        .barcode-section {
            background: white;
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            text-align: center;
        }

        .barcode-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 70px;
        }

        .barcode-display {
            max-width: 100%;
            height: auto;
            max-height: 60px;
        }

        .barcode-id {
            margin-top: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            font-family: 'Courier New', monospace;
        }

        .student-details {
            margin: 16px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-value {
            font-weight: 600;
            color: var(--text);
        }

        .detail-icon {
            font-size: 14px;
            color: var(--primary);
        }

        .student-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .btn-action {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-print {
            background: var(--primary);
            color: white;
        }

        .btn-print:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-edit {
            background: transparent;
            color: var(--text);
            border: 1.5px solid var(--border);
        }

        .btn-edit:hover {
            border-color: var(--text-muted);
            background: var(--bg);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .students-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .app {
                margin-left: 0;
            }

            .main {
                padding: 16px;
            }

            .topbar {
                padding: 16px;
            }

            .topbar strong {
                font-size: 18px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 12px;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 12px;
            }

            .stat-icon {
                font-size: 24px !important;
            }

            .stat-value {
                font-size: 24px;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

            .toolbar-actions {
                width: 100%;
            }

            .toolbar-actions .btn {
                flex: 1;
            }

            .students-grid {
                grid-template-columns: 1fr;
            }

            .student-badge {
                position: static;
                margin-bottom: 8px;
                width: fit-content;
            }

            .student-header {
                flex-wrap: wrap;
            }

            .page-title {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .main {
                padding: 12px;
                gap: 16px;
            }

            .topbar {
                padding: 12px 16px;
            }

            .page-title {
                font-size: 20px;
            }

            .stat-value {
                font-size: 20px;
            }

            .student-name {
                font-size: 14px;
            }

            .btn-action {
                font-size: 11px;
                padding: 8px 10px;
            }
        }

        /* Print Styles */
        @media print {

            .toolbar,
            .student-actions {
                display: none !important;
            }

            .students-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .student-card {
                page-break-inside: avoid;
                border: 2px solid #000;
                box-shadow: none;
            }

            .student-card:hover {
                transform: none;
            }

            .barcode-section {
                border: 1px solid #000;
                background: white;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">

        <div class="topbar">
            <strong>Barcode Siswa</strong>
        </div>

        <div class="content">
            <div class="main">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Barcode Siswa</h1>
                    <p class="page-subtitle"><?= htmlspecialchars($school['name'] ?? 'Sekolah') ?></p>
                </div>

                <!-- Stats Section -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <iconify-icon icon="mdi:account-group"></iconify-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Siswa</div>
                            <div class="stat-value"><?= count($members) ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <iconify-icon icon="mdi:account-check"></iconify-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Siswa Aktif</div>
                            <div class="stat-value">
                                <?= count(array_filter($members, fn($m) => $m['status'] === 'active')) ?>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Peminjam Aktif</div>
                            <div class="stat-value">
                                <?= count(array_filter($members, fn($m) => $m['active_borrows'] > 0)) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                        <input type="text" id="searchInput" class="search-input"
                            placeholder="Cari nama atau NISN siswa..." onkeyup="filterStudents()">
                    </div>
                    <div class="toolbar-actions">
                        <button onclick="window.print()" class="btn btn-secondary">
                            <iconify-icon icon="mdi:printer"></iconify-icon>
                            Cetak Semua
                        </button>
                    </div>
                </div>

                <!-- Students Grid -->
                <?php if (empty($members)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <iconify-icon icon="mdi:account-group-outline"></iconify-icon>
                        </div>
                        <h3 class="empty-title">Tidak Ada Siswa</h3>
                        <p class="empty-text">Belum ada siswa yang terdaftar di sekolah ini</p>
                    </div>
                <?php else: ?>
                    <div class="students-grid" id="studentsGrid">
                        <?php foreach ($members as $member): ?>
                            <div class="student-card search-item"
                                data-search="<?= strtolower($member['name'] . ' ' . $member['nisn']) ?>">
                                <span
                                    class="student-badge <?= $member['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <iconify-icon
                                        icon="<?= $member['status'] === 'active' ? 'mdi:check-circle' : 'mdi:close-circle' ?>"></iconify-icon>
                                    <?= $member['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                                </span>

                                <div class="student-header">
                                    <div class="student-avatar">
                                        <?= strtoupper(mb_substr($member['name'], 0, 1)) ?>
                                    </div>
                                    <div class="student-info-header">
                                        <h3 class="student-name"><?= htmlspecialchars($member['name']) ?></h3>
                                        <div class="student-nisn">NISN: <?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                                    </div>
                                </div>

                                <div class="barcode-section">
                                    <div class="barcode-wrapper">
                                        <object data="api/generate-student-barcode.php?member_id=<?= $member['id'] ?>"
                                            type="image/svg+xml" class="barcode-display"></object>
                                    </div>
                                    <div class="barcode-id">ID: <?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                </div>

                                <div class="student-details">
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <iconify-icon icon="mdi:identifier" class="detail-icon"></iconify-icon>
                                            Member ID
                                        </span>
                                        <span class="detail-value"><?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <iconify-icon icon="mdi:book-multiple" class="detail-icon"></iconify-icon>
                                            Peminjaman Aktif
                                        </span>
                                        <span class="detail-value"><?= $member['active_borrows'] ?> buku</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <iconify-icon icon="mdi:calendar-check" class="detail-icon"></iconify-icon>
                                            Bergabung
                                        </span>
                                        <span class="detail-value"><?= date('d/m/Y', strtotime($member['created_at'])) ?></span>
                                    </div>
                                </div>

                                <div class="student-actions">
                                    <button
                                        onclick="printBarcode(<?= $member['id'] ?>, '<?= htmlspecialchars($member['name']) ?>')"
                                        class="btn-action btn-print">
                                        <iconify-icon icon="mdi:printer"></iconify-icon>
                                        Cetak
                                    </button>
                                    <a href="members.php?action=edit&id=<?= $member['id'] ?>" class="btn-action btn-edit">
                                        <iconify-icon icon="mdi:pencil"></iconify-icon>
                                        Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterStudents() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.search-item');
            let visibleCount = 0;

            items.forEach(item => {
                const searchText = item.getAttribute('data-search');
                if (searchText.includes(input)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Optional: Show a message if no results found
            // You can add this functionality if needed
        }

        function printBarcode(memberId, memberName) {
            const win = window.open(`api/generate-student-barcode.php?member_id=${memberId}`, '_blank');
            if (win) {
                win.addEventListener('load', function () {
                    setTimeout(() => {
                        win.print();
                    }, 250);
                });
            }
        }

        // Add keyboard shortcut for search
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('searchInput').addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    filterStudents();
                }
            });

            // Focus search on Ctrl/Cmd + K
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                }
            });
        });
    </script>
</body>

</html>