<?php
// Periksa autentikasi pengguna
require __DIR__ . '/../src/auth.php';
requireAuth();

// Inisialisasi database dan ambil data user dari session
$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Tangani proses pengembalian buku dengan transaksi database
if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['id'])) {
  $pdo->beginTransaction();
  try {
    // Ambil data peminjaman yang aktif
    $stmt = $pdo->prepare('SELECT book_id, due_at FROM borrows WHERE id=:id AND school_id=:sid');
    $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    $borrowData = $stmt->fetch();

    if ($borrowData) {
      // Ambil setting denda keterlambatan dari sekolah
      $schoolStmt = $pdo->prepare('SELECT late_fine FROM schools WHERE id = :sid');
      $schoolStmt->execute(['sid' => $sid]);
      $late_fine = (int) ($schoolStmt->fetchColumn() ?: 500);

      // Hitung denda berdasarkan hari keterlambatan
      $fineAmount = 0;
      if ($borrowData['due_at']) {
        $dueDate = new DateTime($borrowData['due_at']);
        $now = new DateTime();
        if ($now > $dueDate) {
          $diff = $now->diff($dueDate);
          $daysLate = $diff->days;
          $fineAmount = $daysLate * $late_fine;
        }
      }

      // Update status peminjaman menjadi dikembalikan dengan denda
      $stmt = $pdo->prepare(
        'UPDATE borrows SET returned_at=NOW(), status="returned", fine_amount=:fine, fine_status="unpaid"
         WHERE id=:id AND school_id=:sid'
      );
      $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid, 'fine' => $fineAmount]);

      // Tambahkan stok buku karena sudah dikembalikan
      $stmt = $pdo->prepare('UPDATE books SET copies = copies + 1 WHERE id = :bid');
      $stmt->execute(['bid' => $borrowData['book_id']]);

      // Ambil info buku untuk cek waitlist
      $stmt = $pdo->prepare('SELECT title, author FROM books WHERE id = :bid');
      $stmt->execute(['bid' => $borrowData['book_id']]);
      $book = $stmt->fetch();

      // Cek apakah ada siswa yang menunggu buku ini di waitlist
      if ($book) {
        $waitlistStmt = $pdo->prepare(
          'SELECT w.*, u.id as student_real_id 
               FROM waitlist w
               JOIN members m ON w.member_id = m.id
               JOIN users u ON m.nisn = u.nisn AND m.school_id = u.school_id
               WHERE w.school_id = :sid 
               AND w.book_title = :title 
               AND w.book_author = :author 
               AND w.status = "pending"
               ORDER BY w.created_at ASC'
        );
        $waitlistStmt->execute([
          'sid' => $sid,
          'title' => trim($book['title']),
          'author' => trim($book['author'])
        ]);

        $waitingStudents = $waitlistStmt->fetchAll();

        // Jika ada siswa menunggu, kirim notifikasi ke siswa pertama
        if ($waitingStudents) {
          require_once __DIR__ . '/../src/NotificationsHelper.php';
          $notifHelper = new NotificationsHelper($pdo);

          $firstStudent = $waitingStudents[0];

          // Buat notifikasi bahwa buku sudah tersedia
          $notifHelper->createNotification(
            $sid,
            $firstStudent['student_real_id'],
            'info',
            'Buku Tersedia!',
            'Buku "' . htmlspecialchars($book['title']) . '" yang Anda tunggu sudah tersedia. Segera lakukan peminjaman!'
          );

          $updateWaitlist = $pdo->prepare('UPDATE waitlist SET status = "notified" WHERE id = ?');
          $updateWaitlist->execute([$firstStudent['id']]);
        }
      }

      // Commit transaksi jika semua berhasil
      $pdo->commit();
    } else {
      // Rollback jika data tidak ditemukan
      $pdo->rollBack();
    }
  } catch (Exception $e) {
    // Rollback jika terjadi error
    if ($pdo->inTransaction())
      $pdo->rollBack();
  }
  // Redirect ke halaman peminjaman
  header('Location: borrows.php');
  exit;
}

// Ambil data sekolah dan setting denda
$schoolStmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$schoolStmt->execute(['sid' => $sid]);
$school = $schoolStmt->fetch();

