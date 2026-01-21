<?php
/**
 * School Profile Diagnostic Tool
 * Gunakan untuk testing dan debugging
 * URL: http://localhost/perpustakaan-online/public/test-school-profile.php
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

// Check if user is admin
$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$is_admin || !$school_id) {
    die('❌ Akses Ditolak. Hanya admin yang dapat mengakses.');
}

// Get school data
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $school_id]);
$school = $stmt->fetch();

// Check if columns exist
$columns_check = [];
try {
    // First, let's see what columns actually exist in the school row
    $debug_info = "Available keys in \$school: " . implode(', ', array_keys($school ?? [])) . "\n";

    $columns = [
        'photo_path' => 'Foto Profil',
        'npsn' => 'NPSN',
        'website' => 'Website',
        'founded_year' => 'Tahun Berdiri',
        'email' => 'Email',
        'phone' => 'Telepon',
        'address' => 'Alamat'
    ];

    foreach ($columns as $col => $label) {
        // Use array_key_exists to check if column exists (even if value is NULL)
        $exists = array_key_exists($col, $school);
        $value = $school[$col] ?? null;

        if ($exists) {
            $columns_check[$col] = [
                'status' => '✅',
                'value' => $value ? htmlspecialchars($value) : '(kosong - belum diisi)',
                'label' => $label
            ];
        } else {
            $columns_check[$col] = [
                'status' => '❌',
                'value' => 'Kolom tidak ada di database',
                'label' => $label
            ];
        }
    }
} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}

// Check upload directory
$upload_dir = __DIR__ . '/uploads/school-photos';
$dir_exists = is_dir($upload_dir);
$dir_writable = is_writable($upload_dir);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Profile Test - Diagnostik</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 16px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 24px;
            color: #0b3d61;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 18px;
            margin-bottom: 16px;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .status-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 500;
            color: #4b5563;
        }

        .value {
            font-family: 'Courier New', monospace;
            color: #0f1724;
            font-size: 14px;
            word-break: break-all;
        }

        .status {
            font-size: 20px;
            margin-right: 12px;
        }

        .check {
            color: #10b981;
        }

        .error {
            color: #ef4444;
        }

        .warning {
            color: #f59e0b;
        }

        .code-block {
            background: #f9fafb;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New';
            font-size: 13px;
            margin: 12px 0;
            border-left: 4px solid #0b3d61;
        }

        .next-steps {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            border-radius: 6px;
            padding: 16px;
            margin-top: 24px;
        }

        .next-steps h3 {
            color: #065f46;
            margin-bottom: 12px;
        }

        .next-steps ol {
            margin-left: 20px;
        }

        .next-steps li {
            margin: 8px 0;
            color: #047857;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 School Profile - Diagnostic Test</h1>

        <!-- Database Check -->
        <div class="card">
            <h2>📊 Database Columns Check</h2>
            <div class="status-row"
                style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 2px solid #0b3d61;">
                <div
                    style="background: #fef3c7; padding: 12px; border-radius: 6px; font-size: 13px; font-family: 'Courier New';">
                    <strong>🔍 Debug Info:</strong><br>
                    <?php
                    if (isset($school) && is_array($school)) {
                        echo "Available columns in schools table: <br>";
                        echo "<code>" . implode(", ", array_keys($school)) . "</code>";
                    } else {
                        echo "⚠️ No school data found!";
                    }
                    ?>
                </div>
            </div>
            <?php foreach ($columns_check as $col => $info): ?>
                <div class="status-row">
                    <div style="flex: 1;">
                        <div class="label"><?php echo $info['label']; ?></div>
                        <div class="value" style="margin-top: 4px;"><?php echo $info['value']; ?></div>
                    </div>
                    <div class="status <?php echo strpos($info['status'], '✅') === 0 ? 'check' : 'error'; ?>">
                        <?php echo $info['status']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- File System Check -->
        <div class="card">
            <h2>💾 File System Check</h2>
            <div class="status-row">
                <div>
                    <div class="label">Uploads Directory</div>
                    <div class="value" style="margin-top: 4px;"><?php echo $upload_dir; ?></div>
                </div>
                <div class="status <?php echo $dir_exists ? 'check' : 'error'; ?>">
                    <?php echo $dir_exists ? '✅ Ada' : '❌ Tidak ada'; ?>
                </div>
            </div>
            <div class="status-row">
                <div>
                    <div class="label">Writable (dapat ditulis)</div>
                    <div class="value" style="margin-top: 4px;">public/uploads/school-photos</div>
                </div>
                <div class="status <?php echo $dir_writable ? 'check' : 'error'; ?>">
                    <?php echo $dir_writable ? '✅ Ya' : '❌ Tidak'; ?>
                </div>
            </div>
        </div>

        <!-- School Info -->
        <div class="card">
            <h2>🏫 Informasi Sekolah Anda</h2>
            <div class="status-row">
                <div class="label">ID Sekolah</div>
                <div class="value"><?php echo htmlspecialchars($school_id); ?></div>
            </div>
            <div class="status-row">
                <div class="label">Nama Sekolah</div>
                <div class="value"><?php echo htmlspecialchars($school['name']); ?></div>
            </div>
            <div class="status-row">
                <div class="label">Slug</div>
                <div class="value"><?php echo htmlspecialchars($school['slug']); ?></div>
            </div>
            <div class="status-row">
                <div class="label">Status</div>
                <div class="value"><?php echo htmlspecialchars($school['status']); ?></div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="next-steps">
            <h3>✅ Langkah Berikutnya</h3>
            <?php
            $all_columns_ok = true;
            foreach ($columns_check as $info) {
                if (strpos($info['status'], '❌') === 0) {
                    $all_columns_ok = false;
                    break;
                }
            }

            if ($all_columns_ok && $dir_writable):
                ?>
                <ol>
                    <li>Buka halaman <strong>Settings</strong> sekolah Anda</li>
                    <li>Gulir ke section <strong>"Profil Sekolah"</strong></li>
                    <li>Upload foto profil sekolah Anda</li>
                    <li>Isi semua data sekolah (NPSN, Email, Telepon, Alamat, dll)</li>
                    <li>Klik tombol <strong>"Simpan Profil"</strong></li>
                    <li>Cek sidebar - foto dan data sekolah sudah muncul!</li>
                </ol>
                <p style="margin-top: 12px; color: #047857;"><strong>Status:</strong> Sistem sudah siap! 🚀</p>
            <?php else: ?>
                <ol>
                    <?php if (!$all_columns_ok): ?>
                        <li style="color: #dc2626;"><strong>PENTING:</strong> Database belum di-update!<br>
                            Jalankan migration SQL di MySQL/phpMyAdmin:<br>
                            <div class="code-block">sql/migrations/03-school-profile.sql</div>
                        </li>
                    <?php endif; ?>
                    <?php if (!$dir_writable): ?>
                        <li style="color: #dc2626;"><strong>PENTING:</strong> Folder uploads tidak writable!<br>
                            Jalankan di terminal:<br>
                            <div class="code-block">chmod 755 public/uploads/school-photos/</div>
                        </li>
                    <?php endif; ?>
                </ol>
            <?php endif; ?>
        </div>

        <!-- Quick Links -->
        <div class="card" style="margin-top: 24px;">
            <h2>🔗 Quick Links</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <a href="/perpustakaan-online/public/settings.php#school-profile"
                    style="display: block; padding: 12px; background: #0b3d61; color: white; border-radius: 6px; text-decoration: none; text-align: center; font-weight: 500; transition: all 0.2s;">
                    ⚙️ Go to Settings
                </a>
                <a href="/perpustakaan-online/public/"
                    style="display: block; padding: 12px; background: #10b981; color: white; border-radius: 6px; text-decoration: none; text-align: center; font-weight: 500; transition: all 0.2s;">
                    🏠 Go to Dashboard
                </a>
            </div>
        </div>

        <div class="footer">
            <p>School Profile Implementation Test - Hapus file ini setelah selesai testing</p>
            <p style="margin-top: 8px; font-size: 12px;">File: public/test-school-profile.php</p>
        </div>
    </div>
</body>

</html>