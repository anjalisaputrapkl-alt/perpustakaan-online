<?php
/**
 * Siswa Data Sync Test Page
 * Membantu verify bahwa sync data members -> siswa berfungsi dengan baik
 * Hanya bisa diakses oleh authenticated users
 */

session_start();
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/db.php';

// Check auth
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    die("Unauthorized - Student login required");
}

$pdo = require __DIR__ . '/../src/db.php';
$userId = (int) $_SESSION['user']['id'];
$schoolId = (int) $_SESSION['user']['school_id'];

$syncResult = null;
$syncError = null;
$memberData = null;
$siswaDataBefore = null;
$siswaDataAfter = null;

// Get member data
try {
    $stmt = $pdo->prepare("
        SELECT id, name, nisn, member_no, email, status, created_at
        FROM members 
        WHERE id = ? AND school_id = ?
    ");
    $stmt->execute([$userId, $schoolId]);
    $memberData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $syncError = "Error fetching member data: " . $e->getMessage();
}

// Get siswa data BEFORE sync
if ($memberData) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$userId]);
        $siswaDataBefore = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $syncError = "Error fetching siswa data: " . $e->getMessage();
    }
}

// Handle manual sync button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
    if ($memberData) {
        try {
            $check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ?");
            $check->execute([$userId]);
            $exists = $check->fetch();

            if ($exists) {
                $update = $pdo->prepare("
                    UPDATE siswa 
                    SET 
                        nama_lengkap = ?,
                        nisn = ?,
                        nis = ?,
                        email = ?,
                        updated_at = NOW()
                    WHERE id_siswa = ?
                ");
                $update->execute([$memberData['name'], $memberData['nisn'], $memberData['member_no'], $memberData['email'], $userId]);
                $syncResult = "✅ Siswa record UPDATED successfully";
            } else {
                $insert = $pdo->prepare("
                    INSERT INTO siswa 
                    (id_siswa, nama_lengkap, nisn, nis, email, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $insert->execute([$userId, $memberData['name'], $memberData['nisn'], $memberData['member_no'], $memberData['email']]);
                $syncResult = "✅ Siswa record INSERTED successfully";
            }

            // Get siswa data AFTER sync
            $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
            $stmt->execute([$userId]);
            $siswaDataAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $syncError = "Sync error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Siswa Data Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f8fafc;
            color: #0f1724;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 30px;
            color: #0b3d61;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0b3d61;
        }

        .section h2 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #0b3d61;
        }

        .status-box {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .status-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }

        .status-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #0b3d61;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th {
            background: #f1f5f9;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #0b3d61;
            border-bottom: 2px solid #e2e8f0;
        }

        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .label {
            font-weight: 600;
            color: #475569;
            width: 150px;
        }

        .value {
            color: #0f1724;
        }

        .null-value {
            color: #cbd5e1;
            font-style: italic;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #0b3d61;
            color: white;
        }

        .btn-primary:hover {
            background: #062d4a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 61, 97, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #0f1724;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .comparison-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            background: #f8fafc;
        }

        .comparison-box h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #0b3d61;
        }

        .changed {
            background: #fef3c7;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔄 Siswa Data Sync Test</h1>

        <?php if ($syncResult): ?>
            <div class="status-box status-success">
                <?php echo htmlspecialchars($syncResult); ?>
            </div>
        <?php endif; ?>

        <?php if ($syncError): ?>
            <div class="status-box status-error">
                ❌ <?php echo htmlspecialchars($syncError); ?>
            </div>
        <?php endif; ?>

        <!-- Member Data Section -->
        <?php if ($memberData): ?>
            <div class="section">
                <h2>📋 Data di Table MEMBERS</h2>
                <table class="data-table">
                    <tbody>
                        <tr>
                            <td class="label">ID:</td>
                            <td class="value"><?php echo htmlspecialchars($memberData['id']); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Name:</td>
                            <td class="value"><?php echo htmlspecialchars($memberData['name']); ?></td>
                        </tr>
                        <tr>
                            <td class="label">NISN:</td>
                            <td class="value"><?php echo htmlspecialchars($memberData['nisn'] ?? 'NULL'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Member No (NIS):</td>
                            <td class="value"><?php echo htmlspecialchars($memberData['member_no'] ?? 'NULL'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Email:</td>
                            <td class="value"><?php echo htmlspecialchars($memberData['email'] ?? 'NULL'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Status:</td>
                            <td class="value"><?php echo htmlspecialchars($memberData['status']); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Created At:</td>
                            <td class="value">
                                <?php echo htmlspecialchars(date('d M Y H:i', strtotime($memberData['created_at']))); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Comparison Section -->
        <div class="section">
            <h2>🔍 Perbandingan Data SISWA (Sebelum & Sesudah Sync)</h2>

            <?php if (!$siswaDataBefore && !$memberData): ?>
                <div class="status-box status-info">
                    ℹ️ Tidak ada data member atau siswa
                </div>
            <?php else: ?>
                <div class="comparison">
                    <div class="comparison-box">
                        <h3>❌ SEBELUM Sync</h3>
                        <?php if ($siswaDataBefore): ?>
                            <table class="data-table">
                                <tbody>
                                    <tr>
                                        <td class="label">ID:</td>
                                        <td class="value"><?php echo htmlspecialchars($siswaDataBefore['id_siswa']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Nama:</td>
                                        <td class="value">
                                            <?php echo htmlspecialchars($siswaDataBefore['nama_lengkap'] ?? 'NULL'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">NISN:</td>
                                        <td class="value"><?php echo htmlspecialchars($siswaDataBefore['nisn'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">NIS:</td>
                                        <td class="value"><?php echo htmlspecialchars($siswaDataBefore['nis'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Email:</td>
                                        <td class="value"><?php echo htmlspecialchars($siswaDataBefore['email'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Updated:</td>
                                        <td class="value">
                                            <?php echo htmlspecialchars(date('d M Y H:i', strtotime($siswaDataBefore['updated_at']))); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="status-box status-info">
                                ℹ️ Belum ada record di table siswa
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="comparison-box">
                        <h3>✅ SESUDAH Sync</h3>
                        <?php if ($siswaDataAfter): ?>
                            <table class="data-table">
                                <tbody>
                                    <tr>
                                        <td class="label">ID:</td>
                                        <td class="value"><?php echo htmlspecialchars($siswaDataAfter['id_siswa']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">Nama:</td>
                                        <td
                                            class="value <?php echo ($siswaDataBefore && $siswaDataAfter['nama_lengkap'] !== $siswaDataBefore['nama_lengkap']) ? 'changed' : ''; ?>">
                                            <?php echo htmlspecialchars($siswaDataAfter['nama_lengkap'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">NISN:</td>
                                        <td
                                            class="value <?php echo ($siswaDataBefore && $siswaDataAfter['nisn'] !== $siswaDataBefore['nisn']) ? 'changed' : ''; ?>">
                                            <?php echo htmlspecialchars($siswaDataAfter['nisn'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">NIS:</td>
                                        <td
                                            class="value <?php echo ($siswaDataBefore && $siswaDataAfter['nis'] !== $siswaDataBefore['nis']) ? 'changed' : ''; ?>">
                                            <?php echo htmlspecialchars($siswaDataAfter['nis'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Email:</td>
                                        <td
                                            class="value <?php echo ($siswaDataBefore && $siswaDataAfter['email'] !== $siswaDataBefore['email']) ? 'changed' : ''; ?>">
                                            <?php echo htmlspecialchars($siswaDataAfter['email'] ?? 'NULL'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Updated:</td>
                                        <td class="value">
                                            <?php echo htmlspecialchars(date('d M Y H:i', strtotime($siswaDataAfter['updated_at']))); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="status-box status-info">
                                ℹ️ Belum di-sync (klik tombol Sync di bawah)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Section -->
        <div class="section">
            <h2>⚙️ Aksi</h2>
            <form method="POST">
                <div class="button-group">
                    <button type="submit" name="sync" value="1" class="btn-primary">🔄 Sinkronisasi Sekarang</button>
                    <button type="button" onclick="window.location.href='profil.php'" class="btn-secondary">← Kembali ke
                        Profil</button>
                </div>
            </form>
        </div>

        <!-- Info Section -->
        <div class="section">
            <h2>ℹ️ Informasi</h2>
            <p style="font-size: 13px; line-height: 1.6; color: #475569;">
                <strong>Halaman ini untuk testing saja.</strong> Ketika Anda mengakses halaman profil normal di <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">/profil.php</code>,
                data akan otomatis disinkronkan dari table <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">members</code> ke table <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">siswa</code> tanpa perlu
                intervensi manual.<br>
                <br>
                <strong>Proses Sinkronisasi:</strong><br>
                1️⃣ Ambil data dari table <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">members</code><br>
                2️⃣ Cek apakah record sudah ada di table <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">siswa</code><br>
                3️⃣ Jika ada: UPDATE dengan data terbaru dari <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">members</code><br>
                4️⃣ Jika tidak ada: INSERT record baru<br>
                5️⃣ Tampilkan data dari table <code
                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">siswa</code> di profil
            </p>
        </div>
    </div>
</body>

</html>