// Validasi data sekolah ada
if (!$school) {
  die('Error: School data not found');
}

// Ambil setting denda keterlambatan dari data sekolah
$late_fine = (int) ($school['late_fine'] ?? 500);

// Update status peminjaman yang sudah lewat jatuh tempo
$pdo->prepare(
  'UPDATE borrows SET status="overdue"
   WHERE school_id=:sid AND returned_at IS NULL AND due_at < NOW() AND status != "overdue"'
)->execute(['sid' => $sid]);

// Hitung denda otomatis untuk peminjaman yang terlambat
if ($late_fine > 0) {
  $pdo->prepare(
    'UPDATE borrows 
       SET fine_amount = GREATEST(0, DATEDIFF(NOW(), due_at)) * :fine
       WHERE school_id=:sid 
       AND returned_at IS NULL 
       AND due_at < NOW()'
  )->execute(['sid' => $sid, 'fine' => $late_fine]);
}

// Ambil semua data peminjaman ke buku dan anggota
$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, bk.cover_image, bk.isbn, bk.max_borrow_days, m.name AS member_name, m.nisn
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid
   ORDER BY b.borrowed_at DESC'
);
$stmt->execute(['sid' => $sid]);
$borrows = $stmt->fetchAll();

// Hitung statistik
$totalBorrows = count($borrows);
// Hitung peminjaman yang masih aktif/belum dikembalikan
$activeBorrows = count(array_filter($borrows, fn($b) => $b['status'] !== 'returned'));
// Hitung peminjaman yang sudah terlambat
$overdueBorrows = count(array_filter($borrows, fn($b) => $b['status'] === 'overdue'));
// Hitung peminjaman dengan denda
$withFines = count(array_filter($borrows, fn($b) => !empty($b['fine_amount'])));
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Halaman manajemen peminjaman dan pengembalian buku -->
  <title>Manajemen Peminjaman</title>
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
        <iconify-icon icon="mdi:book-clock-outline" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Manajemen Peminjaman</strong>
      </div>
      <div class="topbar-actions">
      </div>
    </div>

    <!-- Main content area -->
    <div class="content">
      <div class="main">
        <div>
          <!-- Tombol untuk membuka scanner barcode -->
          <div class="scanner-toggle-wrap">
            <!-- Button untuk mulai pemindaian barcode -->
            <button onclick="toggleScanner()" class="btn-barcode-start">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              <span id="scannerToggleText">Mulai Peminjaman Baru</span>
            </button>
          </div>

          <!-- Section pemindai barcode dan keranjang peminjaman -->
          <div id="scannerSection" class="card scanner-section">
            <div class="scanner-grid">
              <!-- Bagian pemindai barcode -->
              <div>
                <!-- Container untuk QR code reader -->
                <div id="reader"></div>
                <!-- Status pemindaian -->
                <div id="scanStatus"></div>

                <!-- Kontrol mode pemindaian -->
                <div class="scanner-controls">
                  <button id="btnModeBook" class="scanner-mode-btn active" onclick="setScanMode('book')">Mode
                    Buku</button>
                  <button id="btnModeMember" class="scanner-mode-btn" onclick="setScanMode('member')">Mode
                    Anggota</button>
                </div>
              </div>

              <!-- Section keranjang peminjaman -->
              <div>
                <!-- Title keranjang -->
                <h2 class="flex-center gap-2">
                  <iconify-icon icon="mdi:basket-outline" style="font-size: 20px;"></iconify-icon>
                  Keranjang Peminjaman
                </h2>

                <!-- Info anggota yang dipilih -->
                <div id="scannedMemberInfo" class="scanned-info-card">
                  <div class="scanned-info-label">Peminjam</div>
                  <div class="scanned-info-value">
                    <span id="scannedMemberName"></span>
                  </div>
                  <div class="scanned-info-meta">NISN: <span id="scannedMemberNisn"></span></div>
                </div>

                <!-- Tampilan kosong sebelum ada scan -->
                <div id="scanEmptyState" class="scanner-empty-state">
                  <iconify-icon icon="mdi:barcode"></iconify-icon>
                  <p>Scan buku atau anggota untuk memulai</p>
                </div>

                <!-- Tabel daftar buku yang sudah di-scan -->
                <div id="scannedBooksContainer" style="display: none;">
                  <!-- Table wrapper untuk buku yang di-scan -->
                  <div class="borrows-table-wrap mb-4">
                    <table class="borrows-table">
                      <thead>
                        <tr>
                          <th style="width: 60px;">Cover</th>
                          <th>Buku</th>
                          <th style="width: 40px;"></th>
                        </tr>
                      </thead>
                      <tbody id="scannedBooksList"></tbody>
                    </table>
                  </div>

                  <!-- Input untuk set tanggal pengembalian -->
                  <div class="form-group">
                    <label>Tanggal Pengembalian</label>
                    <input type="date" id="borrowDueDate">
                  </div>

                  <!-- Tombol konfirmasi dan batal peminjaman -->
                  <div class="action-grid">
                    <!-- Tombol submit peminjaman -->
                    <button onclick="submitBorrow()" id="btnSubmitBorrow" class="btn primary"
                      style="flex: 1; justify-content: center;">
                      Konfirmasi Peminjaman
                    </button>
                    <!-- Tombol batal peminjaman -->
                    <button onclick="resetScannerSession()" class="btn"
                      style="color: var(--danger); border-color: color-mix(in srgb, var(--danger), transparent 70%);">
                      Batal
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading overlay saat proses peminjaman -->
          <div id="scannerLoading"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--overlay); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; color: white; backdrop-filter: blur(4px);">
            <!-- Spinner animation -->
            <div class="spinner"
              style="width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px;">
            </div>
            <p>Memproses...</p>
          </div>

          <!-- Load QR code reader library -->
          <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
          <!-- Set default borrow days dari sekolah setting -->
          <script>
            window.defaultBorrowDays = <?php echo (int) ($school['borrow_duration'] ?? 7); ?>;
          </script>
          <!-- Load scanner dan peminjaman handler script -->
          <script src="../assets/js/borrows-manage.js"></script>

          <!-- Modal untuk tampilkan statistik detail -->
          <!-- Modal overlay statistik -->
          <div class="modal-overlay" id="statsModal">
            <!-- Modal container -->
            <div class="modal-container">
              <!-- Modal header dengan search -->
              <div class="modal-header">
                <!-- Modal title -->
                <div style="flex: 1;">
                  <h2>Detail Data</h2>
                </div>
                <!-- Search box untuk filter modal -->
                <div class="search-wrapper">
                  <input type="text" id="searchModal" class="search-input" placeholder="Cari data...">
                  <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                  <div class="search-kbd">
                    <span style="font-size: 8px;">Ctrl</span>
                    <span>K</span>
                  </div>
                  <button class="search-clear" id="clearModalSearch"><iconify-icon
                      icon="mdi:close-circle"></iconify-icon></button>
                </div>
                <!-- Close modal button -->
                <button class="modal-close" type="button" style="margin-left: 20px;">×</button>
              </div>
              <!-- Modal content body -->
              <div class="modal-body">
                <!-- Loading indicator -->
                <div class="modal-loading">Memuat data...</div>
              </div>
            </div>
          </div>

          <!-- Load statistics handler script -->
          <script src="../assets/js/borrows-stats.js"></script>
          <!-- Box statistik KPI dengan 3 kartu -->
          <div class="stats-grid">
            <!-- KPI Card 1: Total Peminjaman -->
            <div class="stat-card clickable" data-stat-type="total" title="Klik untuk melihat detail">
              <div class="stat-icon blue">
                <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Total Peminjaman</div>
                <div class="stat-value"><?= number_format($totalBorrows) ?></div>
              </div>
            </div>

            <!-- KPI Card 2: Peminjaman Aktif -->
            <div class="stat-card clickable" data-stat-type="active" title="Klik untuk melihat detail">
              <div class="stat-icon blue">
                <iconify-icon icon="mdi:clock-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Sedang Dipinjam</div>
                <div class="stat-value"><?= number_format($activeBorrows) ?></div>
              </div>
            </div>

            <!-- KPI Card 3: Peminjaman Terlambat -->
            <div class="stat-card clickable" data-stat-type="overdue" title="Klik untuk melihat detail">
              <div class="stat-icon red">
                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value"><?= number_format($overdueBorrows) ?></div>
              </div>
            </div>
          </div>



          <!-- Tabel daftar peminjaman yang masih aktif -->
          <div class="card">
            <!-- Header section dengan search -->
            <div class="section-header-flex">
              <!-- Title table -->
              <h2>Daftar Peminjaman Aktif</h2>
              <!-- Search box untuk filter tabel -->
              <div class="search-wrapper">
                <input type="text" id="searchActive" class="search-input" placeholder="Cari buku atau nama siswa...">
                <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                <div class="search-kbd">
                  <span style="font-size: 8px;">Ctrl</span>
                  <span>K</span>
                </div>
                <button class="search-clear"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
              </div>
            </div>
            <!-- Jika tidak ada peminjaman aktif tampilkan empty state -->
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:book-off-outline"></iconify-icon>
                <p>Tidak ada peminjaman aktif saat ini</p>
              </div>
              <!-- Jika ada peminjaman aktif tampilkan table -->
            <?php else: ?>
              <!-- Table wrapper peminjaman aktif -->
              <div class="borrows-table-wrap">
                <table class="borrows-table">
                  <thead>
                    <tr>
                      <th class="table-no">No</th>
                      <th>Nama Buku</th>
                      <th>Nama Anggota</th>
                      <th>Tanggal Pinjam</th>
                      <th>Jatuh Tempo</th>
                      <th>Status</th>
                      <th>Denda</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no = 1;
                    foreach ($borrows as $br):
                      if ($br['status'] === 'returned')
                        continue;
                      ?>
                      <!-- Row data peminjaman aktif -->
                      <tr>
                        <td class="table-no"><?= $no++ ?></td>
                        <!-- Nama buku -->
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <!-- Nama anggota/siswa -->
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <!-- Tanggal peminjaman -->
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <!-- Tanggal jatuh tempo -->
                        <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                        <!-- Status peminjaman (dipinjam/terlambat) -->
                        <td>
                          <?php if ($br['status'] === 'overdue'): ?>
                            <span class="status-badge overdue">Terlambat</span>
                          <?php else: ?>
                            <span class="status-badge borrowed">Dipinjam</span>
                          <?php endif; ?>
                        </td>
                        <!-- Tampilkan denda jika ada -->
                        <td>
                          <?php if (!empty($br['fine_amount'])): ?>
                            <div class="flex-center gap-2" style="font-weight: 700; color: var(--danger);">
                              <iconify-icon icon="mdi:credit-card-outline"></iconify-icon>
                              Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                              <span
                                style="font-size: 10px; opacity: 0.8;">(<?= $br['fine_status'] === 'paid' ? 'Paid' : 'Unpaid' ?>)</span>
                            </div>
                          <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                          <?php endif; ?>
                        </td>
                        <!-- Action buttons perpanjang dan kembalikan -->
                        <td>
                          <div class="action-grid">
                            <!-- Tombol perpanjang tenggat waktu -->
                            <button type="button"
                              onclick="extendDueDate(<?= $br['id'] ?>, '<?= htmlspecialchars($br['title']) ?>')"
                              class="btn-sm btn-sm-warning">
                              <iconify-icon icon="mdi:calendar-plus"></iconify-icon>
                              Perpanjang
                            </button>
                            <!-- Tombol kembalikan buku -->
                            <a href="borrows.php?action=return&id=<?= $br['id'] ?>" class="btn-sm btn-sm-success"
                              style="text-decoration: none;">
                              <iconify-icon icon="mdi:check"></iconify-icon>
                              Kembali
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Tabel riwayat buku yang sudah dikembalikan -->
          <div class="card">
            <!-- Header section dengan search -->
            <div class="section-header-flex">
              <!-- Title table -->
              <h2>Riwayat Pengembalian Buku</h2>
              <!-- Search box untuk filter history -->
              <div class="search-wrapper">
                <input type="text" id="searchHistory" class="search-input" placeholder="Cari buku atau nama siswa...">
                <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                <div class="search-kbd">
                  <span style="font-size: 8px;">Ctrl</span>
                  <span>K</span>
                </div>
                <button class="search-clear"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
              </div>
            </div>
            <!-- Jika tidak ada riwayat pengembalian tampilkan empty state -->
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] === 'returned'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:history"></iconify-icon>
                <p>Belum ada riwayat pengembalian</p>
              </div>
              <!-- Jika ada riwayat pengembalian tampilkan table -->
            <?php else: ?>
              <!-- Table wrapper riwayat pengembalian -->
              <div class="borrows-table-wrap">
                <table class="borrows-table">
                  <thead>
                    <tr>
                      <th class="table-no">No</th>
                      <th>Nama Buku</th>
                      <th>Nama Anggota</th>
                      <th>Tanggal Pinjam</th>
                      <th>Tanggal Kembali</th>
                      <th>Denda</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no = 1;
                    foreach ($borrows as $br):
                      if ($br['status'] !== 'returned')
                        continue;
                      ?>
                      <!-- Row data riwayat pengembalian -->
                      <tr>
                        <td class="table-no"><?= $no++ ?></td>
                        <!-- Nama buku -->
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <!-- Nama anggota yang meminjam -->
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <!-- Tanggal peminjaman -->
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <!-- Tanggal pengembalian -->
                        <td><?= $br['returned_at'] ? date('d/m/Y', strtotime($br['returned_at'])) : '-' ?></td>
                        <!-- Denda yang dikenakan jika ada -->
                        <td>
                          <?php if (!empty($br['fine_amount'])): ?>
                            <div class="flex-center gap-2" style="font-weight: 700; color: var(--danger);">
                              Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                            </div>
                          <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                          <?php endif; ?>
                        </td>
                        <!-- Status buku sudah dikembalikan -->
                        <td>
                          <span class="status-badge returned">Dikembalikan</span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- JavaScript handler functions -->
  <script>
    // Fungsi untuk perpanjang tenggat waktu peminjaman buku
    function extendDueDate(borrowId, bookTitle) {
      // Prompt user untuk input jumlah hari perpanjangan
      const days = prompt(`Perpanjang tenggat untuk "${bookTitle}":\n\nMasukkan jumlah hari perpanjangan (1-365):`, '7');

      // Cancel jika user tekan cancel/close
      if (days === null) {
        return;
      }

      // Parse input menjadi integer
      const daysInt = parseInt(days, 10);

      // Validasi input harus antara 1-365
      if (isNaN(daysInt) || daysInt < 1 || daysInt > 365) {
        alert('Jumlah hari harus antara 1-365');
        return;
      }

      // Kirim request ke API untuk perpanjang tenggat
      fetch('api/extend-due-date.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId + '&extend_days=' + daysInt
      })
        // Parse response JSON
        .then(response => response.json())
        // Tangani hasil response
        .then(data => {
          // Jika perpanjangan berhasil tampilkan sukses dan reload
          if (data.success) {
            alert(`✓ Tenggat waktu diperpanjang!\n\nBuku: ${data.book_title}\nAnggota: ${data.member_name}\nTenggat Baru: ${data.new_due_date}`);
            location.reload();
          } else {
            // Tampilkan error jika gagal
            alert('❌ Gagal memperpanjang tenggat:\n' + data.message);
          }
        })
        // Catch network error
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan saat memperpanjang tenggat');
        });
    }

    // Fungsi untuk konfirmasi pengembalian buku
    function confirmReturn(borrowId) {
      // Konfirmasi pengembalian dengan user
      if (!confirm('Konfirmasi pengembalian buku ini?')) {
        return;
      }

      // Kirim request ke API untuk konfirmasi pengembalian
      fetch('api/admin-confirm-return.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId
      })
        // Parse response JSON
        .then(response => response.json())
        // Tangani hasil response
        .then(data => {
          // Jika konfirmasi berhasil tampilkan sukses dan reload
          if (data.success) {
            alert('Pengembalian buku telah dikonfirmasi!');
            location.reload();
          } else {
            // Tampilkan pesan error
            alert(data.message || 'Gagal mengkonfirmasi pengembalian');
          }
        })
        // Catch network error
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan');
        });
    }
  </script>

  <!-- Audio notification untuk scan barcode sukses -->
  <audio id="scanNotificationSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"
    preload="auto"></audio>
</body>

</html>