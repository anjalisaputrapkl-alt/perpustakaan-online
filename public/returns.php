<?php
// Periksa autentikasi pengguna
require __DIR__ . '/../src/auth.php';
requireAuth();

// Inisialisasi database dan ambil data user dari session
$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Hitung jumlah peminjaman yang masih aktif (belum dikembalikan)
$stmt = $pdo->prepare(
  'SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status IN ("borrowed", "overdue")'
);
$stmt->execute(['sid' => $sid]);
$activeBorrowsCount = $stmt->fetchColumn();

// Ambil 5 pengembalian terbaru dengan data buku dan peminjam
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
  <!-- Halaman pengembalian buku dengan scanner -->
  <title>Pengembalian Buku</title>
  <!-- Load theme loader script -->
  <script src="../assets/js/theme-loader.js"></script>
  <!-- Load theme styling -->
  <script src="../assets/js/theme.js"></script>
  <!-- Load Google Fonts Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Load icon library -->
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <!-- Load CSS styling -->
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/borrows.css">
  <!-- CSS untuk tabel data di modal -->
  <style>
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    .data-table thead {
      background: color-mix(in srgb, var(--primary), transparent 95%);
      border-bottom: 2px solid var(--primary);
    }

    .data-table thead th {
      padding: 12px 16px;
      text-align: left;
      font-weight: 600;
      color: var(--primary);
    }

    .data-table tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.2s ease;
    }

    .data-table tbody tr:hover {
      background: color-mix(in srgb, var(--primary), transparent 98%);
    }

    .data-table tbody td {
      padding: 12px 16px;
      color: var(--text);
    }

    .modal-loading {
      text-align: center;
      padding: 40px 20px;
      color: var(--muted);
    }
  </style>
  <!-- Load dynamic theme CSS -->
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
  <!-- Load sidebar navigation -->
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <!-- Top navigation bar -->
    <div class="topbar">
      <div class="topbar-title">
        <!-- Page icon dan title -->
        <iconify-icon icon="mdi:keyboard-return" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Pengembalian Buku</strong>
      </div>
    </div>

    <!-- Main content area -->
    <div class="content">
      <div class="main">

        <!-- Scanner Wrapper untuk pengembalian buku -->
        <div>
          <!-- Tombol untuk membuka/tutup scanner -->
          <div class="scanner-toggle-wrap">
            <!-- Tombol untuk toggle mode scanner QR/manual -->
            <button onclick="toggleScanner()" class="btn-barcode-start">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              <span id="scannerToggleText">Mulai Pengembalian</span>
            </button>
          </div>

          <!-- Section untuk scanner barcode dan history -->
          <div id="scannerSection" class="card scanner-section">
            <!-- Grid: scanner di kiri, history di kanan -->
            <div class="scanner-grid">
              <!-- Bagian scanner barcode -->
              <div>
                <!-- Container untuk QR code reader -->
                <div id="reader"></div>
                <!-- Status pesan pemindaian -->
                <div id="scanStatus" style="display:none; margin-bottom: 10px; padding: 10px; border-radius: 8px;">
                </div>

                <!-- Kontrol scanner dengan input manual -->
                <div class="scanner-controls">
                  <!-- Input untuk manual barcode entry atau scanner gun -->
                  <input type="text" id="barcodeInput" placeholder="Ketik/Scan ISBN atau ID..."
                    style="flex: 2; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;">
                  <!-- Tombol untuk proses input manual -->
                  <button class="scanner-mode-btn active" onclick="processManualInput()"
                    style="flex: 1; text-align: center;">
                    <!-- Icon keyboard dan teks proses -->
                    <iconify-icon icon="mdi:keyboard-return"
                      style="vertical-align: -2px; margin-right: 4px;"></iconify-icon> Proses
                  </button>
                </div>
                <!-- Instruksi untuk scanner gun -->
                <div style="font-size: 12px; color: var(--muted); margin-top: 8px;">
                  * Pastikan kursor aktif di kotak input jika menggunakan scanner gun.
                </div>
              </div>

              <!-- Bagian riwayat pengembalian sesi ini -->
              <div>
                <!-- Title riwayat sesi dengan icon -->
                <h2 class="flex-center gap-2">
                  <iconify-icon icon="mdi:history" style="font-size: 20px;"></iconify-icon>
                  Riwayat Sesi Ini
                </h2>

                <!-- Card hasil pengembalian terakhir -->
                <div id="lastReturnCard" style="display: none; margin-bottom: 20px;">
                  <div class="scanned-info-card" style="display: block;">
                    <!-- Header dengan label dan waktu -->
                    <div class="scanned-info-label" style="display: flex; justify-content: space-between;">
                      <span>Buku Berhasil Dikembalikan</span>
                      <span id="resTime" style="font-weight: 400; opacity: 0.8;">-</span>
                    </div>

                    <!-- Judul buku yang dikembalikan -->
                    <div class="scanned-info-value" id="resBookTitle" style="margin-top: 8px;">-</div>
                    <!-- Nama peminjam -->
                    <div class="scanned-info-meta" id="resMemberName">-</div>

                    <!-- Display informasi denda jika ada -->
                    <div id="fineDisplay" style="margin-top: 12px;"></div>
                  </div>
                </div>

                <!-- Tampilan kosong sebelum ada scan -->
                <div id="scanEmptyState" class="scanner-empty-state">
                  <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
                  <p>Scan buku untuk memulai sesi pengembalian</p>
                </div>

                <!-- Tabel riwayat pengembalian sesi ini -->
                <div id="sessionHistory" style="display: none;">
                  <!-- Table wrapper untuk riwayat sesi -->
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

                  <!-- Total pengembalian dalam sesi ini -->
                  <div style="text-align: right; font-size: 13px; color: var(--muted); font-weight: 600;">
                    Total Sesi Ini: <span id="sessionCountBadge"
                      style="color: var(--primary); font-weight: 800;">0</span> Buku
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI Statistics Cards -->
        <div class="stats-grid">
          <!-- KPI Card 1: Buku yang masih dipinjam (Clickable) -->
          <div class="stat-card clickable" data-stat-type="active-borrows" title="Klik untuk melihat detail"
            onclick="openStatsModal('active-borrows')" style="cursor: pointer;">
            <div class="stat-icon blue">
              <iconify-icon icon="mdi:book-clock"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Buku Belum Kembali</div>
              <div class="stat-value"><?= number_format($activeBorrowsCount) ?></div>
            </div>
          </div>
          <!-- KPI Card 2: Pengembalian dalam sesi ini (Clickable) -->
          <div class="stat-card clickable" data-stat-type="session-returns" title="Klik untuk melihat detail"
            onclick="openStatsModal('session-returns')" style="cursor: pointer;">
            <div class="stat-icon green">
              <iconify-icon icon="mdi:check-circle"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Sesi Ini</div>
              <div class="stat-value" id="sessionCount">0</div>
            </div>
          </div>
        </div>

        <!-- Tabel aktivitas pengembalian terbaru -->
        <div class="card">
          <!-- Title tabel -->
          <h2>Aktivitas Pengembalian Terbaru</h2>
          <!-- Table wrapper untuk riwayat pengembalian -->
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
              <!-- Daftar 5 pengembalian terbaru -->
              <tbody id="recentReturnsList">
                <?php foreach ($recentReturns as $r): ?>
                  <!-- Row data pengembalian buku -->
                  <tr>
                    <!-- Nama buku -->
                    <td style="font-weight: 700;"><?= htmlspecialchars($r['title']) ?></td>
                    <!-- Nama peminjam/siswa -->
                    <td><?= htmlspecialchars($r['member_name']) ?></td>
                    <!-- Waktu pengembalian -->
                    <td><?= date('d/m/Y H:i', strtotime($r['returned_at'])) ?></td>
                    <!-- Denda yang dikenakan jika ada -->
                    <td>
                      <?php if ($r['fine_amount'] > 0): ?>
                        <!-- Tampilkan nominal denda jika ada -->
                        <span style="color: var(--danger); font-weight: 700;">Rp
                          <?= number_format($r['fine_amount'], 0, ',', '.') ?></span>
                      <?php else: ?>
                        <!-- Tampilkan nihil jika tidak ada denda -->
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

  <!-- Modal untuk tampilkan detail statistik -->
  <div class="modal-overlay" id="statsModal">
    <!-- Container utama modal dengan header dan body -->
    <div class="modal-container">
      <!-- Header modal dengan judul dan search -->
      <div class="modal-header">
        <!-- Div flex untuk space left dengan judul -->
        <div style="flex: 1;">
          <!-- Judul modal yang berubah sesuai tipe data -->
          <h2 id="modalTitle">Detail Data</h2>
        </div>
        <!-- Search wrapper dengan input dan button -->
        <div class="search-wrapper">
          <!-- Search input field untuk filter data modal -->
          <input type="text" id="searchModal" class="search-input" placeholder="Cari data...">
          <!-- Icon search magnifier -->
          <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
          <!-- Keyboard shortcut indicator (Ctrl K) -->
          <div class="search-kbd">
            <span style="font-size: 8px;">Ctrl</span>
            <span>K</span>
          </div>
          <!-- Clear search button -->
          <button class="search-clear" id="clearModalSearch"><iconify-icon
              icon="mdi:close-circle"></iconify-icon></button>
        </div>
        <!-- Close modal button -->
        <button class="modal-close" type="button" style="margin-left: 20px;">×</button>
      </div>
      <!-- Body modal yang akan di-populate dengan data tabel -->
      <div class="modal-body">
        <!-- Loading indicator saat fetch data -->
        <div class="modal-loading">Memuat data...</div>
      </div>
    </div>
  </div>

  <!-- Audio untuk efek suara -->
  <!-- Success notification sound -->
  <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"
    preload="auto"></audio>
  <!-- Error notification sound -->
  <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>
  <!-- Warning notification sound -->
  <audio id="soundWarning" src="https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3"
    preload="auto"></audio>

  <!-- Load QR code scanner library -->
  <script src="https://unpkg.com/html5-qrcode"></script>
  <!-- Load returns scanner dan handler script -->
  <script src="../assets/js/returns-manage.js"></script>

  <!-- JavaScript untuk menangani modal statistik -->
  <script>
    // Variabel global untuk menyimpan data modal
    let statsModalData = {
      currentType: null,
      allData: [],
      filteredData: []
    };

    // Fungsi untuk membuka modal statistik dengan tipe data tertentu
    function openStatsModal(statType) {
      // Query elemen modal dan body untuk populate
      const modal = document.getElementById('statsModal');
      const modalBody = modal.querySelector('.modal-body');
      const modalTitle = document.getElementById('modalTitle');
      const searchInput = document.getElementById('searchModal');

      // Reset search input ke kosong
      searchInput.value = '';

      // Set tipe data yang sedang ditampilkan
      statsModalData.currentType = statType;

      // Load data berdasarkan tipe statistik yang dipilih
      if (statType === 'active-borrows') {
        // Set judul untuk data buku belum kembali
        modalTitle.textContent = 'Buku Belum Kembali';
        // Load data active borrows dari server
        loadActiveBorrowsData();
      } else if (statType === 'session-returns') {
        // Set judul untuk data pengembalian sesi ini
        modalTitle.textContent = 'Pengembalian Sesi Ini';
        // Load data session returns dari server
        loadSessionReturnsData();
      }

      // Tampilkan modal dengan flex display
      modal.style.display = 'flex';
      // Auto-focus ke search input
      searchInput.focus();
    }

    // Fungsi untuk menutup modal statistik
    function closeStatsModal() {
      // Query elemen modal
      const modal = document.getElementById('statsModal');
      // Hide modal dengan display none
      modal.style.display = 'none';
    }

    // Fungsi untuk load data buku belum kembali dari API
    function loadActiveBorrowsData() {
      // Query modal body element
      const modalBody = document.querySelector('#statsModal .modal-body');

      // Fetch data dari API endpoint
      fetch('api/get-active-borrows.php')
        // Parse response sebagai JSON
        .then(response => response.json())
        // Handle data success
        .then(data => {
          // Cek success flag di response
          if (data.success) {
            // Store all data ke variable global
            statsModalData.allData = data.borrows || [];
            // Set filtered data sama dengan all data (sebelum filter)
            statsModalData.filteredData = statsModalData.allData;
            // Render tabel dengan data
            renderActiveBorrowsTable();
          } else {
            // Tampilkan error jika fetch gagal
            modalBody.innerHTML = '<p style="color: var(--danger); padding: 20px;">Gagal memuat data</p>';
          }
        })
        // Handle fetch error
        .catch(error => {
          // Log error ke console
          console.error('Error:', error);
          // Tampilkan pesan error ke modal
          modalBody.innerHTML = '<p style="color: var(--danger); padding: 20px;">Terjadi kesalahan: ' + error.message + '</p>';
        });
    }

    // Fungsi untuk load data pengembalian dalam sesi ini dari API
    function loadSessionReturnsData() {
      // Query modal body element
      const modalBody = document.querySelector('#statsModal .modal-body');

      // Fetch data dari API endpoint
      fetch('api/get-session-returns.php')
        // Parse response sebagai JSON
        .then(response => response.json())
        // Handle data success
        .then(data => {
          // Cek success flag di response
          if (data.success) {
            // Store all data ke variable global
            statsModalData.allData = data.returns || [];
            // Set filtered data sama dengan all data (sebelum filter)
            statsModalData.filteredData = statsModalData.allData;
            // Render tabel dengan data
            renderSessionReturnsTable();
          } else {
            // Tampilkan error jika fetch gagal
            modalBody.innerHTML = '<p style="color: var(--danger); padding: 20px;">Gagal memuat data</p>';
          }
        })
        // Handle fetch error
        .catch(error => {
          // Log error ke console
          console.error('Error:', error);
          // Tampilkan pesan error ke modal
          modalBody.innerHTML = '<p style="color: var(--danger); padding: 20px;">Terjadi kesalahan: ' + error.message + '</p>';
        });
    }

    // Fungsi untuk render tabel buku belum kembali di modal
    function renderActiveBorrowsTable() {
      // Query modal body element untuk inject HTML
      const modalBody = document.querySelector('#statsModal .modal-body');

      // Cek jika data kosong setelah filter
      if (statsModalData.filteredData.length === 0) {
        // Tampilkan pesan tidak ada data
        modalBody.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--muted);">Tidak ada data</p>';
        return;
      }

      // Build HTML table string
      let html = '<table class="data-table" style="width: 100%;">';
      // Add table header dengan kolom
      html += '<thead><tr><th>Judul Buku</th><th>Peminjam</th><th>Tanggal Pinjam</th><th>Durasi</th><th>Status</th></tr></thead>';
      // Start table body
      html += '<tbody>';

      // Loop setiap item data untuk render row
      statsModalData.filteredData.forEach(item => {
        // Determine status text berdasarkan status value
        const status = item.status === 'overdue' ? 'Terlambat' : 'Dipinjam';
        // Set color status berdasarkan status value
        const statusColor = item.status === 'overdue' ? 'var(--danger)' : 'var(--warning)';
        // Add row dengan data item
        html += `<tr>
          <td>${item.book_title || '-'}</td>
          <td>${item.member_name || '-'}</td>
          <td>${new Date(item.borrowed_at).toLocaleDateString('id-ID')}</td>
          <td>${item.days_borrowed} hari</td>
          <td><span style="color: ${statusColor}; font-weight: 600;">${status}</span></td>
        </tr>`;
      });

      // Close table body dan table tag
      html += '</tbody></table>';
      // Inject HTML ke modal body
      modalBody.innerHTML = html;
    }

    // Fungsi untuk render tabel pengembalian sesi ini di modal
    function renderSessionReturnsTable() {
      // Query modal body element untuk inject HTML
      const modalBody = document.querySelector('#statsModal .modal-body');

      // Cek jika data kosong setelah filter
      if (statsModalData.filteredData.length === 0) {
        // Tampilkan pesan tidak ada data
        modalBody.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--muted);">Tidak ada data pengembalian pada sesi ini</p>';
        return;
      }

      // Build HTML table string
      let html = '<table class="data-table" style="width: 100%;">';
      // Add table header dengan kolom
      html += '<thead><tr><th>Judul Buku</th><th>Peminjam</th><th>Waktu Kembali</th><th>Denda</th></tr></thead>';
      // Start table body
      html += '<tbody>';

      // Loop setiap item data untuk render row
      statsModalData.filteredData.forEach(item => {
        // Format nominal denda atau nihil
        const fineDisplay = item.fine_amount > 0 ? 'Rp ' + parseInt(item.fine_amount).toLocaleString('id-ID') : 'Nihil';
        // Set color denda berdasarkan nominal
        const fineColor = item.fine_amount > 0 ? 'var(--danger)' : 'var(--success)';
        // Format return date dengan locale dan waktu
        const returnDate = new Date(item.returned_at).toLocaleString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        // Add row dengan data item
        html += `<tr>
          <td>${item.book_title || '-'}</td>
          <td>${item.member_name || '-'}</td>
          <td>${returnDate}</td>
          <td><span style="color: ${fineColor}; font-weight: 600;">${fineDisplay}</span></td>
        </tr>`;
      });

      // Close table body dan table tag
      html += '</tbody></table>';
      // Inject HTML ke modal body
      modalBody.innerHTML = html;
    }

    // Fungsi untuk filter data berdasarkan search term
    function filterModalData(searchTerm) {
      // Convert search term ke lowercase untuk case-insensitive search
      const search = searchTerm.toLowerCase();
      // Filter all data berdasarkan search term
      statsModalData.filteredData = statsModalData.allData.filter(item => {
        // Array field yang akan di-search
        const searchFields = [
          (item.book_title || '').toLowerCase(),
          (item.member_name || '').toLowerCase(),
          (item.status || '').toLowerCase()
        ];
        // Return true jika ada field yang match dengan search term
        return searchFields.some(field => field.includes(search));
      });

      // Render ulang tabel berdasarkan tipe data
      if (statsModalData.currentType === 'active-borrows') {
        // Re-render active borrows table
        renderActiveBorrowsTable();
      } else if (statsModalData.currentType === 'session-returns') {
        // Re-render session returns table
        renderSessionReturnsTable();
      }
    }

    // Event listeners untuk modal
    document.addEventListener('DOMContentLoaded', function () {
      // Query modal element
      const modal = document.getElementById('statsModal');
      // Query close button element
      const closeBtn = modal.querySelector('.modal-close');
      // Query search input element
      const searchInput = document.getElementById('searchModal');
      // Query clear search button element
      const clearBtn = document.getElementById('clearModalSearch');

      // Close modal saat tombol X diklik
      closeBtn.addEventListener('click', closeStatsModal);

      // Close modal saat click di overlay (outside modal)
      modal.addEventListener('click', function (e) {
        // Check jika target click adalah modal itu sendiri
        if (e.target === modal) {
          // Close modal
          closeStatsModal();
        }
      });

      // Close modal saat ESC key ditekan
      document.addEventListener('keydown', function (e) {
        // Check jika ESC key pressed dan modal visible
        if (e.key === 'Escape' && modal.style.display === 'flex') {
          // Close modal
          closeStatsModal();
        }
      });

      // Filter data saat user type di search input
      searchInput.addEventListener('input', function (e) {
        // Call filter function dengan search term
        filterModalData(e.target.value);
      });

      // Clear search saat clear button diklik
      clearBtn.addEventListener('click', function () {
        // Reset search input ke kosong
        searchInput.value = '';
        // Reset filter dengan empty string
        filterModalData('');
        // Focus ke search input
        searchInput.focus();
      });

      // Focus search saat Ctrl+K ditekan
      document.addEventListener('keydown', function (e) {
        // Check jika Ctrl+K atau Cmd+K ditekan
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
          // Prevent default behavior
          e.preventDefault();
          // Query modal element
          const modal = document.getElementById('statsModal');
          // Check jika modal visible
          if (modal.style.display === 'flex') {
            // Focus ke search input
            searchInput.focus();
          }
        }
      });
    });
  </script>
</body>

</html>