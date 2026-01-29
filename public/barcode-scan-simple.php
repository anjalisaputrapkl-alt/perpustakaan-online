<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemindai Barcode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }

        .container {
            width: 100%;
            max-width: 500px;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 24px;
            padding: 16px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #1a1a1a;
        }

        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background-color: #f8f9ff;
        }

        .reader {
            width: 100%;
            height: 350px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .info-text {
            font-size: 13px;
            color: #666;
            margin-bottom: 16px;
            padding: 12px;
            background: #f0f8ff;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }

        .status-message {
            font-size: 13px;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            text-align: center;
            font-weight: 500;
        }

        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .btn {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #e0e0e0;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
        }

        .scanned-items {
            margin-top: 24px;
            padding: 16px;
            background: #f9f9f9;
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
        }

        .scanned-items h3 {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .scanned-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #667eea;
        }

        .scanned-item:last-child {
            margin-bottom: 0;
        }

        .scanned-item .item-name {
            font-weight: 500;
            color: #333;
        }

        .scanned-item .item-code {
            font-size: 12px;
            color: #999;
        }

        .empty-message {
            text-align: center;
            padding: 24px;
            color: #999;
            font-size: 13px;
        }

        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-remove:hover {
            background: #dc2626;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-overlay p {
            color: white;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📖 Pemindai Barcode</h1>
            <p>Sistem Perpustakaan Sekolah</p>
        </div>

        <div class="card">
            <!-- Scanner Section -->
            <h2>Pemindai Barcode</h2>

            <div class="info-text">
                ✓ Arahkan kamera ke barcode anggota atau buku untuk memulai pemindaian
            </div>

            <!-- QR Reader -->
            <div id="reader" class="reader"></div>

            <!-- Status Message -->
            <div id="statusMessage" class="status-message info" style="display: none;"></div>

            <!-- Barcode Input (Hidden - auto filled by scanner) -->
            <input type="hidden" id="scannedBarcode">

            <!-- Debug Panel -->
            <div
                style="margin-bottom: 16px; padding: 12px; background: #e8f4f8; border-radius: 6px; border-left: 4px solid #0c5460;">
                <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 8px;">
                    🔧 Debug Panel - Nilai Scan Terakhir:
                </label>
                <div id="debugPanel"
                    style="background: white; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 12px; color: #666; min-height: 30px; word-break: break-all;">
                    Menunggu scan...
                </div>
            </div>

            <!-- Manual Input Option -->
            <div
                style="margin-bottom: 16px; padding: 12px; background: #ffffcc; border-radius: 6px; border-left: 4px solid #ffa500;">
                <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 8px;">
                    🔍 Input Manual (Jika Kamera Tidak Bekerja):
                </label>
                <input type="text" id="manualBarcode" class="input-field" placeholder="Masukkan barcode manual"
                    style="margin-bottom: 8px;">
                <button class="btn btn-secondary" id="btnManualSubmit">
                    Proses Manual
                </button>
            </div>

            <!-- Scanned Items Section -->
            <div class="scanned-items">
                <h3>📋 Item Terpindai</h3>
                <div id="scannedList">
                    <p class="empty-message">Belum ada item yang dipindai</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 24px;">
                <button class="btn btn-secondary" id="btnClear">
                    🗑️ Hapus Semua
                </button>
                <button class="btn btn-primary" id="btnSubmit">
                    ✓ Selesai & Kirim Data
                </button>
                <button class="btn btn-danger" id="btnLogout">
                    🚪 Logout
                </button>
            </div>
        </div>
    </div>

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
        let scannedItems = [];
        let lastScannedTime = 0;
        const SCAN_DELAY = 1000; // Prevent duplicate scans

        // ============================================================================
        // Initialize Scanner
        // ============================================================================

        function initScanner() {
            console.log('[INIT] Starting scanner initialization...');

            const readerDiv = document.getElementById('reader');

            scanner = new Html5Qrcode("reader");

            const qrConfig = {
                fps: 10,
                qrbox: 250
            };

            scanner.start(
                { facingMode: "environment" },
                qrConfig,
                onScanSuccess,
                onScanError
            ).then(() => {
                console.log('[SCANNER] ✓ Camera started successfully');
                showStatus('✓ Kamera aktif - siap memindai barcode', 'info');
            }).catch(err => {
                console.error('[SCANNER] ✗ Error:', err);
                showStatus('⚠️ Gagal mengakses kamera. Coba gunakan input manual.', 'error');
            });
        }

        function onScanSuccess(text) {
            const now = Date.now();

            // Update debug panel
            const debugPanel = document.getElementById('debugPanel');
            debugPanel.innerHTML = `<strong>✓ Terdeteksi:</strong> "${text}" (${text.length} karakter)`;

            console.log('[SCAN] ✓ Barcode detected:', text);
            console.log('[SCAN] Length:', text.length);
            console.log('[SCAN] Type:', typeof text);
            console.log('[SCAN] Trimmed:', text.trim());

            // Prevent duplicate scans too quickly
            if (now - lastScannedTime < SCAN_DELAY) {
                console.log('[SCAN] Duplicate scan ignored (too quick)');
                return;
            }

            lastScannedTime = now;
            processBarcode(text.trim());
        }

        function onScanError(error) {
            // Silently ignore - scanner keeps trying
        }

        // ============================================================================
        // Parse Barcode - Extract value from formats like "NISN:0094234" or "ISBN:982384"
        // ============================================================================

        function parseBarcode(rawBarcode) {
            // Try to extract from formatted patterns
            // Patterns: "NISN:0094234", "ISBN:982384", "ID:123", etc.
            const patterns = [
                /^(?:NISN|nisn|ID|id)[:=]?(.+)$/,  // NISN:0094234 or ID:123
                /^(?:ISBN|isbn)[:=]?(.+)$/,         // ISBN:982384
                /^[\*=](.+)[\*=]$/                  // *0094234* or =0094234=
            ];

            for (let pattern of patterns) {
                const match = rawBarcode.match(pattern);
                if (match && match[1]) {
                    return match[1].trim();
                }
            }

            // If no pattern matched, return as-is
            return rawBarcode.trim();
        }

        // ============================================================================
        // Process Barcode
        // ============================================================================

        async function processBarcode(barcode) {
            if (!barcode) return;

            // Parse barcode to extract actual value
            const parsedBarcode = parseBarcode(barcode);
            console.log('[PARSE] Original:', barcode);
            console.log('[PARSE] Parsed:', parsedBarcode);

            showLoading(true);
            showStatus('Memproses barcode...', 'info');

            try {
                console.log('[API] Sending barcode:', parsedBarcode);

                // Send barcode to server
                const response = await fetch('./api/process-barcode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        barcode: parsedBarcode
                    })
                });

                const data = await response.json();
                console.log('[API] Response:', data);
                console.log('[API] HTTP Status:', response.status);

                if (!data.success) {
                    const msg = `❌ Barcode "${parsedBarcode}" tidak ditemukan. ${data.message ? '(' + data.message + ')' : ''}`;
                    showStatus(msg, 'error');
                    console.log('[API] Error response:', data);
                    showLoading(false);
                    return;
                }

                // Add to scanned items
                addScannedItem(data.data);
                showStatus('✓ ' + (data.data.name || 'Item') + ' berhasil dipindai', 'success');

            } catch (error) {
                console.error('[ERROR]', error);
                showStatus('❌ Error: ' + error.message, 'error');
            }

            showLoading(false);
        }

        // ============================================================================
        // Scanned Items Management
        // ============================================================================

        function addScannedItem(item) {
            scannedItems.push(item);
            updateScannedList();
            console.log('[ITEM] Added:', item);
        }

        function updateScannedList() {
            const scannedList = document.getElementById('scannedList');

            if (scannedItems.length === 0) {
                scannedList.innerHTML = '<p class="empty-message">Belum ada item yang dipindai</p>';
                return;
            }

            scannedList.innerHTML = scannedItems.map((item, index) => `
                <div class="scanned-item">
                    <div>
                        <div class="item-name">${escapeHtml(item.name || item.title || 'Unknown')}</div>
                        <div class="item-code">Barcode: ${escapeHtml(item.barcode)}</div>
                    </div>
                    <button class="btn-remove" onclick="removeItem(${index})">Hapus</button>
                </div>
            `).join('');
        }

        function removeItem(index) {
            scannedItems.splice(index, 1);
            updateScannedList();
            console.log('[ITEM] Removed index:', index);
        }

        function clearAll() {
            if (!confirm('Hapus semua item yang dipindai?')) return;
            scannedItems = [];
            updateScannedList();
            showStatus('✓ Semua item telah dihapus', 'success');
        }

        // ============================================================================
        // Form Submission
        // ============================================================================

        async function submitData() {
            if (scannedItems.length === 0) {
                alert('Belum ada item yang dipindai. Silakan pindai minimal 1 barcode.');
                return;
            }

            if (!confirm(`Submit ${scannedItems.length} item yang telah dipindai?`)) {
                return;
            }

            showLoading(true);
            showStatus('Mengirim data...', 'info');

            try {
                const response = await fetch('./api/submit-scan-data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        items: scannedItems
                    })
                });

                const data = await response.json();
                console.log('[SUBMIT] Response:', data);

                if (!data.success) {
                    showStatus('❌ ' + (data.message || 'Gagal mengirim data'), 'error');
                    showLoading(false);
                    return;
                }

                showStatus('✓ Data berhasil dikirim!', 'success');
                alert('Pemindaian berhasil! Total ' + scannedItems.length + ' item telah diproses.');

                // Reset
                setTimeout(() => {
                    location.reload();
                }, 2000);

            } catch (error) {
                console.error('[ERROR]', error);
                showStatus('❌ Error: ' + error.message, 'error');
            }

            showLoading(false);
        }

        function handleLogout() {
            if (!confirm('Logout dari sistem?')) return;
            if (scanner) {
                scanner.stop();
            }
            location.href = './logout.php';
        }

        // ============================================================================
        // UI Helpers
        // ============================================================================

        function showStatus(message, type = 'info') {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.className = 'status-message ' + type;
            statusDiv.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 4000);
            }
        }

        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.toggle('show', show);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ============================================================================
        // Event Listeners
        // ============================================================================

        document.getElementById('btnManualSubmit').addEventListener('click', () => {
            const barcode = document.getElementById('manualBarcode').value.trim();
            if (!barcode) {
                alert('Masukkan barcode terlebih dahulu');
                return;
            }
            document.getElementById('manualBarcode').value = '';
            processBarcode(barcode);
        });

        document.getElementById('manualBarcode').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('btnManualSubmit').click();
            }
        });

        document.getElementById('btnClear').addEventListener('click', clearAll);
        document.getElementById('btnSubmit').addEventListener('click', submitData);
        document.getElementById('btnLogout').addEventListener('click', handleLogout);

        // ============================================================================
        // Page Initialization
        // ============================================================================

        window.addEventListener('load', () => {
            console.log('[PAGE] Loading complete');
            initScanner();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (scanner) {
                scanner.stop();
            }
        });
    </script>
</body>

</html>