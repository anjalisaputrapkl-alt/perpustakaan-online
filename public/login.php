<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
  $stmt->execute(['email' => $email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user'] = [
      'id' => $user['id'],
      'school_id' => $user['school_id'],
      'name' => $user['name'],
      'role' => $user['role']
    ];
    header('Location: /perpustakaan-online/public/index.php');
    exit;
  } else {
    $message = 'Email atau password salah.';
  }
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Perpustakaan Online</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

  <div class="login-card">

    <div class="logo">
      <div class="logo-icon">📚</div>
      <h1>Masuk Perpustakaan</h1>
      <p>Kelola perpustakaan sekolah Anda</p>
    </div>

    <?php if ($message): ?>
      <div class="alert">
        <strong>⚠️</strong>
        <div><?= $message ?></div>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required placeholder="admin@sekolah.com">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>

      <button class="btn btn-primary">🔓 Login</button>
    </form>

    <div class="divider"></div>

    <p class="footer-text">Belum punya akun?</p>
    <a href="/perpustakaan-online/public/register.php">
      <button class="btn btn-secondary">📝 Daftar Akun</button>
    </a>

  </div>

</body>

</html>