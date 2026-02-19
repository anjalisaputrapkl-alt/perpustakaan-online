<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';

$siswaId = (int) $_SESSION['user']['school_id'];

// Get student profile
$stmt = $pdo->prepare("
    SELECT 
        id_siswa, nama_lengkap, nis, nisn, kelas, jurusan,
        tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto
    FROM siswa
    WHERE id_siswa = ?
");
$stmt->execute([$siswaId]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    die("Profil tidak ditemukan.");
}

$message = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');

    // Validasi
    if (empty($nama_lengkap)) {
        $errorMessage = "Nama lengkap tidak boleh kosong.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Email tidak valid.";
    } elseif (!empty($no_hp) && !preg_match('/^[0-9\-\+\s]{7,20}$/', $no_hp)) {
        $errorMessage = "Nomor HP tidak valid (hanya angka, -, +).";
    } else {
        // Update ke database dengan prepared statement
        try {
            $updateStmt = $pdo->prepare("
                UPDATE siswa 
                SET nama_lengkap=?, email=?, no_hp=?, alamat=?, tanggal_lahir=?, jenis_kelamin=?, updated_at=NOW()
                WHERE id_siswa=?
            ");
            $updateStmt->execute([
                $nama_lengkap,
                !empty($email) ? $email : null,
                !empty($no_hp) ? $no_hp : null,
                !empty($alamat) ? $alamat : null,
                !empty($tanggal_lahir) ? $tanggal_lahir : null,
                !empty($jenis_kelamin) ? $jenis_kelamin : null,
                $siswaId
            ]);

            $message = "Profil berhasil diperbarui!";

            // Refresh data dari database
            $stmt->execute([$siswaId]);
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
}

// Get member_id dengan auto-create jika belum ada
$memberHelper = new MemberHelper($pdo);
$userData = $_SESSION['user'];
$member_id = $memberHelper->getMemberId($userData);

// Get damage fines for this member
$schoolId = $userData['school_id'];
$damageController = new DamageController($pdo, $schoolId);
$memberDamageFines = $damageController->getByMember($member_id);
$totalMemberDenda = 0;
$pendingMemberDenda = 0;
foreach ($memberDamageFines as $fine) {
    $totalMemberDenda += $fine['fine_amount'];
    if ($fine['status'] === 'pending') {
        $pendingMemberDenda += $fine['fine_amount'];
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/profil-edit-style.css">
</head>

<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <div class="header">
        <div class="header-container">
            <h1>Edit Profil</h1>
        </div>
    </div>

    <div class="container-main">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Total Denda Section -->
        <div
            style="animation: fadeInSlideUp 0.4s ease-out; margin-bottom: 24px; padding: 16px; background-color: <?php echo $pendingMemberDenda > 0 ? 'rgba(239, 68, 68, 0.05)' : 'rgba(16, 185, 129, 0.05)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? '#ef4444' : '#10b981'; ?>; display: flex; align-items: center; gap: 16px;">
            <div style="font-size: 24px; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                <iconify-icon icon="<?php echo $pendingMemberDenda > 0 ? 'mdi:alert-circle' : 'mdi:check-circle'; ?>"
                    width="24" height="24"></iconify-icon>
            </div>
            <div style="flex: 1;">
                <div
                    style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                    Denda Tertunda</div>
                <div
                    style="font-size: 24px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                    Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                <?php if ($pendingMemberDenda > 0): ?>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 4px 0 0 0; line-height: 1.5;">Denda dari
                        kerusakan buku saat peminjaman. Hubungi admin untuk detail lebih lanjut.</p>
                <?php else: ?>
                    <p style="font-size: 12px; color: #10b981; margin: 4px 0 0 0;">✓ Tidak ada denda tertunda</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>Ubah Informasi Profil</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required
                        value="<?php echo htmlspecialchars($siswa['nama_lengkap']); ?>">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($siswa['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="tel" name="no_hp" value="<?php echo htmlspecialchars($siswa['no_hp'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir"
                        value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?php echo $siswa['jenis_kelamin'] === 'L' ? 'selected' : ''; ?>>Laki-laki
                        </option>
                        <option value="P" <?php echo $siswa['jenis_kelamin'] === 'P' ? 'selected' : ''; ?>>Perempuan
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" rows="4"><?php echo htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="actions" style="margin-top: 24px;">
                    <button type="submit" class="btn primary">Simpan Perubahan</button>
                    <a href="upload-foto.php" class="btn primary">Ganti Foto</a>
                    <a href="profil.php" class="btn secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>