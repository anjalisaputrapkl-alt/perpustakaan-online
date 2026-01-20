<?php
/**
 * Student Dashboard Sidebar
 * File terpisah untuk navigasi sidebar siswa
 * Include: <?php include 'partials/student-sidebar.php'; ?>
 */

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Sidebar -->
<aside class="nav-sidebar" id="navSidebar">
    <a href="student-dashboard.php" class="nav-sidebar-header">
        <div class="nav-sidebar-header-icon">
            <iconify-icon icon="mdi:library" width="32" height="32"></iconify-icon>
        </div>
        <h2>AS Library</h2>
    </a>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="student-dashboard.php" <?php echo $currentPage === 'student-dashboard.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:home" width="18" height="18"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>
            <a href="student-borrowing-history.php" <?php echo $currentPage === 'student-borrowing-history.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:book-open-variant" width="18" height="18"></iconify-icon>
                Riwayat Peminjaman
            </a>
        </li>
        <li>
            <a href="notifications.php" <?php echo $currentPage === 'notifications.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:bell" width="18" height="18"></iconify-icon>
                Notifikasi
            </a>
        </li>
        <li>
            <a href="favorites.php" <?php echo $currentPage === 'favorites.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:heart" width="18" height="18"></iconify-icon>
                Koleksi Favorit
            </a>
        </li>
        <li>
            <a href="profil.php" <?php echo $currentPage === 'profil.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:account" width="18" height="18"></iconify-icon>
                Profil Saya
            </a>
        </li>
    </ul>

    <div class="nav-sidebar-divider"></div>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="help.php" <?php echo $currentPage === 'help.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:help-circle" width="18" height="18"></iconify-icon>
                Bantuan
            </a>
        </li>
        <li>
            <a href="settings.php" <?php echo $currentPage === 'settings.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:cog" width="18" height="18"></iconify-icon>
                Pengaturan
            </a>
        </li>
        <li>
            <a href="logout.php" <?php echo $currentPage === 'logout.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:logout" width="18" height="18"></iconify-icon>
                Logout
            </a>
        </li>
    </ul>
</aside>
