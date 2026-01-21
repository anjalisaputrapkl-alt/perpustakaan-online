<?php
/**
 * Bulk Sync Script
 * Sinkronisasi semua data dari members ke siswa table
 * Jalankan sekali untuk populate data
 */

try {
    $pdo = require __DIR__ . '/src/db.php';

    echo "<style>";
    echo "body { font-family: Arial; background: #f5f5f5; padding: 20px; }";
    echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
    echo "h1 { color: #0b3d61; }";
    echo ".success { background: #d1fae5; border: 1px solid #10b981; padding: 10px; margin: 10px 0; border-radius: 5px; }";
    echo ".error { background: #fee2e2; border: 1px solid #ef4444; padding: 10px; margin: 10px 0; border-radius: 5px; }";
    echo ".info { background: #dbeafe; border: 1px solid #0b3d61; padding: 10px; margin: 10px 0; border-radius: 5px; }";
    echo "code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }";
    echo "button { background: #0b3d61; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }";
    echo "button:hover { background: #062d4a; }";
    echo "table { border-collapse: collapse; width: 100%; margin: 10px 0; }";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "th { background: #f0f0f0; }";
    echo "</style>";

    echo "<div class='container'>";
    echo "<h1>🔄 Bulk Sync: Members → Siswa</h1>";

    // Get all members
    echo "<div class='info'><strong>Step 1: Mengambil semua data dari table members...</strong></div>";

    $stmt = $pdo->query("
        SELECT id, name, nisn, member_no, email, school_id
        FROM members 
        ORDER BY id
    ");
    $allMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='success'>✅ Ditemukan " . count($allMembers) . " member(s)</div>";

    if (count($allMembers) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>NISN</th><th>Member No</th><th>Email</th></tr>";
        foreach ($allMembers as $member) {
            echo "<tr>";
            echo "<td>" . $member['id'] . "</td>";
            echo "<td>" . htmlspecialchars($member['name']) . "</td>";
            echo "<td>" . htmlspecialchars($member['nisn'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($member['member_no'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($member['email'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Perform sync
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_sync'])) {
        echo "<div class='info'><strong>Step 2: Menjalankan Sync...</strong></div>";

        $synced = 0;
        $updated = 0;
        $inserted = 0;
        $errors = [];

        foreach ($allMembers as $member) {
            try {
                // Check if exists
                $check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ?");
                $check->execute([$member['id']]);
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
                    $update->execute([
                        $member['name'],
                        $member['nisn'],
                        $member['member_no'],
                        $member['email'],
                        $member['id']
                    ]);
                    $updated++;
                } else {
                    // INSERT
                    $insert = $pdo->prepare("
                        INSERT INTO siswa 
                        (id_siswa, nama_lengkap, nisn, nis, email, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $insert->execute([
                        $member['id'],
                        $member['name'],
                        $member['nisn'],
                        $member['member_no'],
                        $member['email']
                    ]);
                    $inserted++;
                }
                $synced++;
            } catch (Exception $e) {
                $errors[] = "ID " . $member['id'] . ": " . $e->getMessage();
            }
        }

        echo "<div class='success'>";
        echo "<strong>✅ Sync Selesai!</strong><br>";
        echo "Total diproses: " . $synced . "<br>";
        echo "Inserted: " . $inserted . "<br>";
        echo "Updated: " . $updated . "<br>";
        echo "</div>";

        if (!empty($errors)) {
            echo "<div class='error'>";
            echo "<strong>⚠️ Errors:</strong><br>";
            foreach ($errors as $error) {
                echo "- " . htmlspecialchars($error) . "<br>";
            }
            echo "</div>";
        }

        // Show verification
        echo "<div class='info'><strong>Step 3: Verifikasi hasil sync...</strong></div>";

        $stmt = $pdo->query("
            SELECT id_siswa, nama_lengkap, nis, nisn, email, updated_at
            FROM siswa 
            ORDER BY id_siswa
        ");
        $siswaRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<div class='success'>✅ Sekarang ada " . count($siswaRecords) . " record di siswa table</div>";

        if (count($siswaRecords) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nama Lengkap</th><th>NIS</th><th>NISN</th><th>Email</th><th>Updated</th></tr>";
            foreach ($siswaRecords as $siswa) {
                echo "<tr>";
                echo "<td>" . $siswa['id_siswa'] . "</td>";
                echo "<td>" . htmlspecialchars($siswa['nama_lengkap']) . "</td>";
                echo "<td>" . htmlspecialchars($siswa['nis'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($siswa['nisn'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($siswa['email'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($siswa['updated_at'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        echo "<div class='success'>";
        echo "<strong>✅ Data sudah tersync!</strong><br>";
        echo "Sekarang user bisa:<br>";
        echo "1. Login dan buka /public/profil.php untuk lihat profile<br>";
        echo "2. Edit custom fields (kelas, jurusan, dll) di profile-edit page<br>";
        echo "3. Setiap kali buka profile, data members akan otomatis update siswa<br>";
        echo "</div>";

    } else {
        echo "<div class='info'>";
        echo "<form method='POST'>";
        echo "<strong>Klik tombol di bawah untuk SINKRONISASI semua member ke siswa:</strong><br><br>";
        echo "<button type='submit' name='start_sync' value='1'>▶️ Mulai Sinkronisasi</button>";
        echo "</form>";
        echo "</div>";
    }

    echo "</div>";

} catch (Exception $e) {
    echo "<div style='max-width: 1000px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px;'>";
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</div>";
}
?>