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

  <style>
    :root {
      --bg: #f1f4f8;
      --surface: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --accent: #2563eb;
      --danger: #dc2626;
    }

    * {
      box-sizing: border-box
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg);
      font-family: Inter, system-ui, sans-serif;
      color: var(--text);
    }

    .login-card {
      width: 100%;
      max-width: 420px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
    }

    .logo {
      text-align: center;
      margin-bottom: 24px;
    }

    .logo .icon {
      font-size: 48px;
      margin-bottom: 12px;
    }

    .logo h1 {
      font-size: 22px;
      margin: 0 0 6px;
    }

    .logo p {
      font-size: 13px;
      color: var(--muted);
      margin: 0;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 16px;
    }

    label {
      font-size: 12px;
      color: var(--muted);
    }

    input {
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 13px;
    }

    input:focus {
      outline: none;
      border-color: var(--accent);
    }

    .btn {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      font-size: 13px;
      border: 1px solid var(--border);
      background: white;
      cursor: pointer;
    }

    .btn.primary {
      background: var(--accent);
      color: white;
      border: none;
    }

    .btn.secondary {
      background: #f9fafb;
    }

    .alert {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      background: #fee2e2;
      color: var(--danger);
      border: 1px solid #fecaca;
      border-radius: 8px;
      padding: 12px;
      font-size: 13px;
      margin-bottom: 16px;
    }

    .divider {
      border-top: 1px solid var(--border);
      margin: 24px 0;
      text-align: center;
    }

    .footer-text {
      font-size: 12px;
      color: var(--muted);
      text-align: center;
      margin-bottom: 12px;
    }
  </style>
</head>

<body>

  <div class="login-card">

    <div class="logo">
      <div class="icon">📚</div>
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

      <button class="btn primary">🔓 Login</button>
    </form>

    <div class="divider"></div>

    <p class="footer-text">Belum punya akun?</p>
    <a href="/perpustakaan-online/public/register.php">
      <button class="btn secondary">📝 Daftar Akun</button>
    </a>

  </div>

</body>

</html>