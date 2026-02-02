<?php
// Load auth helpers (this will handle session_start internally)
require __DIR__ . '/../src/auth.php';

// Initialize database
try {
    $pdo = require __DIR__ . '/../src/db.php';
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
}

// Check if preview mode is enabled (localhost only, for development)
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isPreviewMode = isset($_GET['preview']) && $_GET['preview'] === '1' && $isLocalhost;

$member = null;
$user = $_SESSION['user'] ?? null;

// Route 1: Normal authenticated user - get from session
if ($user && !empty($user['id'])) {
    $member = $user; // Use session data directly
    
    // Enrich with database data if available
    if (isset($pdo)) {
        try {
            // Fetch school info and siswa-specific fields from database
            $stmt = $pdo->prepare(
                'SELECT u.id, u.name, u.nisn, u.school_id,
                        s.student_uuid AS student_uuid, s.foto AS foto, s.kelas, s.jurusan,
                        sch.name AS school_name, sch.address AS location, sch.logo AS school_logo
                 FROM users u
                 LEFT JOIN siswa s ON s.id_siswa = u.id
                 LEFT JOIN schools sch ON u.school_id = sch.id
                 WHERE u.id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => (int)$user['id']]);
            $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dbData) {
                // Merge database data with session data
                $member = array_merge($member, $dbData);
            }
        } catch (Exception $e) {
            error_log("Database query error in student-card: " . $e->getMessage());
        }
    }
}

// Route 2: Preview mode (localhost development only)
if (!$member && $isPreviewMode && isset($pdo)) {
    try {
            $stmt = $pdo->query(
            'SELECT u.id, u.name, u.nisn, u.school_id,
                s.student_uuid AS student_uuid, s.foto AS foto, s.kelas, s.jurusan,
                sch.name AS school_name, sch.address AS location, sch.logo AS school_logo
             FROM users u
             LEFT JOIN siswa s ON s.id_siswa = u.id
             LEFT JOIN schools sch ON u.school_id = sch.id
             ORDER BY u.id ASC LIMIT 1'
        );
        if ($stmt) {
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($member) {
                // Ensure session user has minimal fields
                $_SESSION['user'] = ['id' => $member['id'], 'school_id' => $member['school_id'] ?? null, 'name' => $member['name'], 'nisn' => $member['nisn'] ?? null];
            }
        }
    } catch (Exception $e) {
        error_log("Preview mode query error: " . $e->getMessage());
    }
}

// No member found: require authentication
if (!$member) {
    header('Location: index.php', true, 302);
    exit;
}

// Get photo URL safely
$photoSrc = '../assets/images/default-avatar.svg'; // Corrected path
if (!empty($member['foto'])) {
    $photoPath = $member['foto'];
    // Handle relative or absolute paths
    if (strpos($photoPath, 'http') === 0) {
        $photoSrc = htmlspecialchars($photoPath);
    } else {
        // Assume upload path relative to public root
        $photoSrc = htmlspecialchars($photoPath);
    }
}

