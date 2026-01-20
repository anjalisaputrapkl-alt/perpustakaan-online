<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name'] ?? '');
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';

    // Validation
    if ($school_name === '' || $admin_name === '' || $admin_email === '' || $admin_password === '') {
        $errors[] = 'Semua field wajib diisi.';
    }

    if (empty($errors)) {
        try {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school_name)));

            // Insert school
            $stmt = $pdo->prepare('INSERT INTO schools (name, slug) VALUES (:name, :slug)');
            $stmt->execute(['name' => $school_name, 'slug' => $slug]);
            $school_id = $pdo->lastInsertId();

            // Insert admin user
            $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (school_id, name, email, password, role) VALUES (:school_id, :name, :email, :password, :role)');
            $stmt->execute([
                'school_id' => $school_id,
                'name' => $admin_name,
                'email' => $admin_email,
                'password' => $password_hash,
                'role' => 'admin'
            ]);

            $success = 'Sekolah berhasil didaftarkan! Silakan login.';

        } catch (PDOException $e) {
            $errors[] = 'Database Error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Sekolah</title>
    <link rel="stylesheet" href="../assets/css/register-debug.css">
</head>

<body>
    <div class="container">
        <h1>Daftar Sekolah Baru</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert-errors">
                <?php foreach ($errors as $err): ?>
                    <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">
                <p><?php echo htmlspecialchars($success); ?></p>
                <p><a href="/perpustakaan-online/public/login.php">Klik di sini untuk login</a></p>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="school_name">Nama Sekolah</label>
                    <input type="text" id="school_name" name="school_name" required>
                </div>

                <div class="form-group">
                    <label for="admin_name">Nama Admin</label>
                    <input type="text" id="admin_name" name="admin_name" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">Email Admin</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>

                <div class="form-group">
                    <label for="admin_password">Password Admin</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="6">
                </div>

                <button type="submit">Daftarkan Sekolah</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>