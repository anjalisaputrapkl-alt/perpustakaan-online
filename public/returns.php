<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get some basic stats for the return page
$stmt = $pdo->prepare(
  'SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status IN ("borrowed", "overdue")'
);
$stmt->execute(['sid' => $sid]);
$activeBorrowsCount = $stmt->fetchColumn();

// Get recent returns
$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, m.name as member_name 
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid AND b.status = "returned"
   ORDER BY b.returned_at DESC LIMIT 5'
);
$stmt->execute(['sid' => $sid]);
$recentReturns = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pengembalian Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/borrows.css">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
  <style>
    /* Specific overrides for returns if needed */
    .scanner-mode-btn {
        flex: 1;
    }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <div class="topbar">
      <div class="topbar-title">
        <iconify-icon icon="mdi:keyboard-return" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Pengembalian Buku</strong>
      </div>
    </div>

    <div class="content">
      <div class="main">
        
        <!-- Scanner Wrapper (Fixes button width issue) -->
        <div>
            <!-- Toggle Button -->
            <div class="scanner-toggle-wrap">
                <button onclick="toggleScanner()" class="btn-barcode-start">
                  <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                  <span id="scannerToggleText">Mulai Pengembalian</span>
                </button>
            </div>

            <!-- Scanner Section (Hidden by default) -->
            <div id="scannerSection" class="card scanner-section">
                <div class="scanner-grid">
                    <!-- Left: Scanner Controls -->
                    <div>
                       <div id="reader"></div>
                       <div id="scanStatus" style="display:none; margin-bottom: 10px; padding: 10px; border-radius: 8px;"></div>
                       
                       <div class="scanner-controls">
                           <input type="text" id="barcodeInput" placeholder="Ketik/Scan ISBN atau ID..." 
                                  style="flex: 2; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;">
                           <button class="scanner-mode-btn active" onclick="processManualInput()" style="flex: 1; text-align: center;">
                               <iconify-icon icon="mdi:keyboard-return" style="vertical-align: -2px; margin-right: 4px;"></iconify-icon> Proses
                           </button>
                       </div>
                       <div style="font-size: 12px; color: var(--muted); margin-top: 8px;">
                           * Pastikan kursor aktif di kotak input jika menggunakan scanner gun.
                       </div>
                    </div>

                    <!-- Right: Result Display -->
                    <div>
                        <h2 class="flex-center gap-2">
                            <iconify-icon icon="mdi:history" style="font-size: 20px;"></iconify-icon>
                            Riwayat Sesi Ini
                        </h2>

                        <!-- Session Info / Stats (Custom to Returns but styled consistently) -->
                        <div id="lastReturnCard" style="display: none; margin-bottom: 20px;">
                            <div class="scanned-info-card" style="display: block;">
                                 <div class="scanned-info-label" style="display: flex; justify-content: space-between;">
                                    <span>Buku Berhasil Dikembalikan</span>
                                    <span id="resTime" style="font-weight: 400; opacity: 0.8;">-</span>
                                 </div>
                                 
                                 <div class="scanned-info-value" id="resBookTitle" style="margin-top: 8px;">-</div>
                                 <div class="scanned-info-meta" id="resMemberName">-</div>
                                 
                                 <div id="fineDisplay" style="margin-top: 12px;"></div>
                            </div>
                        </div>

                        <!-- Intro/Empty State -->
                        <div id="scanEmptyState" class="scanner-empty-state">
                            <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                            <p>Scan buku untuk memulai sesi pengembalian</p>
                        </div>

                        <!-- Session History Table -->
                        <div id="sessionHistory" style="display: none;">
                             <div class="borrows-table-wrap mb-4">
                                <table class="borrows-table">
                                    <thead>
                                        <tr>
                                            <th>Buku</th>
                                            <th>Peminjam</th>
                                            <th style="text-align: right;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sessionReturnsList"></tbody>
                                </table>
                            </div>
                            
                            <div style="text-align: right; font-size: 13px; color: var(--muted); font-weight: 600;">
                                Total Sesi Ini: <span id="sessionCountBadge" style="color: var(--primary); font-weight: 800;">0</span> Buku
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue">
              <iconify-icon icon="mdi:book-clock"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Buku Belum Kembali</div>
              <div class="stat-value"><?= number_format($activeBorrowsCount) ?></div>
            </div>
          </div>
          <div class="stat-card">
             <div class="stat-icon green">
              <iconify-icon icon="mdi:check-circle"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Sesi Ini</div>
              <div class="stat-value" id="sessionCount">0</div>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Aktivitas Pengembalian Terbaru</h2>
          <div class="borrows-table-wrap">
            <table class="borrows-table">
              <thead>
                <tr>
                  <th>Buku</th>
                  <th>Peminjam</th>
                  <th>Waktu Kembali</th>
                  <th>Denda</th>
                </tr>
              </thead>
              <tbody id="recentReturnsList">
                <?php foreach($recentReturns as $r): ?>
                <tr>
                  <td style="font-weight: 700;"><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['member_name']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($r['returned_at'])) ?></td>
                  <td>
                    <?php if($r['fine_amount'] > 0): ?>
                      <span style="color: var(--danger); font-weight: 700;">Rp <?= number_format($r['fine_amount'], 0, ',', '.') ?></span>
                    <?php else: ?>
                      <span style="color: var(--success);">Nihil</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
  <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>
  <audio id="soundWarning" src="https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3" preload="auto"></audio>

  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
    const barcodeInput = document.getElementById('barcodeInput');
    const sessionHistory = document.getElementById('sessionHistory');
    const sessionList = document.getElementById('sessionReturnsList');
    const emptyState = document.getElementById('scanEmptyState');
    const sessionCountEl = document.getElementById('sessionCount');
    const sessionCountBadge = document.getElementById('sessionCountBadge');
    
    let sessionCount = 0;
    let html5QrcodeScanner = null;

    function toggleScanner() {
        const section = document.getElementById('scannerSection');
        const btnText = document.getElementById('scannerToggleText');
        const btn = document.querySelector('.scanner-toggle-wrap button');
        
        if (section.style.display === 'none' || section.style.display === '') {
            section.style.display = 'block';
            btnText.textContent = 'Tutup Pengembalian';
            btn.innerHTML = '<iconify-icon icon="mdi:close"></iconify-icon> <span id="scannerToggleText">Tutup Pengembalian</span>';
            btn.classList.add('btn-danger'); // Optional styling for active state if needed
            startCamera();
        } else {
            section.style.display = 'none';
            btnText.textContent = 'Mulai Pengembalian';
            btn.innerHTML = '<iconify-icon icon="mdi:barcode-scan"></iconify-icon> <span id="scannerToggleText">Mulai Pengembalian</span>';
            btn.classList.remove('btn-danger');
            stopCamera();
        }
    }

    function startCamera() {
        if (html5QrcodeScanner) return;

        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 150 } },
            (decodedText) => {
                const now = Date.now();
                // Simple Debounce
                if(window.lastScan && now - window.lastScan < 2000) return;
                window.lastScan = now;
                
                processReturn(decodedText);
            },
            (error) => {}
        ).catch(err => console.error("Error starting scanner", err));
        
        // Focus input
        setTimeout(() => barcodeInput.focus(), 500);
    }

    function stopCamera() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }).catch(err => console.error(err));
        }
    }

    function processManualInput() {
        const val = barcodeInput.value.trim();
        if (val) {
            processReturn(val);
            barcodeInput.value = '';
        }
    }

    // Auto-focus input
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && document.activeElement === barcodeInput) {
          processManualInput();
      }
    });

    async function processReturn(barcode) {
      showStatus('Memproses...', 'info');

      try {
        const res = await fetch('api/process-return.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({barcode: barcode})
        });
        
        const result = await res.json();
        
        if (result.success) {
          handleSuccess(result.data);
          showStatus('Berhasil: ' + result.data.book_title, 'success');
        } else {
          handleError(result.message);
          showStatus('Gagal: ' + result.message, 'error');
        }
      } catch (e) {
        handleError('Koneksi terputus atau server error');
        showStatus('Error: Koneksi terputus', 'error');
      }
    }

    function showStatus(msg, type) {
        const el = document.getElementById('scanStatus');
        if(el) {
            el.textContent = msg;
            el.style.display = 'block';
            el.style.background = type === 'error' ? 'var(--danger-soft)' : (type === 'success' ? 'var(--success-soft)' : 'var(--info-soft)');
            el.style.color = type === 'error' ? 'var(--danger)' : (type === 'success' ? 'var(--success)' : 'var(--info)');
            
            if(type === 'success') {
                setTimeout(() => { el.style.display = 'none'; }, 3000);
            }
        }
    }

    function handleSuccess(data) {
      if(document.getElementById('soundSuccess')) document.getElementById('soundSuccess').play();
      
      sessionCount++;
      sessionCountEl.textContent = sessionCount;
      if(sessionCountBadge) sessionCountBadge.textContent = sessionCount;
      
      // Update Right Column visibility
      if (sessionCount === 1) {
          emptyState.style.display = 'none';
          sessionHistory.style.display = 'block';
      }
      
      // Create Table Row
      const row = document.createElement('tr');
      row.className = 'fade-in-row'; // Access animations
      row.style.animation = 'slideInRight 0.3s ease-out';
      
      // Fine badge logic
      let statusHtml = '';
      if (data.fine_amount > 0) {
          if(document.getElementById('soundWarning')) document.getElementById('soundWarning').play();
          statusHtml = `
            <div style="text-align: right;">
                <div class="text-danger" style="font-weight: 800; font-size: 13px;">Rp ${data.fine_amount.toLocaleString('id-ID')}</div>
                <div style="font-size: 10px; color: var(--danger); opacity: 0.8;">Terlambat ${data.late_days} Hari</div>
            </div>
          `;
      } else {
          statusHtml = `
            <div style="text-align: right;">
                <div class="text-success" style="font-weight: 700; font-size: 12px;">
                    <iconify-icon icon="mdi:check-circle"></iconify-icon> Tepat Waktu
                </div>
            </div>
          `;
      }

      row.innerHTML = `
        <td>
           <div style="font-weight: 700; color: var(--text); line-height: 1.3;">${data.book_title}</div>
           <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">${new Date().toLocaleTimeString('id-ID')}</div>
        </td>
        <td>
           <div style="font-size: 13px;">${data.member_name}</div>
        </td>
        <td>${statusHtml}</td>
      `;
      
      // Prepend to top
      sessionList.insertBefore(row, sessionList.firstChild);

      // Also add to the "Global" Recent History Table at the bottom
      const globalList = document.getElementById('recentReturnsList');
      if (globalList) {
          const globalRow = document.createElement('tr');
          globalRow.style.animation = 'fadeIn 0.5s ease';
          globalRow.innerHTML = `
            <td style="font-weight: 700;">${data.book_title}</td>
            <td>${data.member_name}</td>
            <td>${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</td>
            <td>${data.fine_amount > 0 ? `<span style="color: var(--danger); font-weight: 700;">Rp ${data.fine_amount.toLocaleString('id-ID')}</span>` : `<span style="color: var(--success);">Nihil</span>`}</td>
          `;
          globalList.insertBefore(globalRow, globalList.firstChild);
          if(globalList.children.length > 10) globalList.removeChild(globalList.lastChild);
      }
    }

    function handleError(msg) {
        if(document.getElementById('soundError')) document.getElementById('soundError').play();
        // Option: could show a toast or alert
        console.error(msg);
    }
  </script>
</body>
</html>
