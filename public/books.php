<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsHelper.php';

$user = $_SESSION['user'];
$sid = $user['school_id'];
$action = $_GET['action'] ?? 'list';

// Create uploads directory if not exists
$uploadsDir = __DIR__ . '/../img/covers';
if (!is_dir($uploadsDir)) {
  mkdir($uploadsDir, 0755, true);
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $coverImage = '';

  // Handle image upload
  if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff'];
    $filename = basename($_FILES['cover_image']['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
      $newFilename = 'book_' . time() . '_' . uniqid() . '.' . $ext;
      $uploadPath = $uploadsDir . '/' . $newFilename;

      if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
        $coverImage = $newFilename;
      }
    }
  }

  $pdo->prepare(
    'INSERT INTO books (school_id,title,author,isbn,category,access_level,shelf,row_number,lokasi_rak,copies,max_borrow_days,cover_image)
     VALUES (:sid,:title,:author,:isbn,:category,:access_level,:shelf,:row,:lokasi_rak,:copies,:max_borrow_days,:cover_image)'
  )->execute([
        'sid' => $sid,
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'isbn' => $_POST['isbn'],
        'category' => $_POST['category'],
        'access_level' => $_POST['access_level'] ?? 'all',
        'shelf' => $_POST['shelf'],
        'row' => $_POST['row_number'],
        'lokasi_rak' => $_POST['lokasi_rak'],
        'copies' => 1,
        'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
        'cover_image' => $coverImage
      ]);
  
  // Get all students in this school to notify them about new book
  $studentsStmt = $pdo->prepare(
    'SELECT id FROM users WHERE school_id = :school_id AND role = "student"'
  );
  $studentsStmt->execute(['school_id' => $sid]);
  $students = $studentsStmt->fetchAll(PDO::FETCH_COLUMN);
  
  // Broadcast notification to all students
  if (!empty($students)) {
    $helper = new NotificationsHelper($pdo);
    $bookTitle = $_POST['title'];
    $notificationMessage = 'Buku "' . htmlspecialchars($bookTitle) . '" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!';
    
    $helper->broadcastNotification(
      $sid,
      $students,
      'new_book',
      'Buku Baru Tersedia',
      $notificationMessage
    );
  }
  
  header('Location: books.php');
  exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT cover_image FROM books WHERE id=:id AND school_id=:sid');
    $stmt->execute(['id' => $id, 'sid' => $sid]);
    $oldBook = $stmt->fetch();
    $coverImage = $oldBook['cover_image'] ?? '';

    // Handle new image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      $filename = basename($_FILES['cover_image']['name']);
      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      if (in_array($ext, $allowed)) {
        $newFilename = 'book_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadsDir . '/' . $newFilename;

        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
          // Delete old image if exists
          if ($coverImage && file_exists($uploadsDir . '/' . $coverImage)) {
            unlink($uploadsDir . '/' . $coverImage);
          }
          $coverImage = $newFilename;
        }
      }
    }

    $pdo->prepare(
      'UPDATE books SET title=:title,author=:author,isbn=:isbn,category=:category,access_level=:access_level,shelf=:shelf,row_number=:row,lokasi_rak=:lokasi_rak,copies=:copies,max_borrow_days=:max_borrow_days,cover_image=:cover_image
       WHERE id=:id AND school_id=:sid'
    )->execute([
          'title' => $_POST['title'],
          'author' => $_POST['author'],
          'isbn' => $_POST['isbn'],
          'category' => $_POST['category'],
          'access_level' => $_POST['access_level'] ?? 'all',
          'shelf' => $_POST['shelf'],
          'row' => $_POST['row_number'],
          'lokasi_rak' => $_POST['lokasi_rak'],
          'copies' => 1,
          'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
          'cover_image' => $coverImage,
          'id' => $id,
          'sid' => $sid
        ]);
    header('Location: books.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM books WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $book = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM books WHERE id=:id AND school_id=:sid')
    ->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
  header('Location: books.php');
  exit;
}

