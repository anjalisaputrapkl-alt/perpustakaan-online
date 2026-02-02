<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Mobile</title>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        /* Modern Immersive Scanner Design */
        :root {
            --primary: #3A7FF2;
            --success: #00C853;
            --danger: #FF3D00;
            --dark-overlay: rgba(0, 0, 0, 0.6);
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #121212; /* Dark theme */
            color: white;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .scanner-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        /* Fullscreen Reader */
        #reader {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
            background: black;
        }
        
        #reader video {
            object-fit: cover !important;
            width: 100% !important;
            height: 100% !important;
        }

        /* UI Layer over camera */
        .ui-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            display: flex;
            flex-direction: column;
            pointer-events: none; /* Let clicks pass through to potential controls */
        }

        /* Top Bar */
        .top-bar {
            padding: 20px;
            padding-top: max(20px, env(safe-area-inset-top));
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: auto;
        }

        .app-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Scanner Overlay & Laser */
        .scan-region {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }



        /* Controls Area */
        .controls-area {
            background: #1e1e1e;
            padding: 24px;
            padding-bottom: max(24px, env(safe-area-inset-bottom));
            border-radius: 24px 24px 0 0;
            pointer-events: auto;
            max-height: 45vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; /* Ensure z-index works */
            z-index: 50; /* Higher than scanner layers */
        }

        .mode-switch {
            display: flex;
            background: #333;
            border-radius: 100px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .mode-btn {
            flex: 1;
            background: transparent;
            border: none;
            color: #aaa;
            padding: 10px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .mode-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(58, 127, 242, 0.4);
        }

        /* Scanned List Mini-View */
        .scanned-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            min-height: 0; /* Allow shrinking */
            scrollbar-width: none; /* Firefox */
        }
        
        .scanned-list::-webkit-scrollbar {
            display: none;
        }
        
        .empty-placeholder {
             text-align: center;
             color: #666;
             font-size: 13px;
             margin-top: 20px;
        }

        .scanned-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #2a2a2a;
            border-radius: 12px;
            margin-bottom: 10px;
            animation: slideUp 0.3s ease;
            border: 1px solid #333;
        }

        .item-cover {
            width: 40px;
            height: 56px;
            background: #444;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 12px;
        }

        .item-info {
            flex: 1;
            overflow: hidden;
        }

        .item-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .item-meta {
            font-size: 11px;
            color: #aaa;
        }

        .item-remove {
            background: rgba(255, 82, 82, 0.1);
            border: none;
            color: #FF5252;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 8px;
        }

        .action-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
        }

        .btn-main {
            background: var(--success);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 200, 83, 0.3);
            transition: transform 0.1s;
        }
        
        .btn-main:active {
            transform: scale(0.98);
        }

        .btn-clear {
            background: #333;
            color: white;
            border: none;
            width: 52px;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Status Toast */
        .toast {
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: rgba(0,0,0,0.85);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 0;
            white-space: nowrap;
            z-index: 10;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(8px);
        }
        
        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.error { background: rgba(255, 61, 0, 0.9); }
        .toast.success { background: rgba(0, 200, 83, 0.9); }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Member Badge */
        .member-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 13px;
            font-weight: 600;
            display: none;
        }
        
        .member-badge.active {
            display: flex;
            background: rgba(0, 200, 83, 0.2);
            border-color: rgba(0, 200, 83, 0.5);
            color: #69f0ae;
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(4px);
        }

        /* Loading Overlay Adjusted */
        .loading-overlay {
            background: rgba(0,0,0,0.85) !important;
            backdrop-filter: blur(5px);
        }
    </style>
</head>

