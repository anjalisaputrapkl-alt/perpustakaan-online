<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all members
$stmt = $pdo->prepare('
    SELECT id, name, nisn FROM members 
    WHERE school_id = :sid AND status = "active"
    ORDER BY name ASC
');
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode Anggota</title>
    <link rel="stylesheet" href="../assets/css/barcode-member-print-style.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏷️ Print Barcode Anggota</h1>
            <p style="color: #666; font-size: 14px;">Pilih anggota untuk mencetak barcode NISN</p>
        </div>

        <div class="button-group">
            <button class="print-btn" onclick="window.print()">🖨️ Cetak</button>
            <button class="secondary" onclick="history.back()">← Kembali</button>
        </div>

        <div class="checkbox-group">
            <button class="select-all-btn" onclick="selectAll()">Pilih Semua</button>
            <button class="select-all-btn" onclick="deselectAll()">Batal Pilih</button>
            <span style="margin-left: auto; color: #666;">
                <strong id="selectedCount">0</strong> dipilih
            </span>
        </div>

        <div class="checkbox-group">
            <?php foreach ($members as $member): ?>
                <div class="checkbox-item">
                    <input type="checkbox" class="member-checkbox" value="<?php echo htmlspecialchars($member['id']); ?>"
                        data-nisn="<?php echo htmlspecialchars($member['nisn']); ?>"
                        data-name="<?php echo htmlspecialchars($member['name']); ?>" onchange="updateDisplay()">
                    <label><?php echo htmlspecialchars($member['name']); ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="barcode-grid" id="barcodeContainer">
            <p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">
                Pilih anggota di atas untuk menampilkan barcode
            </p>
        </div>
    </div>

    <!-- JsBarcode CDN -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="../assets/js/barcode-member-print-manage.js"></script>
</body>

</html>