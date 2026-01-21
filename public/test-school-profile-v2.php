<?php
/**
 * Advanced School Profile Diagnostic Tool V2
 * Untuk debug detail kolom database
 */

session_start();
$pdo = require __DIR__ . '/../src/db.php';

// Check if user is admin
$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$is_admin || !$school_id) {
    die('❌ Akses Ditolak. Hanya admin yang dapat mengakses.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Profile Test V2 - Advanced Debug</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #1e293b;
            color: #e2e8f0;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 16px;
        }

        h1 {
            color: #60a5fa;
            margin-bottom: 20px;
            font-size: 24px;
        }

        h2 {
            color: #34d399;
            margin: 20px 0 10px 0;
            font-size: 16px;
            border-bottom: 1px solid #475569;
            padding-bottom: 5px;
        }

        .section {
            background: #334155;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            border: 1px solid #475569;
        }

        code,
        pre {
            background: #0f172a;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 3px solid #60a5fa;
        }

        .status-ok {
            color: #34d399;
        }

        .status-error {
            color: #f87171;
        }

        .info {
            color: #60a5fa;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #475569;
        }

        th {
            background: #1e293b;
            color: #60a5fa;
            font-weight: bold;
        }

        tr:hover {
            background: #475569;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔬 School Profile Debug V2 - Advanced Diagnostic</h1>

        <!-- 1. Check Table Schema -->
        <div class="section">
            <h2>1️⃣ Table Schema (dari INFORMATION_SCHEMA)</h2>
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME='schools' AND TABLE_SCHEMA='perpustakaan_online'
                    ORDER BY ORDINAL_POSITION
                ");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($columns) > 0) {
                    echo "<p class='status-ok'>✅ Total " . count($columns) . " columns found</p>";
                    echo "<table>";
                    echo "<tr><th>Column Name</th><th>Type</th><th>Nullable</th></tr>";

                    $required_cols = ['photo_path', 'npsn', 'website', 'founded_year', 'email', 'phone', 'address'];
                    foreach ($columns as $col) {
                        $col_name = $col['COLUMN_NAME'];
                        $is_required = in_array($col_name, $required_cols);
                        $status = $is_required ? '⭐' : '  ';
                        echo "<tr>";
                        echo "<td>$status <strong>$col_name</strong></td>";
                        echo "<td>" . $col['COLUMN_TYPE'] . "</td>";
                        echo "<td>" . $col['IS_NULLABLE'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='status-error'>❌ No columns found!</p>";
                }
            } catch (Exception $e) {
                echo "<p class='status-error'>❌ Error: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>

        <!-- 2. SELECT * Result -->
        <div class="section">
            <h2>2️⃣ SELECT * Result (Actual Data)</h2>
            <?php
            try {
                $stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
                $stmt->execute(['id' => $school_id]);
                $school = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($school) {
                    echo "<p class='status-ok'>✅ School found (ID: $school_id)</p>";
                    echo "<p class='info'>Available keys: <code>" . implode(", ", array_keys($school)) . "</code></p>";
                    echo "<table>";
                    echo "<tr><th>Column</th><th>Value</th></tr>";
                    foreach ($school as $key => $value) {
                        $display_val = $value !== null ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : '(NULL)';
                        echo "<tr>";
                        echo "<td><strong>$key</strong></td>";
                        echo "<td>" . htmlspecialchars($display_val) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='status-error'>❌ School tidak ditemukan!</p>";
                }
            } catch (Exception $e) {
                echo "<p class='status-error'>❌ Error: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>

        <!-- 3. Check Specific Columns -->
        <div class="section">
            <h2>3️⃣ Required Columns Status</h2>
            <?php
            $required = ['photo_path', 'npsn', 'website', 'founded_year', 'email', 'phone', 'address'];
            $all_ok = true;

            echo "<table>";
            echo "<tr><th>Column</th><th>Status</th><th>Current Value</th></tr>";

            foreach ($required as $col) {
                $exists = isset($school[$col]);
                $value = $school[$col] ?? 'N/A';
                $status = $exists ? "<span class='status-ok'>✅ EXISTS</span>" : "<span class='status-error'>❌ MISSING</span>";

                if (!$exists)
                    $all_ok = false;

                $display = $value !== null ? (strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value) : '(NULL)';
                echo "<tr>";
                echo "<td><strong>$col</strong></td>";
                echo "<td>$status</td>";
                echo "<td>" . htmlspecialchars($display) . "</td>";
                echo "</tr>";
            }
            echo "</table>";

            if ($all_ok) {
                echo "<p class='status-ok'>✅ Semua kolom required ada!</p>";
            } else {
                echo "<p class='status-error'>❌ Ada kolom yang masih missing!</p>";
            }
            ?>
        </div>

        <!-- 4. Raw Query Check -->
        <div class="section">
            <h2>4️⃣ Raw Database Query</h2>
            <p>Jalankan query ini di MySQL untuk verifikasi:</p>
            <pre>
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='schools' 
AND TABLE_SCHEMA='perpustakaan_online'
ORDER BY ORDINAL_POSITION;
            </pre>
        </div>

        <div class="section" style="background: #1e293b; border: 1px solid #60a5fa;">
            <h2>📝 Summary</h2>
            <p>
                Jika kolom masih merah di sini, mungkin masalahnya:<br>
                1. <strong>Kolom belum ada di database</strong> - cek bagian 1<br>
                2. <strong>Query SELECT * gagal</strong> - cek bagian 2<br>
                3. <strong>Cache PHP belum refresh</strong> - Ctrl+Shift+R di browser<br>
                <br>
                Bagikan screenshot bagian 1 dan 2 untuk debug lebih lanjut!
            </p>
        </div>
    </div>
</body>

</html>