<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
$siswaId = (int)$_SESSION['user']['school_id'];

// Get student profile
$stmt = $pdo->prepare("
    SELECT 
        id_siswa, nama_lengkap, nis, nisn, kelas, jurusan, foto
    FROM siswa
    WHERE id_siswa = ?
");
$stmt->execute([$siswaId]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    die("Profil siswa tidak ditemukan.");
}

// Photo
$photoUrl = !empty($siswa['foto']) && file_exists(__DIR__ . str_replace('/perpustakaan-online/public', '', $siswa['foto']))
    ? $siswa['foto']
    : '/perpustakaan-online/assets/img/default-avatar.png';

// QR Code data
$qrData = 'ID:' . $siswa['id_siswa'] . '|NISN:' . $siswa['nisn'] . '|Nama:' . $siswa['nama_lengkap'];
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f1724;
            --muted: #6b7280;
            --accent: #0b3d61;
            --accent-light: #e0f2fe;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Navigation Sidebar */
        .nav-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 240px;
            background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%);
            color: white;
            padding: 24px 0;
            z-index: 1002;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }

        .nav-sidebar-header {
            padding: 0 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .nav-sidebar-header-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        .nav-sidebar-header-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: white;
        }

        .nav-sidebar-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .nav-sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-sidebar-menu li {
            margin: 0;
        }

        .nav-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .nav-sidebar-menu iconify-icon {
            font-size: 18px;
            width: 24px;
            height: 24px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-sidebar-menu a:hover iconify-icon,
        .nav-sidebar-menu a.active iconify-icon {
            color: white;
        }

        .nav-sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 16px 0;
        }

        /* Header */
        .header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: slideDown 0.6s ease-out;
            margin-left: 240px;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        /* Container */
        .container-main {
            margin-left: 240px;
            padding: 24px;
            max-width: 1400px;
        }

        /* Content */
        .content {
            text-align: center;
        }

        .card-container {
            display: flex;
            gap: 32px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
            animation: fadeInUp 0.6s ease-out;
        }

        .card-wrapper {
            perspective: 1000px;
            width: 400px;
            height: 250px;
        }

        .card {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            font-weight: 500;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            background: linear-gradient(135deg, #0b3d61 0%, #062d4a 100%);
            border: none !important;
        }

        .card.back {
            background: linear-gradient(135deg, #062d4a 0%, #041824 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .card-header {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-content {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .card-photo {
            width: 80px;
            height: 100px;
            border-radius: 4px;
            border: 3px solid white;
            object-fit: cover;
        }

        .card-info {
            flex: 1;
        }

        .card-name {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 4px 0;
            word-break: break-word;
        }

        .card-detail {
            font-size: 11px;
            margin: 2px 0;
            opacity: 0.95;
        }

        .card-footer {
            font-size: 10px;
            text-align: right;
            opacity: 0.8;
        }

        .qr-code {
            width: 150px;
            height: 150px;
            border: 3px solid white;
            border-radius: 8px;
            padding: 4px;
            background: white;
        }

        .qr-label {
            font-size: 12px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn.primary {
            background: var(--accent);
            color: white;
        }

        .btn.primary:hover {
            background: #062d4a;
            transform: translateY(-2px);
        }

        .btn.secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn.secondary:hover {
            background: #d1d5db;
        }

        @media (max-width: 768px) {
            .card-container {
                flex-direction: column;
                align-items: center;
            }

            .card-wrapper {
                width: 100%;
                max-width: 350px;
            }

            .card {
                height: 220px;
            }
        }

        @media print {
            .header {
                display: none !important;
            }

            .actions {
                display: none !important;
            }

            .container-main {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .card-container {
                gap: 20px;
            }

            .card-wrapper {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <div class="header">
        <div class="header-container">
            <h1>Kartu Siswa Digital</h1>
        </div>
    </div>

    <div class="container-main">
        <div class="content">
            <div class="card-container">
                <!-- Front Card -->
                <div class="card-wrapper">
                    <div class="card">
                        <div class="card-header">Perpustakaan Digital</div>
                        <div class="card-content">
                            <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto" class="card-photo">
                            <div class="card-info">
                                <p class="card-name"><?php echo htmlspecialchars(substr($siswa['nama_lengkap'], 0, 25)); ?></p>
                                <p class="card-detail">NIS: <?php echo htmlspecialchars($siswa['nis']); ?></p>
                                <p class="card-detail">Kelas: <?php echo htmlspecialchars($siswa['kelas']); ?></p>
                                <p class="card-detail">Jurusan: <?php echo htmlspecialchars($siswa['jurusan']); ?></p>
                            </div>
                        </div>
                        <div class="card-footer">Berlaku seumur hidup</div>
                    </div>
                </div>

                <!-- Back Card -->
                <div class="card-wrapper">
                    <div class="card back">
                        <div class="qr-label">Kode Verifikasi</div>
                        <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code" class="qr-code">
                    </div>
                </div>
            </div>

            <div class="actions">
                <button class="btn primary" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak / Unduh PDF
                </button>
                <a href="profil.php" class="btn secondary">Kembali ke Profil</a>
            </div>
        </div>
    </div>
</body>
</html>
