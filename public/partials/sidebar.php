<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
$user = $_SESSION['user'] ?? null;
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/perpustakaan-online/public';

function _is_active_sidebar($path, $current)
{
    $current = rtrim(str_replace('/perpustakaan-online/public', '', $current), '/') ?: '/';
    $path = rtrim(str_replace('/perpustakaan-online/public', '', $path), '/') ?: '/';
    return $current === $path ? ' active' : '';
}
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="<?php echo $base; ?>/../assets/css/global.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/../assets/css/header-sidebar.css">
</head>

<body></body>

</html>

<!-- Navigation Sidebar -->
<nav class="nav-sidebar" id="navSidebar">
    <a href="<?php echo $base; ?>/" class="nav-sidebar-header">
        <div class="nav-sidebar-header-icon">
            <iconify-icon icon="mdi:library"></iconify-icon>
        </div>
        <h2>Perpustakaan</h2>
    </a>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="<?php echo $base; ?>/" class="nav-link<?php echo _is_active_sidebar($base . '/', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:view-dashboard"></iconify-icon>
                </span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/books.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/books.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:book-multiple"></iconify-icon>
                </span>
                <span>Buku</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/book-maintenance.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/book-maintenance.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:wrench"></iconify-icon>
                </span>
                <span>Pemeliharaan</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/members.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/members.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:account-multiple"></iconify-icon>
                </span>
                <span>Anggota</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/borrows.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/borrows.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                </span>
                <span>Peminjaman</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/reports.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/reports.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:chart-line"></iconify-icon>
                </span>
                <span>Laporan</span>
            </a>
        </li>
        <?php if ($user): ?>
            <li>
                <div class="nav-sidebar-divider"></div>
            </li>
            <li>
                <a href="<?php echo $base; ?>/settings.php"
                    class="nav-link<?php echo _is_active_sidebar($base . '/settings.php', $current); ?>">
                    <span class="nav-sidebar-menu-icon">
                        <iconify-icon icon="mdi:cog"></iconify-icon>
                    </span>
                    <span>Pengaturan</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>/logout.php" class="nav-link">
                    <span class="nav-sidebar-menu-icon">
                        <iconify-icon icon="mdi:logout"></iconify-icon>
                    </span>
                    <span>Logout</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<script>
    // Make sidebar links respond to active page
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.classList.contains('active')) {
            link.setAttribute('aria-current', 'page');
        }
    });
</script>