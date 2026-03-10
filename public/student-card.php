<?php
require __DIR__ . '/../src/auth.php';

// Initialize database
try {
    $pdo = require __DIR__ . '/../src/db.php';
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
}

$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isPreviewMode = isset($_GET['preview']) && $_GET['preview'] === '1' && $isLocalhost;

$member = null;
$user = $_SESSION['user'] ?? null;

if ($user && !empty($user['id'])) {
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare(
                'SELECT u.id, u.name, u.nisn, u.school_id,
                        s.student_uuid AS student_uuid, s.foto AS foto, s.kelas, s.jurusan,
                        sch.name AS school_name, sch.address AS location, sch.logo AS school_logo
                 FROM users u
                 LEFT JOIN siswa s ON s.id_siswa = u.id
                 LEFT JOIN schools sch ON u.school_id = sch.id
                 WHERE u.id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => (int)$user['id']]);
            $dbData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dbData) {
                $member = $dbData;
            } else {
                error_log("No anggota record found for user ID: " . $user['id']);
                $member = $user;
            }
        } catch (Exception $e) {
            error_log("Database query error in student-card: " . $e->getMessage());
            $member = $user;
        }
    } else {
        error_log("Database not available in student-card.php");
        $member = $user;
    }
}

if (!$member && $isPreviewMode && isset($pdo)) {
    try {
            $stmt = $pdo->query(
            'SELECT u.id, u.name, u.nisn, u.school_id,
                s.student_uuid AS student_uuid, s.foto AS foto, s.kelas, s.jurusan,
                sch.name AS school_name, sch.address AS location, sch.logo AS school_logo
             FROM users u
             LEFT JOIN siswa s ON s.id_siswa = u.id
             LEFT JOIN schools sch ON u.school_id = sch.id
             ORDER BY u.id ASC LIMIT 1'
        );
        if ($stmt) {
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($member) {
                $_SESSION['user'] = ['id' => $member['id'], 'school_id' => $member['school_id'] ?? null, 'name' => $member['name'], 'nisn' => $member['nisn'] ?? null]; // ID Anggota
            }
        }
    } catch (Exception $e) {
        error_log("Preview mode query error: " . $e->getMessage());
    }
}

if (!$member) {
    header('Location: index.php', true, 302);
    exit;
}

$photoSrc = '../assets/images/default-avatar.svg';

if (!empty($member['foto'])) {
    $photoPath = trim($member['foto']);
    
    if (strpos($photoPath, 'http') === 0) {
        $photoSrc = htmlspecialchars($photoPath);
    } elseif (strpos($photoPath, '/perpustakaan-online/public/uploads/') === 0) {
        $relativePath = str_replace('/perpustakaan-online/public/', './', $photoPath);
        $photoSrc = htmlspecialchars($relativePath);
    } elseif (strpos($photoPath, '/public/uploads/') === 0) {
        $relativePath = str_replace('/public/', './', $photoPath);
        $photoSrc = htmlspecialchars($relativePath);
    } elseif (strpos($photoPath, '/uploads/') === 0) {
        $photoSrc = htmlspecialchars('.' . $photoPath);
    } elseif (strpos($photoPath, 'uploads/') === 0) {
        $photoSrc = htmlspecialchars('./' . $photoPath);
    } elseif (strpos($photoPath, '../') === 0) {
        $photoSrc = htmlspecialchars($photoPath);
    } else {
        $photoSrc = htmlspecialchars('../' . $photoPath);
    }

    $useDefault = false;
    
    if (strpos($photoSrc, 'http') !== 0) {
        $cleanPath = str_replace(['./', '../'], '', $photoSrc);

        if (strpos($cleanPath, 'assets/') !== false) {
             $checkPath = __DIR__ . '/' . $cleanPath;
             if (!file_exists($checkPath)) {
                 $useDefault = true;
             }
        } 
        elseif (strpos($cleanPath, 'uploads/') !== false) {
             $checkPath = __DIR__ . '/' . $cleanPath;
        }
    }

    if ($useDefault) {
        $photoSrc = '../assets/images/default-avatar.svg';
    }
}

$separator = (strpos($photoSrc, '?') !== false) ? '&' : '?';
$photoSrc .= $separator . 'v=' . bin2hex(random_bytes(4));

$barcodeValue = trim($member['nisn'] ?? $member['student_uuid'] ?? $member['id'] ?? '');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Perpustakaan - <?= htmlspecialchars($member['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <!-- JsBarcode for client-side barcode generation -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <!-- Load theme from database (runs first) -->
    <script src="../assets/js/db-theme-loader.js"></script>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="stylesheet" href="../assets/css/student-card-style.css">
</head>

<body>
    <div class="container">
        <a href="profil.php" class="nav-back">
            <iconify-icon icon="mdi:arrow-left"></iconify-icon>
            Kembali ke Profil
        </a>

        <div class="page-header">
            <h1>Kartu Perpustakaan</h1>
            <p>Gunakan kartu ini untuk meminjam buku</p>
        </div>

        <!-- CARD -->
        <div class="library-card" id="printableCard">
            <div class="card-header">
                <div class="school-logo-frame">
                   <?php if (!empty($member['school_logo'])): ?>
                        <img src="<?= htmlspecialchars($member['school_logo']) ?>" alt="Logo">
                    <?php else: ?>
                        <iconify-icon icon="mdi:school" style="color:var(--primary); font-size:24px;"></iconify-icon>
                    <?php endif; ?>
                </div>
                <div class="school-info">
                    <h2><?= htmlspecialchars($member['school_name'] ?? 'Perpustakaan Digital') ?></h2>
                    <p><?= htmlspecialchars($member['location'] ?? 'Kartu Anggota Resmi') ?></p>
                </div>
            </div>

            <div class="card-body">
                <!-- Profile Section -->
                <div class="student-profile">
                    <img src="<?= $photoSrc ?>" alt="Foto" class="student-photo">
                    <div class="student-details">
                        <div class="student-name"><?= htmlspecialchars($member['name']) ?></div>
                        <div class="student-id-label">Nomor Anggota</div>
                        <div class="student-id"><?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                    </div>
                </div>

                <!-- Barcode Section -->
                <div class="barcode-section">
                    <svg id="barcode"></svg>
                </div>
            </div>

            <div class="card-footer-strip">
                Berlaku selama menjadi anggota aktif di sekolah ini.
            </div>
        </div>

        <div class="actions">
            <!-- Download / Print -->
            <button onclick="printCard()" class="btn btn-primary">
                <iconify-icon icon="mdi:printer"></iconify-icon>
                Cetak Kartu
            </button>
            
            <a href="profil.php" class="btn btn-secondary">
                <iconify-icon icon="mdi:account-edit"></iconify-icon>
                Edit Profil
            </a>
        </div>
    </div>

    <!-- Initialize Barcode -->
    <script src="../assets/js/student-card-manage.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            initCardBarcode("<?= htmlspecialchars($barcodeValue) ?>");
        });
    </script>
</body>
</html>