<body>
    <div class="scanner-container">
        <!-- QR Reader -->
        <div id="reader"></div>

        <!-- UI Layer -->
        <div class="ui-layer">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="app-title">
                    <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                    Scanner
                </div>
                <!-- Member Badge -->
                <div class="member-badge" id="memberBadge">
                    <iconify-icon icon="mdi:account"></iconify-icon>
                    <span id="badgeName"></span>
                </div>
                
                <button class="logout-btn" onclick="document.getElementById('logoutForm').submit()">
                    <iconify-icon icon="mdi:logout"></iconify-icon>
                </button>
            </div>

            <!-- Scanning Region -->
            <div class="scan-region">

                <!-- Toast Message -->
                <div class="toast" id="toastMessage"></div>
            </div>

            <!-- Controls -->
            <div class="controls-area">
                <div class="mode-switch">
                    <button class="mode-btn active" id="btnModeBook" onclick="switchMode('book')">Scan Buku</button>
                    <button class="mode-btn" id="btnModeMember" onclick="switchMode('member')">Scan Anggota</button>
                </div>

                <div class="scanned-list" id="scannedListMini">
                    <div class="empty-placeholder">Belum ada buku discan</div>
                </div>

                <div class="action-bar" id="actionBar" style="display:none">
                    <button class="btn-main" id="btnSubmit" onclick="submitScannedBooks()">
                        <iconify-icon icon="mdi:check-circle-outline" style="font-size: 20px;"></iconify-icon>
                        Pinjam (<span id="btnCount">0</span>)
                    </button>
                    <button class="btn-clear" onclick="clearScannedBooks()">
                        <iconify-icon icon="mdi:delete-outline"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden logout form -->
    <form id="logoutForm" action="logout.php" method="POST" style="display: none;"></form>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses...</p>
    </div>

    <!-- Html5 QRCode Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // ============================================================================
        // Global Variables
        // ============================================================================

        let scanner = null;
        let scanMode = 'book'; // Default mode Book
        let currentMember = null;
        let scannedBooks = []; // Array to store scanned books
        let lastScannedTime = 0;
        const SCAN_DELAY = 1500;
        let toastTimeout = null;

        // ============================================================================
        // Initialize Scanner
        // ============================================================================

        function initScanner() {
            console.log('[INIT] Starting scanner...');
            scanner = new Html5Qrcode("reader");

            scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 280, height: 130 } }, // Rectangular box for barcodes
                onScanSuccess,
                onScanError
            ).then(() => {
                console.log('[SCANNER] Started');
                showToast('Siap memindai', 'info');
            }).catch(err => {
                console.error('[SCANNER] Error:', err);
                showToast('Gagal mengakses kamera', 'error');
            });
        }

        function onScanSuccess(text) {
            const now = Date.now();
            if (now - lastScannedTime < SCAN_DELAY) return;
            lastScannedTime = now;

            // Visual feedback


            processBarcode(text.trim());
        }

        function onScanError(error) {
            // Silently ignore
        }

        // ============================================================================
        // Logic
        // ============================================================================

        function parseBarcode(rawBarcode) {
            const patterns = [
                /^(?:NISN|nisn|ID|id)[:=]?(.+)$/,
                /^(?:ISBN|isbn)[:=]?(.+)$/,
                /^[\*=](.+)[\*=]$/
            ];

            for (let pattern of patterns) {
                const match = rawBarcode.match(pattern);
                if (match && match[1]) return match[1].trim();
            }
            return rawBarcode.trim();
        }

        async function processBarcode(barcode) {
            const parsedBarcode = parseBarcode(barcode);
            console.log('[PROCESS]', parsedBarcode, scanMode);

            showLoading(true);

            try {
                const response = await fetch('./api/process-barcode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ barcode: parsedBarcode })
                });

                const data = await response.json();

                if (!data.success) {
                    showToast('Data tidak ditemukan', 'error');
                    showLoading(false);
                    return;
                }

                // Handle member scan
                if (scanMode === 'member') {
                     if (data.data.type !== 'member') {
                        // Intelligent auto-switch
                        if (data.data.type === 'book') {
                            showToast('Buku terdeteksi. Mode: Buku', 'info');
                            switchMode('book');
                            processBarcode(barcode);
                            return;
                        }
                        showToast('Bukan kartu anggota!', 'error');
                        showLoading(false);
                        return;
                    }
                    currentMember = data.data;
                    updateMemberUI();
                    
                    if (scannedBooks.length > 0) {
                        showToast(`Anggota: ${currentMember.name}`, 'success');
                    } else {
                        showToast(`Hai ${currentMember.name.split(' ')[0]}. Scan buku sekarang.`, 'success');
                        switchMode('book');
                    }
                }
                // Handle book scan
                else if (scanMode === 'book') {
                     if (data.data.type !== 'book') {
                         // Intelligent auto-switch
                        if (data.data.type === 'member') {
                            showToast('Kartu anggota terdeteksi', 'info');
                            switchMode('member');
                            processBarcode(barcode);
                            return;
                        }
                        showToast('Bukan kode buku!', 'error');
                        showLoading(false);
                        return;
                    }

                    if (scannedBooks.some(b => b.book_id === data.data.id)) {
                        showToast('Buku sudah ada', 'error');
                    } else {
                        scannedBooks.push({
                            book_id: data.data.id,
                            book_title: data.data.name,
                            cover_image: data.data.cover_image
                        });
                        updateScannedList();
                        showToast('Buku ditambahkan', 'success');

                        if (!currentMember) {
                           // Stay in book mode 
                        }
                    }
                }

            } catch (error) {
                console.error(error);
                showToast('Error koneksi', 'error');
            }

            showLoading(false);
        }

        async function submitScannedBooks() {
            if (scannedBooks.length === 0) return;

            if (!currentMember) {
                showToast('Scan kartu anggota dulu!', 'error');
                switchMode('member');
                return;
            }

            showLoading(true);

            try {
                const response = await fetch('./api/submit-borrow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        borrows: scannedBooks.map(book => ({
                            member_id: currentMember.id,
                            book_id: book.book_id
                        }))
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Peminjaman Berhasil!', 'success');
                    scannedBooks = [];
                    updateScannedList();
                    currentMember = null;
                    updateMemberUI();
                    switchMode('book');
                } else {
                    showToast(data.message || 'Gagal', 'error');
                }
            } catch (error) {
                showToast('Error koneksi', 'error');
            }

            showLoading(false);
        }

        // ============================================================================
        // UI Helpers
        // ============================================================================

        function switchMode(mode) {
            scanMode = mode;
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(mode === 'book' ? 'btnModeBook' : 'btnModeMember').classList.add('active');
            
            // Optional: Change laser color or UI hint?
        }

        function updateMemberUI() {
            const badge = document.getElementById('memberBadge');
            const nameEl = document.getElementById('badgeName');
            
            if (currentMember) {
                nameEl.textContent = currentMember.name;
                badge.classList.add('active');
            } else {
                badge.classList.remove('active');
            }
        }

        function updateScannedList() {
            const container = document.getElementById('scannedListMini');
            const actionBar = document.getElementById('actionBar');
            
            if (scannedBooks.length === 0) {
                container.innerHTML = '<div class="empty-placeholder">Belum ada buku discan</div>';
                actionBar.style.display = 'none';
                return;
            }

            actionBar.style.display = 'grid';
            document.getElementById('btnCount').textContent = scannedBooks.length;

            container.innerHTML = scannedBooks.map((book, index) => `
                <div class="scanned-item">
                    ${book.cover_image 
                      ? `<img src="../img/covers/${escapeHtml(book.cover_image)}" class="item-cover">`
                      : `<div class="item-cover" style="display:flex;align-items:center;justify-content:center;font-size:10px;color:#888;">NoImg</div>`
                    }
                    <div class="item-info">
                        <div class="item-title">${escapeHtml(book.book_title)}</div>
                        <div class="item-meta">Tap hapus untuk membatalkan</div>
                    </div>
                    <button class="item-remove" onclick="removeBook(${index})">
                        <iconify-icon icon="mdi:close"></iconify-icon>
                    </button>
                </div>
            `).join('');
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        function removeBook(index) {
            scannedBooks.splice(index, 1);
            updateScannedList();
        }

        function clearScannedBooks() {
            if(confirm('Hapus semua?')) {
                scannedBooks = [];
                updateScannedList();
            }
        }

        function showToast(msg, type) {
            const toast = document.getElementById('toastMessage');
            toast.textContent = msg;
            toast.className = 'toast show ' + type;
            
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function showLoading(show) {
            const el = document.getElementById('loadingOverlay');
            if (show) el.classList.add('show');
            else el.classList.remove('show');
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Start
        window.addEventListener('load', initScanner);

    </script>
</body>
</html>