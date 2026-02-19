<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/BarcodeModel.php';

$school_id = $_SESSION['user']['school_id'] ?? null;
$user_id = $_SESSION['user']['id'] ?? null;

if (!$school_id) {
    header('Location: index.php');
    exit;
}

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Barcode - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="stylesheet" href="../assets/css/generate-barcode-style.css">
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar">
            <strong>Barcode Buku</strong>

        </div>

        <div class="content">
            <div class="section-card">
                <div class="search-title">
                    <iconify-icon icon="mdi:qrcode-plus" style="color: var(--accent); font-size: 24px;"></iconify-icon>
                    Pusat Barcode Buku
                </div>
                <p class="search-description">
                    Kelola label dan barcode untuk seluruh koleksi buku. Siap membantu mencetak label barcode.<br>
                    Ketik minimal 2 karakter untuk melihat hasil.
                </p>
                <div class="search-input-wrapper">
                    <iconify-icon icon="lucide:search" class="search-icon"></iconify-icon>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input"
                        placeholder="Cari berdasarkan judul, kode buku, atau penulis..."
                        autocomplete="off"
                    >
                </div>
                <div style="margin-top: 16px;">
                    <button class="btn-generate" onclick="generateAll()" style="width: 100%; height: 48px; font-size: 14px; border-radius: 10px; box-shadow: none;">
                        <iconify-icon icon="mdi:qrcode-scan" style="font-size: 20px;"></iconify-icon>
                        Generate Barcode untuk Seluruh Koleksi Buku
                    </button>
                </div>
            </div>

            <div id="searchResultsWrapper" style="display: none; margin-top: 32px;">
                <div class="search-title" style="margin-bottom: 16px;">
                    <iconify-icon icon="mdi:format-list-bulleted" style="color: var(--accent); font-size: 22px;"></iconify-icon>
                    Hasil Pencarian
                </div>
                <div class="select-all-wrapper" style="margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px; background: var(--surface); padding: 12px 16px; border-radius: 10px; border: 1px solid var(--border);">
                        <input type="checkbox" id="selectAllBooks" class="checkbox-custom" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="selectAllBooks" style="font-size: 14px; font-weight: 600; cursor: pointer; color: var(--text);">Pilih Semua Buku</label>
                    </div>
                </div>
                <div id="searchResults" class="search-results">
                    <!-- Results will be populated here -->
                </div>
            </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:book-search-outline"></iconify-icon>
                    </div>
                    <p style="font-weight: 600; font-size: 16px;">Siap membantu mencetak label barcode.</p>
                    <p style="font-size: 14px; margin-top: 4px;">Ketik minimal 2 karakter untuk melihat hasil.</p>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <div id="bulkActionBar" class="bulk-action-bar">
                <span class="bulk-info-text"><span id="selectedCount">0</span> buku dipilih</span>
                <button class="btn-bulk-generate" onclick="generateBulk()">
                    <iconify-icon icon="mdi:qrcode-plus"></iconify-icon>
                    Generate Barcode (<span id="btnSelectedCount">0</span>)
                </button>
                <button onclick="clearSelection()" style="background:none; border:none; color:#ffb0b0; cursor:pointer; font-size:13px; font-weight:600;">Batal</button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="barcodeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Preview Barcode</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <div id="modalPreviewBody">
                <div class="barcode-preview-card">
                    <div class="barcode-book-info">
                        <div class="barcode-book-title" id="modalTitle">-</div>
                        <span class="barcode-book-code" id="modalCode">-</span>
                        <div style="margin-top: 12px; font-size: 13px; color: var(--muted);" id="modalAuthor">-</div>
                    </div>

                    <div class="barcode-container">
                        <img id="barcodeImage" style="max-width: 100%; height: auto;" src="" alt="Barcode">
                    </div>
                    
                    <div style="font-size: 11px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">Barcode Label Preview</div>
                </div>
            </div>
            
            <div id="modalBulkBody" class="barcode-grid" style="display: none; padding: 10px; background: var(--bg); border-radius: 20px;">
                <!-- Bulk entries will go here -->
            </div>

            <div class="modal-actions">
                <button class="btn-modal btn-download" onclick="downloadBarcodes()">
                    <iconify-icon icon="mdi:download"></iconify-icon>
                    Download PNG
                </button>
                <button class="btn-modal btn-print" onclick="printBarcodes()">
                    <iconify-icon icon="mdi:printer"></iconify-icon>
                    Cetak
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/generate-barcode-manage.js"></script>
</body>

</html>