// Ensure barcode value is clean (Prioritize NISN)
$barcodeValue = trim($member['nisn'] ?? $member['student_uuid'] ?? $member['id'] ?? '');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Perpustakaan - <?= htmlspecialchars($member['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <!-- JsBarcode for client-side barcode generation -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --primary: #3A7FF2;
            --primary-2: #7AB8F5;
            --primary-dark: #0A1A4F;
            --bg: #F6F9FF;
            --card: #FFFFFF;
            --border: #E6EEF8;
            --text: #0F172A;
            --text-muted: #50607A;
            --accent: #3A7FF2;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 24px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .nav-back {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* --- THE CARD --- */
        .library-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(58, 127, 242, 0.15);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.8);
            position: relative;
            transform-style: preserve-3d;
            perspective: 1000px;
            margin-bottom: 32px;
        }
        
        .library-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(58, 127, 242, 0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            z-index: 0;
        }

        .card-header {
            background: var(--primary);
            color: white;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .school-logo-frame {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .school-logo-frame img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .school-info h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .school-info p {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 2px;
        }

        .card-body {
            padding: 24px;
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .student-profile {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .student-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            object-fit: cover;
            background: #eee;
        }

        .student-details {
            flex: 1;
        }

        .student-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .student-id-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .student-id {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            font-family: monospace;
            background: rgba(58, 127, 242, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .barcode-section {
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        /* Barcode SVG scaling */
        #barcode {
            max-width: 100%;
            height: auto;
        }

        .card-footer-strip {
            background: #f1f5f9;
            padding: 10px;
            text-align: center;
            font-size: 10px;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
        }

        /* --- ACTIONS --- */
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn {
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: white;
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f8fafc;
        }

        @media print {
            body { 
                background: white; 
                padding: 0; 
                align-items: flex-start;
            }
            .nav-back, .page-header p, .actions { display: none; }
            .container { max-width: 100%; margin: 0; }
            .library-card {
                box-shadow: none;
                border: 1px solid #ccc;
                page-break-inside: avoid;
            }
        }
        
        @media (max-width: 480px) {
            .student-profile {
                flex-direction: column;
                text-align: center;
            }
            .card-header {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="profil.php" class="nav-back">
            <iconify-icon icon="mdi:arrow-left"></iconify-icon>
            Kembali ke Profil
        </a>

        <div class="page-header">
            <h1>Kartu Perpustakaan</h1>
            <p>Gunakan kartu ini untuk meminjam buku</p>
        </div>

        <!-- THE CARD -->
        <div class="library-card" id="printableCard">
            <!-- Header -->
            <div class="card-header">
                <div class="school-logo-frame">
                   <?php if (!empty($member['school_logo'])): ?>
                        <img src="<?= htmlspecialchars($member['school_logo']) ?>" alt="Logo">
                    <?php else: ?>
                        <iconify-icon icon="mdi:school" style="color:var(--primary); font-size:24px;"></iconify-icon>
                    <?php endif; ?>
                </div>
                <div class="school-info">
                    <h2><?= htmlspecialchars($member['school_name'] ?? 'Perpustakaan Digital') ?></h2>
                    <p><?= htmlspecialchars($member['location'] ?? 'Kartu Anggota Resmi') ?></p>
                </div>
            </div>

            <div class="card-body">
                <!-- Profile Section -->
                <div class="student-profile">
                    <img src="<?= $photoSrc ?>" alt="Foto" class="student-photo">
                    <div class="student-details">
                        <div class="student-name"><?= htmlspecialchars($member['name']) ?></div>
                        <div class="student-id-label">Nomor Anggota</div>
                        <div class="student-id"><?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                    </div>
                </div>

                <!-- Barcode Section -->
                <div class="barcode-section">
                    <svg id="barcode"></svg>
                </div>
            </div>

            <div class="card-footer-strip">
                Berlaku selama menjadi siswa aktif di sekolah ini.
            </div>
        </div>

        <div class="actions">
            <!-- Download / Print -->
            <button onclick="window.print()" class="btn btn-primary">
                <iconify-icon icon="mdi:printer"></iconify-icon>
                Cetak Kartu
            </button>
            
            <a href="profil.php" class="btn btn-secondary">
                <iconify-icon icon="mdi:account-edit"></iconify-icon>
                Edit Profil
            </a>
        </div>
    </div>

    <!-- Initialize Barcode -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            try {
                JsBarcode("#barcode", "<?= htmlspecialchars($barcodeValue) ?>", {
                    format: "CODE128",
                    lineColor: "#000",
                    width: 2,
                    height: 50,
                    displayValue: true,
                    font: "monospace",
                    fontSize: 14,
                    marginTop: 10,
                    marginBottom: 10
                });
            } catch (e) {
                console.error("Barcode error:", e);
                document.getElementById('barcode').style.display = 'none';
                // Fallback text
                const div = document.querySelector('.barcode-section');
                div.innerHTML += '<div style="color:red;font-size:12px">Barcode Error: ' + e.message + '</div>';
            }
        });
    </script>
</body>
</html>