// Get school info needed for default settings
$stmt = $pdo->prepare('SELECT borrow_duration FROM schools WHERE id = :sid');
$stmt->execute(['sid' => $sid]);
$school = $stmt->fetch();
$defaultDuration = $school['borrow_duration'] ?? 7;

$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id=:sid ORDER BY id DESC');
$stmt->execute(['sid' => $sid]);
$books = $stmt->fetchAll();

$categories = [
  'Fiksi',
  'Non-Fiksi',
  'Referensi',
  'Biografi',
  'Sejarah',
  'Seni & Budaya',
  'Teknologi',
  'Pendidikan',
  'Anak-anak',
  'Komik',
  'Majalah',
  'Lainnya'
];
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/books.css">
  <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Buku</strong>
    </div>

    <div class="content">
      <div class="main">

        <!-- SECTION 1: ADD/EDIT FORM (Full Width) -->
        <div class="card form-card">
          <div class="card-header-flex" style="border-bottom: none; margin-bottom: 24px; padding-bottom: 0;">
             <div style="flex: 1;">
                 <h2 style="border: none; padding: 0; margin-bottom: 4px;">
                    <iconify-icon icon="mdi:book-plus-outline" style="color: var(--accent);"></iconify-icon>
                    <?= $action === 'edit' ? 'Edit Detail Buku' : 'Tambah Koleksi Baru' ?>
                 </h2>
                 <p style="color: var(--muted); font-size: 13px;">Lengkapi informasi detail buku di bawah ini</p>
             </div>
          </div>

          <form method="post" action="<?= $action === 'edit' ? '' : 'books.php?action=add' ?>"
            enctype="multipart/form-data">
            
            <div class="form-group-wrapper">
                <div class="form-subheader">
                    <iconify-icon icon="mdi:information-outline"></iconify-icon>
                    Informasi Utama
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group"><label>Judul Buku</label>
                            <input name="title" required value="<?= $book['title'] ?? '' ?>" placeholder="Contoh: Laskar Pelangi">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group"><label>Pengarang</label>
                            <input name="author" required value="<?= $book['author'] ?? '' ?>" placeholder="Nama Penulis">
                        </div>
                    </div>
                </div>
                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-col">
                        <div class="form-group"><label>ISBN / Kode Buku</label>
                            <input name="isbn" value="<?= $book['isbn'] ?? '' ?>" placeholder="Contoh: 978-602-...">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group"><label>Kategori</label>
                            <select name="category">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($book['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?>
                                </option>
                            <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col wide">
                    <div class="form-group-wrapper">
                        <div class="form-subheader">
                            <iconify-icon icon="mdi:map-marker-outline"></iconify-icon>
                            Lokasi & Pengaturan
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group"><label>Target Peminjam</label>
                                    <select name="access_level">
                                        <option value="all" <?= ($book['access_level'] ?? '') === 'all' ? 'selected' : '' ?>>Semua (Umum)</option>
                                        <option value="teacher_only" <?= ($book['access_level'] ?? '') === 'teacher_only' ? 'selected' : '' ?>>Khusus Guru/Staf</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group"><label>Batas Pinjam (Hari)</label>
                                    <input type="number" name="max_borrow_days" placeholder="<?= (int)$defaultDuration ?> hari (Default)" 
                                           value="<?= $action === 'edit' && isset($book['max_borrow_days']) ? (int)$book['max_borrow_days'] : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 16px;">
                            <label>Lokasi Rak (Rak / Baris / Detail)</label>
                            <div class="book-location-input">
                                <input name="shelf" value="<?= $book['shelf'] ?? '' ?>" placeholder="Rak A" style="flex: 1;">
                                <input type="number" min="1" name="row_number" value="<?= $book['row_number'] ?? '' ?>" placeholder="Baris 1" style="flex: 1;">
                                <input name="lokasi_rak" value="<?= $book['lokasi_rak'] ?? '' ?>" placeholder="Detail Spesifik Lokasi" style="flex: 2;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-col wide">
                    <div class="form-group-wrapper" style="margin-bottom: 0;">
                        <div class="form-subheader">
                            <iconify-icon icon="mdi:image-outline"></iconify-icon>
                            Sampul Buku (Opsional)
                        </div>
                        <div class="form-row" style="align-items: center; margin-bottom: 0; gap: 32px;">
                            <div class="form-col" style="flex: 0 0 160px;">
                                <div id="imagePreview" class="image-preview-mini" style="height: 220px; width: 160px; border: 2px dashed var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: var(--bg); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); padding: 8px; box-sizing: border-box;">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../img/covers/<?= htmlspecialchars($book['cover_image']) ?>" alt="Preview" style="max-height: 100%; max-width: 100%; object-fit: contain; border-radius: 4px; box-shadow: 0 8px 15px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div style="text-align: center;">
                                            <iconify-icon icon="mdi:camera-outline" style="font-size: 32px; color: var(--muted); opacity: 0.5;"></iconify-icon>
                                            <div style="font-size: 11px; color: var(--muted); margin-top: 4px; font-weight: 600;">PREVIEW</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-col" style="flex: 1;">
                                <div class="form-group">
                                    <label>Pilih File Gambar</label>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/gif" id="imageInput"
                                        onchange="previewImage(event)">
                                    </div>
                                    <p style="color: var(--muted); font-size: 13px; margin-top: 12px; line-height: 1.5;">
                                        Unggah gambar sampul untuk memudahkan identifikasi buku. 
                                        <br>
                                        <iconify-icon icon="mdi:information-outline" style="vertical-align: middle;"></iconify-icon>
                                        Format: JPG, PNG, GIF. Maks: 5MB. 
                                        <br>
                                        <iconify-icon icon="mdi:aspect-ratio" style="vertical-align: middle;"></iconify-icon>
                                        Rasio ideal: 2:3 (Tegak).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock hidden, always 1 -->
            <input type="hidden" name="copies" value="1">

            <div class="form-actions">
                <button class="btn" type="submit"><?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Buku Baru' ?></button>
                <?php if($action === 'edit'): ?>
                    <a href="books.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- SECTION 2: BOOK LIST (Full Width) -->
        <div class="card" style="padding-top: 0;">
          <div class="card-header-flex" style="border-bottom: 3px solid var(--accent-soft); margin-bottom: 24px; padding: 24px 0 16px 0;">
             <div style="flex: 1;">
                 <h2 style="border: none; padding: 0; margin-bottom: 4px;">
                    <iconify-icon icon="mdi:bookshelf" style="color: var(--accent);"></iconify-icon>
                    Daftar Koleksi Buku (<?= count($books) ?>)
                 </h2>
                 <p style="color: var(--muted); font-size: 13px;">Kelola dan cari koleksi buku perpustakaan anda secara real-time</p>
             </div>
             <div class="search-wrapper">
                 <input type="text" id="searchBooksList" class="search-input" placeholder="Cari judul, penulis, atau ISBN...">
                 <iconify-icon icon="mdi:magnify" class="search-icon-inside"></iconify-icon>
                 <div class="search-kbd">
                     <span style="font-size: 8px;">Ctrl</span>
                     <span>K</span>
                 </div>
                 <button class="search-clear" id="clearBooksSearch"><iconify-icon icon="mdi:close-circle"></iconify-icon></button>
             </div>
          </div>
          
          <div class="books-grid">
            <?php foreach ($books as $idx => $b): ?>
              <div class="book-card-vertical">
                <div class="book-cover-container">
                  <?php if (!empty($b['cover_image']) && file_exists(__DIR__ . '/../img/covers/' . $b['cover_image'])): ?>
                    <img src="../img/covers/<?= htmlspecialchars($b['cover_image']) ?>"
                      alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="no-image-placeholder">
                        <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px; color: var(--accent); opacity: 0.5;"></iconify-icon>
                    </div>
                  <?php endif; ?>
                  
                  <div class="stock-badge-overlay" style="
                      background: <?= $b['copies'] > 0 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>;
                      color: <?= $b['copies'] > 0 ? '#059669' : '#dc2626' ?>;
                      border: 1px solid <?= $b['copies'] > 0 ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)' ?>;
                  ">
                      <iconify-icon icon="<?= $b['copies'] > 0 ? 'mdi:check-circle' : 'mdi:clock-alert' ?>" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                      <?= $b['copies'] > 0 ? 'Tersedia' : 'Sirkulasi' ?>
                  </div>
                </div>
                
                <div class="book-card-body">
                  <div class="book-category"><?= htmlspecialchars($b['category'] ?? 'Umum') ?></div>
                  <div class="book-title" title="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?></div>
                  <div class="book-author"><?= htmlspecialchars($b['author']) ?></div>
                  
                  <div class="book-card-footer">
                      <div class="shelf-info">
                          <iconify-icon icon="mdi:bookshelf" style="color: var(--accent);"></iconify-icon>
                          <span>Rak <?= htmlspecialchars($b['shelf'] ?? '-') ?> / <?= htmlspecialchars($b['row_number'] ?? '-') ?></span>
                      </div>
                      
                      <div class="action-buttons">
                        <button class="btn-icon-sm" onclick="openDetailModal(<?= $idx ?>)" title="Lihat Detail">
                           <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                        </button>
                        <a href="books.php?action=edit&id=<?= $b['id'] ?>" class="btn-icon-sm" title="Ubah Data">
                           <iconify-icon icon="mdi:pencil-outline"></iconify-icon>
                        </a>
                        <a href="books.php?action=delete&id=<?= $b['id'] ?>" class="btn-icon-sm btn-icon-danger" 
                           onclick="return confirm('Hapus buku ini dari database?')" title="Hapus Buku">
                           <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                        </a>
                      </div>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        </div>

        <!-- SECTION 3: BOTTOM INFO (Grid) -->
        <div class="bottom-grid">
            <!-- FAQ -->
            <div class="card">
                <h2>Pertanyaan Umum</h2>
                <div class="faq-container">
                    <div class="faq-item" onclick="toggleFaq(this)">
                        <div class="faq-question">Bagaimana cara menambah buku? <iconify-icon icon="mdi:chevron-down"></iconify-icon></div>
                        <div class="faq-answer">Isi formulir "Tambah Buku" di bagian atas halaman dengan lengkap, lalu klik tombol simpan.</div>
                    </div>
                    <div class="faq-item" onclick="toggleFaq(this)">
                        <div class="faq-question">Bagaimana edit stok? <iconify-icon icon="mdi:chevron-down"></iconify-icon></div>
                        <div class="faq-answer">Cari buku di daftar, klik tombol pensil (edit), lalu ubah jumlah stok dan simpan.</div>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="card">
                <h2>Statistik Perpustakaan</h2>
                <div class="stats-grid-modern">
                    
                    <!-- Card 1: Total Buku -->
                    <div class="stat-card-modern" onclick="showStatDetail('books')">
                        <div class="stat-icon blue">
                            <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count($books) ?></div>
                            <div class="stat-label">Total Judul Buku</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>


                    <!-- Card 3: Kategori -->
                    <div class="stat-card-modern" onclick="showStatDetail('categories')">
                        <div class="stat-icon teal">
                            <iconify-icon icon="mdi:shape"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count(array_unique(array_column($books, 'category'))) ?></div>
                            <div class="stat-label">Kategori Buku</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>

                </div>
            </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Stat Detail Modal -->
  <div id="statModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="statModalTitle">Statistik Detail</h2>
        <button class="modal-close" onclick="closeStatModal()">&times;</button>
      </div>
      <div class="modal-body" id="statModalBody">
          <!-- Content injected via JS -->
      </div>
    </div>
  </div>

  <!-- Detail Modal -->
  <div id="detailModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Detail Buku</h2>
        <button class="modal-close" onclick="closeDetail()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-layout">
          <div class="detail-image">
            <img id="detailCover" src="" alt="Book Cover">
          </div>
          <div class="detail-info">
            <div class="detail-field">
              <label>Judul</label>
              <div id="detailTitle"></div>
            </div>
            <div class="detail-field">
              <label>Pengarang</label>
              <div id="detailAuthor"></div>
            </div>
            <div class="detail-field">
              <label>ISBN</label>
              <div id="detailISBN"></div>
            </div>
            <div class="detail-field">
              <label>Kategori</label>
              <div id="detailCategory"></div>
            </div>
            <div class="detail-field">
              <label>Lokasi</label>
              <div id="detailLocation"></div>
            </div>
            <div class="detail-field detail-lokasi-spesifik" style="display: none;">
              <label>Lokasi Spesifik</label>
              <div id="detailLokasiRak"></div>
            </div>
            <div class="detail-field">
              <label>Batas Pinjam</label>
              <div id="detailMaxBorrow"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Data Payload for JS -->
  <script>
    // Pass PHP data to JS
    window.booksData = <?= json_encode(array_values($books)) ?>;

    /**
     * UTILITY: Image Preview
     */
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('imagePreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; box-shadow: 0 8px 15px rgba(0,0,0,0.1);">`;
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = `
                <div style="text-align: center;">
                    <iconify-icon icon="mdi:camera-outline" style="font-size: 32px; color: var(--muted); opacity: 0.5;"></iconify-icon>
                    <div style="font-size: 11px; color: var(--muted); margin-top: 4px; font-weight: 600;">PREVIEW</div>
                </div>`;
        }
    }

    /**
     * UTILITY: FAQ Accordion
     */
    function toggleFaq(element) {
        // Close other FAQs
        const allFaqs = document.querySelectorAll('.faq-item');
        allFaqs.forEach(item => {
            if (item !== element) {
                item.classList.remove('active');
            }
        });
        // Toggle current
        element.classList.toggle('active');
    }

    /**
     * FEATURE 1: DETAIL MODAL (Mata Icon)
     */
    function openDetailModal(index) {
        if (!window.booksData || !window.booksData[index]) {
            alert('Data buku tidak ditemukan!');
            return;
        }

        const book = window.booksData[index];
        
        // Helper to set text
        const set = (id, val) => {
            const el = document.getElementById(id);
            if(el) {
                // Force string and handle empty/null
                let content = (val !== null && val !== undefined && val !== '') ? String(val) : '-';
                el.textContent = content;
                console.log(`Set #${id} to "${content}"`);
            } else {
                console.error(`Element #${id} not found in Modal!`);
            }
        };

        console.log('Populating modal with:', book);

        set('detailTitle', book.title);
        set('detailAuthor', book.author);
        set('detailISBN', book.isbn);
        set('detailCategory', book.category);
        set('detailLocation', `Rak ${book.shelf || '?'} / Baris ${book.row_number || '?'}`);
        
        // Lokasi Rak Spesifik
        const specLoc = document.querySelector('.detail-lokasi-spesifik');
        if (book.lokasi_rak) {
            set('detailLokasiRak', book.lokasi_rak);
            if (specLoc) specLoc.style.display = 'block';
        } else {
            if (specLoc) specLoc.style.display = 'none';
        }
        set('detailCopies', `${book.copies} Salinan`);
        set('detailMaxBorrow', book.max_borrow_days ? `${book.max_borrow_days} Hari` : 'Default Sekolah');
        
        // Image Handling
        const imgContainer = document.getElementById('detailCover');
        if (imgContainer) {
            console.log('Cover image:', book.cover_image);
            if (book.cover_image && book.cover_image !== '') {
                imgContainer.src = '../img/covers/' + book.cover_image;
                imgContainer.style.display = 'block';
            } else {
                // Use a placeholder if no image
                imgContainer.src = 'https://via.placeholder.com/150x200?text=No+Cover';
                imgContainer.style.display = 'block';
            }
        }
        
        const modal = document.getElementById('detailModal');
        if (modal) modal.style.display = 'block';
    }

    function closeDetail() {
        const modal = document.getElementById('detailModal');
        if (modal) modal.style.display = 'none';
    }

    /**
     * FEATURE 2: STATS MODAL (Statistic Cards)
     */
    function showStatDetail(type) {
        const books = window.booksData || [];
        const modal = document.getElementById('statModal');
        const titleEl = document.getElementById('statModalTitle');
        const bodyEl = document.getElementById('statModalBody');

        if (!modal) {
             console.error('Modal element #statModal not found in DOM');
             return;
        }

        let content = '';

        if (type === 'books') {
            titleEl.textContent = 'Daftar Semua Buku';
            content = `<div class="modal-stat-list">`;
            // Get 10 newest
            const recent = books.slice(0, 10);
            if (recent.length === 0) {
                content += `<div class="empty-state">Belum ada buku.</div>`;
            } else {
                recent.forEach(b => {
                    content += `
                        <div class="modal-stat-item">
                            <span class="stat-item-label">${b.title}</span>
                            <span class="stat-item-val">${b.category || '-'}</span>
                        </div>
                    `;
                });
            }
            content += `</div>`;


        } else if (type === 'categories') {
            titleEl.textContent = 'Statistik Kategori';
            const counts = {};
            books.forEach(b => {
                const cat = b.category || 'Lainnya';
                counts[cat] = (counts[cat] || 0) + 1;
            });
            content = `<div class="modal-stat-list">`;
            for (const [key, val] of Object.entries(counts)) {
                content += `
                    <div class="modal-stat-item">
                        <span class="stat-item-label">${key}</span>
                        <span class="stat-item-val">${val} Judul</span>
                    </div>
                `;
            }
            content += `</div>`;
        }

        bodyEl.innerHTML = content;
        modal.style.display = 'block';
    }

    function closeStatModal() {
        document.getElementById('statModal').style.display = 'none';
    }

    /**
     * FEATURE 3: REAL-TIME SEARCH FOR BOOKS
     */
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchBooksList');
        const clearBtn = document.getElementById('clearBooksSearch');
        const grid = document.querySelector('.books-grid');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.book-card-vertical');
                let hasResults = false;

                // Clear any existing no-results message
                const oldNoResults = document.querySelector('.no-results-message');
                if (oldNoResults) oldNoResults.remove();

                cards.forEach(card => {
                    const title = card.querySelector('.book-title').textContent.toLowerCase();
                    const author = card.querySelector('.book-author').textContent.toLowerCase();
                    const category = card.querySelector('.book-category').textContent.toLowerCase();
                    const shelf = card.querySelector('.book-card-footer').textContent.toLowerCase();
                    
                    const isMatch = title.includes(query) || 
                                   author.includes(query) || 
                                   category.includes(query) || 
                                   shelf.includes(query);
                    
                    if (isMatch) {
                        card.style.display = 'flex';
                        card.classList.remove('search-fade-out');
                        card.classList.add('search-fade-in');
                        hasResults = true;
                    } else {
                        card.classList.add('search-fade-out');
                        setTimeout(() => {
                            if (card.classList.contains('search-fade-out')) {
                                card.style.display = 'none';
                            }
                        }, 300);
                    }
                });

                // Show/Hide Clear Button
                if (clearBtn) clearBtn.style.display = query.length > 0 ? 'flex' : 'none';

                // No results handling
                if (!hasResults && query.length > 0) {
                    const noResults = document.createElement('div');
                    noResults.className = 'no-results-message';
                    noResults.innerHTML = `
                        <iconify-icon icon="mdi:book-search-outline" style="font-size: 48px; margin-bottom: 16px; display: block; margin-left: auto; margin-right: auto; opacity: 0.2; color: var(--accent);"></iconify-icon>
                        <div style="font-weight: 700; font-size: 18px; color: var(--text);">Buku tidak ditemukan</div>
                        <p style="color: var(--muted); margin-top: 8px;">Tidak ada buku yang sesuai dengan kata kunci "${query}"</p>
                    `;
                    grid.appendChild(noResults);
                }
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                    searchInput.focus();
                });
            }

            // --- Shortcut Key (Ctrl+K or Cmd+K) ---
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
        }
    });

    // Global Close Click Outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
  </script>

</body>

</html>