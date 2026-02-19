<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all books
$stmt = $pdo->prepare('
    SELECT id, title, isbn, author FROM books 
    WHERE school_id = :sid
    ORDER BY title ASC
');
$stmt->execute(['sid' => $sid]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode Buku</title>
    <link rel="stylesheet" href="../assets/css/barcode-book-print-style.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📚 Print Barcode Buku</h1>
            <p style="color: #666; font-size: 14px;">Pilih buku untuk mencetak barcode ISBN</p>
        </div>

        <div class="button-group">
            <button class="print-btn" onclick="window.print()">🖨️ Cetak</button>
            <button class="secondary" onclick="history.back()">← Kembali</button>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <div class="search-box">
                <input type="text" id="searchBox" placeholder="Cari buku..." oninput="filterBooks()">
            </div>
            <button class="select-all-btn" onclick="selectAll()">Pilih Semua</button>
            <button class="select-all-btn" onclick="deselectAll()">Batal Pilih</button>
            <span style="display: flex; align-items: center; color: #666;">
                <strong id="selectedCount">0</strong> dipilih
            </span>
        </div>

        <div class="checkbox-group" id="bookList">
            <?php foreach ($books as $book): ?>
                <div class="checkbox-item">
                    <input type="checkbox" class="book-checkbox" value="<?php echo htmlspecialchars($book['id']); ?>"
                        data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                        data-title="<?php echo htmlspecialchars($book['title']); ?>"
                        data-author="<?php echo htmlspecialchars($book['author'] ?? ''); ?>" onchange="updateDisplay()">
                    <label><?php echo htmlspecialchars($book['title']); ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="barcode-grid" id="barcodeContainer">
            <p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">
                Pilih buku di atas untuk menampilkan barcode
            </p>
        </div>
    </div>

    <!-- JsBarcode CDN -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="../assets/js/barcode-book-print-manage.js"></script>
</body>

</html>