<?php
/**
 * Debug Script untuk Memverifikasi Sync
 * Gunakan ini untuk cek apakah sync benar-benar terjadi
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Demo mode - gunakan hardcoded user ID untuk testing
    $_SESSION['user'] = [
        'id' => 2,  // Change ini ke student ID yang mau ditest
        'school_id' => 2,
        'role' => 'student'
    ];
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>⚠️ Demo Mode:</strong> Menggunakan student ID = 2. Ubah di line 16 sesuai user yang mau ditest.";
    echo "</div>";
}

$userId = (int) $_SESSION['user']['id'];
$schoolId = (int) $_SESSION['user']['school_id'];

try {
    $pdo = require __DIR__ . '/src/db.php';
} catch (Exception $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Sync - Verifikasi Data</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        h2 {
            color: #0b3d61;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-box {
            border-left: 4px solid #0b3d61;
        }

        .success {
            border-left-color: #10b981;
            background: #d1fae5;
        }

        .error {
            border-left-color: #ef4444;
            background: #fee2e2;
        }

        .warning {
            border-left-color: #f59e0b;
            background: #fef3e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th {
            background: #f0f0f0;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .null {
            color: #999;
            font-style: italic;
        }

        .changed {
            background: #ffffcc;
            font-weight: bold;
        }

        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        button {
            background: #0b3d61;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px 0;
        }

        button:hover {
            background: #062d4a;
        }

        button.secondary {
            background: #6b7280;
        }

        button.secondary:hover {
            background: #4b5563;
        }

        .action-box {
            background: #dbeafe;
            border-left: 4px solid #0b3d61;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 Debug Sync - Verifikasi Data Members → Siswa</h1>

        <div class="box info-box">
            <strong>Student ID:</strong> <?php echo $userId; ?><br>
            <strong>School ID:</strong> <?php echo $schoolId; ?>
        </div>

        <?php
        // 1. Check Members Data
        echo "<h2>📋 Step 1: Data di Table MEMBERS</h2>";
        try {
            $stmt = $pdo->prepare("
                SELECT id, name, nisn, member_no, email, status, school_id, created_at
                FROM members 
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([$userId, $schoolId]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($member) {
                echo "<div class='box success'>";
                echo "✅ Data ditemukan di members table<br>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($member as $key => $value) {
                    $display = $value === null ? '<span class="null">NULL</span>' : htmlspecialchars($value);
                    echo "<tr><td><code>$key</code></td><td>$display</td></tr>";
                }
                echo "</table>";
                echo "</div>";
            } else {
                echo "<div class='box error'>";
                echo "❌ Tidak ada data untuk student ID $userId di school ID $schoolId<br>";
                echo "Debug: Cek apakah user sudah login dengan benar atau ID sudah ada di members table";
                echo "</div>";
                $member = null;
            }
        } catch (Exception $e) {
            echo "<div class='box error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            $member = null;
        }

        // 2. Check Siswa Data BEFORE Sync
        echo "<h2>📋 Step 2: Data di Table SISWA (SEBELUM Sync)</h2>";
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM siswa WHERE id_siswa = ?
            ");
            $stmt->execute([$userId]);
            $siswaBeforeSync = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($siswaBeforeSync) {
                echo "<div class='box warning'>";
                echo "⚠️ Record SUDAH ADA di siswa table<br>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($siswaBeforeSync as $key => $value) {
                    $display = $value === null ? '<span class="null">NULL</span>' : htmlspecialchars($value);
                    echo "<tr><td><code>$key</code></td><td>$display</td></tr>";
                }
                echo "</table>";
                echo "</div>";
            } else {
                echo "<div class='box warning'>";
                echo "⚠️ Record BELUM ADA di siswa table (akan dibuat saat sync)<br>";
                echo "</div>";
                $siswaBeforeSync = null;
            }
        } catch (Exception $e) {
            echo "<div class='box error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            $siswaBeforeSync = null;
        }

        // 3. Run SYNC Manually
        if ($member) {
            echo "<h2>⚙️ Step 3: Jalankan SYNC Sekarang</h2>";

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_sync'])) {
                try {
                    // Check if exists
                    $check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ?");
                    $check->execute([$userId]);
                    $exists = $check->fetch();

                    if ($exists) {
                        // UPDATE
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
                        $result = $update->execute([
                            $member['name'],
                            $member['nisn'],
                            $member['member_no'],
                            $member['email'],
                            $userId
                        ]);

                        echo "<div class='box success'>";
                        echo "✅ UPDATE berhasil!<br>";
                        echo "Field yang diupdate:<br>";
                        echo "- nama_lengkap: " . htmlspecialchars($member['name']) . "<br>";
                        echo "- nisn: " . htmlspecialchars($member['nisn'] ?? 'NULL') . "<br>";
                        echo "- nis: " . htmlspecialchars($member['member_no'] ?? 'NULL') . "<br>";
                        echo "- email: " . htmlspecialchars($member['email'] ?? 'NULL') . "<br>";
                        echo "- updated_at: NOW()<br>";
                        echo "</div>";
                    } else {
                        // INSERT
                        $insert = $pdo->prepare("
                            INSERT INTO siswa 
                            (id_siswa, nama_lengkap, nisn, nis, email, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $result = $insert->execute([
                            $userId,
                            $member['name'],
                            $member['nisn'],
                            $member['member_no'],
                            $member['email']
                        ]);

                        echo "<div class='box success'>";
                        echo "✅ INSERT berhasil! Record baru dibuat.<br>";
                        echo "Field yang diisi:<br>";
                        echo "- id_siswa: $userId<br>";
                        echo "- nama_lengkap: " . htmlspecialchars($member['name']) . "<br>";
                        echo "- nisn: " . htmlspecialchars($member['nisn'] ?? 'NULL') . "<br>";
                        echo "- nis: " . htmlspecialchars($member['member_no'] ?? 'NULL') . "<br>";
                        echo "- email: " . htmlspecialchars($member['email'] ?? 'NULL') . "<br>";
                        echo "</div>";

                        $siswaBeforeSync = null; // Reset untuk update Step 4
                    }
                } catch (Exception $e) {
                    echo "<div class='box error'>";
                    echo "❌ Sync GAGAL!<br>";
                    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
                    echo "Stack Trace:<br>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                    echo "</div>";
                }
            } else {
                echo "<div class='action-box'>";
                echo "<strong>Klik tombol di bawah untuk menjalankan sync manual:</strong><br>";
                echo "<form method='POST'>";
                echo "<button type='submit' name='run_sync' value='1'>▶️ Jalankan SYNC Sekarang</button>";
                echo "</form>";
                echo "</div>";
            }
        }

        // 4. Check Siswa Data AFTER Sync
        echo "<h2>📋 Step 4: Data di Table SISWA (SESUDAH Sync)</h2>";
        try {
            $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
            $stmt->execute([$userId]);
            $siswaAfterSync = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($siswaAfterSync) {
                echo "<div class='box success'>";
                echo "✅ Record ada di siswa table (UPDATED/CREATED)<br>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($siswaAfterSync as $key => $value) {
                    $display = $value === null ? '<span class="null">NULL</span>' : htmlspecialchars($value);
                    echo "<tr><td><code>$key</code></td><td>$display</td></tr>";
                }
                echo "</table>";
                echo "</div>";

                // Comparison
                if ($member) {
                    echo "<h2>🔍 Verifikasi Field yang Disync</h2>";
                    echo "<div class='box'>";
                    echo "<table>";
                    echo "<tr><th>Field</th><th>From Members</th><th>In Siswa</th><th>Status</th></tr>";

                    $fields = [
                        ['member_field' => 'name', 'siswa_field' => 'nama_lengkap'],
                        ['member_field' => 'member_no', 'siswa_field' => 'nis'],
                        ['member_field' => 'nisn', 'siswa_field' => 'nisn'],
                        ['member_field' => 'email', 'siswa_field' => 'email'],
                    ];

                    foreach ($fields as $field) {
                        $memberVal = $member[$field['member_field']] ?? 'NULL';
                        $siswaVal = $siswaAfterSync[$field['siswa_field']] ?? 'NULL';
                        $match = $memberVal === $siswaVal ? '✅ Match' : '❌ Mismatch';
                        $memberDisplay = $memberVal === null ? '<span class="null">NULL</span>' : htmlspecialchars($memberVal);
                        $siswaDisplay = $siswaVal === null ? '<span class="null">NULL</span>' : htmlspecialchars($siswaVal);
                        echo "<tr>";
                        echo "<td><code>{$field['siswa_field']}</code></td>";
                        echo "<td>$memberDisplay</td>";
                        echo "<td>$siswaDisplay</td>";
                        echo "<td>$match</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</div>";
                }
            } else {
                echo "<div class='box warning'>";
                echo "⚠️ Record TIDAK ADA di siswa table<br>";
                echo "Kemungkinan: Belum dijalankan sync (klik tombol di Step 3)<br>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='box error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        // 5. Summary & Next Steps
        echo "<h2>📝 Summary & Langkah Selanjutnya</h2>";
        echo "<div class='box'>";
        echo "<strong>Apa yang harus dilakukan:</strong><br>";
        echo "1. ✅ Jalankan sync manual menggunakan tombol di Step 3<br>";
        echo "2. ✅ Verifikasi data di Step 4 sudah terupdate dari members<br>";
        echo "3. ✅ Buka phpmyadmin untuk verifikasi database langsung<br>";
        echo "4. ✅ Buka /public/profil.php untuk test auto-sync<br>";
        echo "5. ✅ Edit profile fields (kelas, jurusan, dll) sesuai kebutuhan<br>";
        echo "<br>";
        echo "<strong>Jika ada error:</strong><br>";
        echo "- Cek PHP error log: <code>C:\\xampp\\logs\\php_error.log</code><br>";
        echo "- Cek database credentials di <code>/src/db.php</code><br>";
        echo "- Pastikan table siswa sudah ada dengan semua columns<br>";
        echo "</div>";

        // 6. Quick SQL Check
        echo "<h2>🗄️ Database Structure Check</h2>";
        try {
            echo "<strong>Columns di members table:</strong><br>";
            $result = $pdo->query("DESCRIBE members");
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td><code>{$col['Field']}</code></td><td>{$col['Type']}</td><td>{$col['Null']}</td></tr>";
            }
            echo "</table>";

            echo "<br><strong>Columns di siswa table:</strong><br>";
            $result = $pdo->query("DESCRIBE siswa");
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td><code>{$col['Field']}</code></td><td>{$col['Type']}</td><td>{$col['Null']}</td></tr>";
            }
            echo "</table>";
        } catch (Exception $e) {
            echo "<div class='box error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>

</html>