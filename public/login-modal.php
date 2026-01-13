<?php
/**
 * login-modal.php - Login Page untuk Subdomain Sekolah
 * 
 * Halaman ini ditampilkan ketika user mengakses subdomain sekolah
 * tapi belum login (contoh: sma1.perpus.test)
 */

require __DIR__ . '/tenant-router.php';

// Redirect ke main domain jika mengakses dari main domain
if (IS_MAIN_DOMAIN) {
    header('Location: http://perpus.test/');
    exit;
}

// Jika subdomain tidak valid, tampilkan error
if (!IS_VALID_TENANT) {
    http_response_code(404);
    die('
        <div style="text-align:center; margin-top:100px; font-family:Arial;">
            <h1>Sekolah Tidak Ditemukan</h1>
            <p>Subdomain "<strong>' . htmlspecialchars(SUBDOMAIN) . '</strong>" tidak terdaftar.</p>
            <p><a href="http://perpus.test/">← Kembali ke Halaman Utama</a></p>
        </div>
    ');
}

// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = require __DIR__ . '/../src/db.php';

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $stmt = $pdo->prepare(
            'SELECT * FROM users WHERE email = :email AND school_id = :school_id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'school_id' => SCHOOL_ID]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'school_id' => $user['school_id'],
                'name' => $user['name'],
                'role' => $user['role']
            ];
            header('Location: /public/index.php');
            exit;
        } else {
            $error = 'Email atau password salah untuk sekolah ini.';
        }
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?php echo htmlspecialchars(SCHOOL_NAME); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Inter, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .school-info {
            text-align: center;
            margin-bottom: 32px;
        }

        .school-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .school-name {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .school-sub {
            font-size: 13px;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            transition: 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            font-family: inherit;
        }

        .btn-submit:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        .divider {
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        .back-link {
            display: block;
            text-align: center;
            font-size: 13px;
            color: #667eea;
            text-decoration: none;
            margin-top: 16px;
            transition: 0.2s ease;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .subdomain-badge {
            display: inline-block;
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="school-info">
            <div class="school-icon">📚</div>
            <h1 class="school-name"><?php echo htmlspecialchars(SCHOOL_NAME); ?></h1>
            <p class="school-sub">Sistem Perpustakaan Digital</p>
            <span class="subdomain-badge"><?php echo htmlspecialchars(SUBDOMAIN); ?>.perpus.test</span>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Admin</label>
                <input type="email" id="email" name="email" required placeholder="admin@sekolah.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-submit">🔓 Login</button>
        </form>

        <div class="divider">atau</div>

        <a href="http://perpus.test/" class="back-link">← Daftar Sekolah Baru di Platform Utama</a>
    </div>

</body>

</html>