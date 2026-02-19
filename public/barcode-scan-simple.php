<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemindai Barcode</title>
    <script>
        if (window.innerWidth <= 768) {
            window.location.href = 'scan-mobile.php';
        }
    </script>
    <link rel="stylesheet" href="../assets/css/barcode-scan-simple-style.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <div class="container">

        <div class="info-text">
            Arahkan kamera ke barcode untuk memulai pemindaian
        </div>

        <!-- QR Reader -->
        <div id="reader" class="reader"></div>

        <!-- Status Message -->
        <div id="statusMessage" class="status-message info" style="display: none;"></div>

        <!-- Mode Indicator -->
        <div class="mode-indicator">
            <div class="mode-text">Mode Pemindaian</div>
            <div class="mode-buttons">
                <button class="mode-btn active" id="btnModeBook">Scan Buku</button>
                <button class="mode-btn inactive" id="btnModeMember">Scan Anggota</button>
            </div>
        </div>

        <!-- Member Display -->
        <div class="member-display" id="memberDisplay">
            <div class="member-label">Anggota Terpilih</div>
            <div class="member-info">
                <span id="memberName"></span> (NISN: <span id="memberNisn"></span>)
            </div>
        </div>

        <!-- Scan Count -->
        <div class="scan-count" id="scanCount" style="display: none;">
            Buku yang di-scan: <strong id="bookCount">0</strong>
        </div>

        <!-- Scanned Items List -->
        <div class="scanned-items" id="scannedItems" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">Cover</th>
                        <th>Judul Buku</th>
                        <th style="width: 50px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="scannedItemsList">
                </tbody>
            </table>
        </div>

        <!-- Action Buttons -->
        <div class="btn-group" id="actionButtons" style="display: none;">
            <button class="btn-primary" id="btnSubmit" onclick="submitScannedBooks()">
                Pinjam
            </button>
            <button class="btn-secondary" id="btnClear" onclick="clearScannedBooks()">
                Hapus Semua
            </button>
        </div>

        <!-- Date Picker Removed -->


        <!-- Instruction -->
        <div class="instruction">
            1. Scan Buku <br>
            2. Scan Anggota <br>
            3. Tekan Pinjam
        </div>

        <!-- Logout Button -->
        <div style="margin-top: 24px;">
            <button class="btn btn-danger" id="btnLogout">
                Logout
            </button>
        </div>
    </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses...</p>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="../assets/js/barcode-scan-simple-manage.js"></script>
</body>

</html>