<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
$anggotaId = (int) $_SESSION['user']['id'];  // ID Anggota

// Get current photo
$stmt = $pdo->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
$stmt->execute([$anggotaId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$currentFoto = $result['foto'] ?? '';

$message = '';
$errorMessage = '';

// Create upload directory if not exists
$uploadDir = __DIR__ . '/uploads/anggota';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $file = $_FILES['foto'];

    // Validasi file
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $errorMsg = '';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Gagal upload file.";
    } elseif ($file['size'] > $maxSize) {
        $errorMsg = "Ukuran file terlalu besar (maksimal 2MB).";
    } elseif (!in_array($file['type'], $allowed)) {
        $errorMsg = "Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF.";
    } else {
        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = 'anggota_' . $anggotaId . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . '/' . $newFilename;

        // Upload file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Construct public URL (Relative path)
            $photoUrl = 'uploads/anggota/' . $newFilename;

            // Delete old photo if exists
            if (!empty($currentFoto)) {
                $oldPath = __DIR__ . str_replace('/perpustakaan-online/public', '', $currentFoto);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Update database
            try {
                $updateStmt = $pdo->prepare("UPDATE siswa SET foto=?, updated_at=NOW() WHERE id_siswa=?");
                $updateStmt->execute([$photoUrl, $anggotaId]);
                $message = "Foto berhasil diperbarui!";
                
                // Update session to keep it in sync with database
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['foto'] = $photoUrl;
                }

                // Refresh current foto
                $stmt->execute([$anggotaId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentFoto = $result['foto'] ?? '';
            } catch (Exception $e) {
                $errorMsg = "Gagal menyimpan foto: " . $e->getMessage();
                @unlink($uploadPath);
            }
        } else {
            $errorMsg = "Gagal mengupload file ke server.";
        }
    }

    if ($errorMsg) {
        $errorMessage = $errorMsg;
    }
}

// Photo display
$photoUrl = !empty($currentFoto) 
    ? (strpos($currentFoto, 'uploads/') === 0 ? './'.$currentFoto : $currentFoto)
    : '../assets/images/default-avatar.svg';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Foto</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/upload-foto-style.css">
</head>
</head>

<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <div class="header">
        <div class="header-container">
            <h1>Ganti Foto Profil</h1>
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

        <div class="card">
            <h2>Foto Saat Ini</h2>
            <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto" class="photo-preview">

            <h2 style="margin-top: 24px;">Upload Foto Baru</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <p>Seret file ke sini atau <label class="upload-label" for="fotoInput">pilih file</label></p>
                    <p class="info-text">Format: JPG, PNG, GIF. Ukuran maksimal: 2MB</p>
                    <p class="file-name" id="fileName"></p>
                </div>
                <input type="file" id="fotoInput" name="foto" class="upload-input" accept="image/*">

                <div class="actions">
                    <button type="submit" class="btn primary" id="submitBtn" disabled>Upload Foto</button>
                    <a href="profil.php" class="btn secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    </div>

    <script src="../assets/js/upload-foto-manage.js"></script>
</body>

</html>