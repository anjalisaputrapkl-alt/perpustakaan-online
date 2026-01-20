<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();
$user = $_SESSION['user'] ?? null;
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/perpustakaan-online/public';
function _is_active($path, $current)
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

<!-- Header -->
<header class="header">
  <div class="header-container">
    <div class="header-brand">
      <div class="header-brand-icon">
        <iconify-icon icon="mdi:library"></iconify-icon>
      </div>
      <div class="header-brand-text">
        <h2>Perpustakaan</h2>
        <p>Admin Dashboard</p>
      </div>
    </div>

    <div class="header-user">
      <div class="header-user-info">
        <p class="name"><?php echo htmlspecialchars($user['name'] ?? 'Admin'); ?></p>
        <p class="role">Administrator</p>
      </div>
      <div class="header-user-avatar">
        <?php echo htmlspecialchars(strtoupper(substr($user['name'] ?? 'A', 0, 1))); ?>
      </div>
      <a href="<?php echo $base; ?>/logout.php" class="header-logout">
        <iconify-icon icon="mdi:logout"></iconify-icon>
        Logout
      </a>
    </div>
  </div>
</header>