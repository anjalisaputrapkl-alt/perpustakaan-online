<?php
/**
 * Global Student Header Component
 * 
 * Menampilkan header dengan brand, user info, dan logout button
 * Digunakan di semua halaman siswa
 * 
 * Requirements:
 * - $_SESSION['user'] harus sudah di-set sebelumnya
 * - $pageTitle variable bisa di-set sebelum include untuk mengubah subtitle
 * 
 * Usage:
 * $pageTitle = 'Riwayat Peminjaman';
 * include 'partials/student-header.php';
 */

if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$pageTitle = $pageTitle ?? 'Dashboard Anggota';

// Special Theme Check
$specialTheme = null;
if (isset($user['school_id'])) {
    require_once __DIR__ . '/../../src/ThemeModel.php';
    $themeModel = new ThemeModel($pdo ?? (require __DIR__ . '/../../src/db.php'));
    $specialTheme = $themeModel->checkSpecialTheme($user['school_id']);
}

// Get school profile data
$school = null;
$school_photo = null;
$school_name = 'AS Library';

// Get student photo from database
$studentPhoto = null;
try {
    $pdo = require __DIR__ . '/../../src/db.php';
    $userId = (int) $_SESSION['user']['id'];

    // Get student photo
    $stmt = $pdo->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentPhoto = $result['foto'] ?? null;

    // Get school profile
    $stmtSchool = $pdo->prepare('SELECT name, photo_path FROM schools WHERE id = :id');
    $stmtSchool->execute(['id' => $_SESSION['user']['school_id']]);
    $school = $stmtSchool->fetch();

    if ($school) {
        $school_photo = $school['photo_path'] ?? null;
        $school_name = $school['name'] ?? 'AS Library';
    }
} catch (Exception $e) {
    error_log('Header data fetch error: ' . $e->getMessage());
}
?>
<!-- Header -->
<style>
    /* Hide scrollbar for Chrome, Safari and Opera */
    body::-webkit-scrollbar, html::-webkit-scrollbar {
        display: none;
    }
    /* Hide scrollbar for IE, Edge and Firefox */
    body, html {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>
<?php if ($specialTheme): ?>
    <script>window.isSpecialThemeActive = true;</script>
    <link rel="stylesheet" id="special-theme-css" href="/perpustakaan-online/public/themes/special/<?php echo htmlspecialchars($specialTheme); ?>.css">
<?php endif; ?>
<header class="header">
    <div class="header-container">
        <a href="student-dashboard.php" class="header-brand">
            <div class="header-brand-icon">
                <?php if ($school_photo && file_exists(__DIR__ . '/../../' . $school_photo)): ?>
                    <img src="/perpustakaan-online/<?php echo htmlspecialchars($school_photo); ?>"
                        alt="<?php echo htmlspecialchars($school_name); ?>"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <iconify-icon icon="mdi:library" width="32" height="32" style="display: none;"></iconify-icon>
                <?php else: ?>
                    <iconify-icon icon="mdi:library" width="32" height="32"></iconify-icon>
                <?php endif; ?>
            </div>
            <div class="header-brand-text">
                <h2><?php echo htmlspecialchars($school_name); ?></h2>
                <p><?php echo htmlspecialchars($pageTitle); ?></p>
            </div>
        </a>

        <div class="header-user">
            <div class="header-user-info">
                <p class="name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></p>
                <p class="role">
                    <?php
                    $role = $user['role'] ?? 'student';
                    if ($role === 'teacher') echo 'Guru';
                    elseif ($role === 'employee') echo 'Karyawan';
                    else echo 'Anggota';
                    ?>
                </p>
            </div>
            <div class="header-user-avatar">
                <?php if ($studentPhoto && file_exists(__DIR__ . '/../' . $studentPhoto)): ?>
                    <img src="/perpustakaan-online/public/<?php echo htmlspecialchars($studentPhoto); ?>" alt="Foto Profil"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['name'] ?? 'S', 0, 1)); ?>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="header-logout">Logout</a>
        </div>
    </div>
</header>

<script>
    // Tampilkan animasi header hanya di kunjungan pertama
    document.addEventListener('DOMContentLoaded', function () {
        const header = document.querySelector('.header');
        const isFirstVisit = !sessionStorage.getItem('headerAnimated');

        if (!isFirstVisit) {
            // Jika bukan kunjungan pertama, hapus animasi
            const allElements = header.querySelectorAll('*');
            header.style.animation = 'none';
            allElements.forEach(el => {
                el.style.animation = 'none';
            });
        }

        // Tandai bahwa header sudah ditampilkan
        sessionStorage.setItem('headerAnimated', 'true');
    });
</script>