<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
$siswaId = (int)$_SESSION['user']['school_id'];

// Get student profile
$stmt = $pdo->prepare("
    SELECT 
        id_siswa, nama_lengkap, nis, nisn, kelas, jurusan,
        tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto,
        created_at, updated_at
    FROM siswa
    WHERE id_siswa = ?
");
$stmt->execute([$siswaId]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    die("Profil siswa tidak ditemukan.");
}

// Format dates
$tanggalLahir = !empty($siswa['tanggal_lahir']) ? date('d M Y', strtotime($siswa['tanggal_lahir'])) : '-';
$createdAt = !empty($siswa['created_at']) ? date('d M Y, H:i', strtotime($siswa['created_at'])) : '-';
$updatedAt = !empty($siswa['updated_at']) ? date('d M Y, H:i', strtotime($siswa['updated_at'])) : '-';

// Gender display
$genderDisplay = match($siswa['jenis_kelamin']) {
    'L', 'M' => 'Laki-laki',
    'P', 'F' => 'Perempuan',
    default => '-'
};

// Photo
$photoUrl = !empty($siswa['foto']) && file_exists(__DIR__ . str_replace('/perpustakaan-online/public', '', $siswa['foto']))
    ? $siswa['foto']
    : '/perpustakaan-online/assets/img/default-avatar.png';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya</title>
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

        /* Card */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: fadeInUp 0.6s ease-out;
        }

        .profile-header {
            display: flex;
            gap: 24px;
            align-items: flex-start;
            margin-bottom: 32px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }

        .profile-info h2 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
        }

        .profile-info p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .divider {
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-label {
            display: block;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
        }

        .meta-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
            color: var(--muted);
        }

        .meta-item strong {
            color: var(--text);
            font-weight: 600;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
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

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-left-color: var(--success);
        }

        .alert-error {
            background-color: rgba(220, 38, 38, 0.1);
            color: #7f1d1d;
            border-left-color: var(--danger);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <div class="header">
        <div class="header-container">
            <h1>Profil Saya</h1>
        </div>
    </div>

    <div class="container-main">
        <div class="card">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto" class="profile-photo">
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h2>
                        <p><?php echo htmlspecialchars($siswa['kelas']); ?> - <?php echo htmlspecialchars($siswa['jurusan']); ?></p>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nama Lengkap</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">NIS</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['nis']); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">NISN</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['nisn']); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Kelas</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['kelas']); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Jurusan</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['jurusan']); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Jenis Kelamin</span>
                        <div class="info-value"><?php echo htmlspecialchars($genderDisplay); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Tanggal Lahir</span>
                        <div class="info-value"><?php echo htmlspecialchars($tanggalLahir); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['email']); ?></div>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Nomor HP</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['no_hp']); ?></div>
                    </div>

                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Alamat</span>
                        <div class="info-value"><?php echo htmlspecialchars($siswa['alamat']); ?></div>
                    </div>
                </div>

                <div class="meta-section">
                    <div class="meta-item">
                        <strong>Dibuat:</strong>
                        <span><?php echo htmlspecialchars($createdAt); ?></span>
                    </div>
                    <div class="meta-item">
                        <strong>Terakhir diperbarui:</strong>
                        <span><?php echo htmlspecialchars($updatedAt); ?></span>
                    </div>
                </div>

                <div class="button-group">
                    <a href="profil-edit.php" class="btn primary">Edit Profil</a>
                    <a href="upload-foto.php" class="btn primary">Ganti Foto</a>
                    <a href="kartu-siswa.php" class="btn primary">Kartu Siswa</a>
                    <a href="student-dashboard.php" class="btn secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
