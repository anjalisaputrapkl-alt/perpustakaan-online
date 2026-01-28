# KODE LENGKAP - Interactive Statistics Cards

Dokumentasi kode lengkap untuk referensi. Semua file ini sudah diintegrasikan ke project.

---

## 1. FILE: /public/api/get-stats-books.php

Endpoint untuk fetch data total buku dengan stok dan status.

```php
<?php
header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.title,
            b.author,
            b.category,
            b.copies,
            (SELECT COUNT(*) FROM borrows WHERE book_id = b.id AND returned_at IS NULL AND school_id = :sid) as borrowed_count
        FROM books b
        WHERE b.school_id = :sid
        ORDER BY b.created_at DESC
    ");
    
    $stmt->execute(['sid' => $school_id]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($books as $book) {
        $available = max(0, $book['copies'] - $book['borrowed_count']);
        $data[] = [
            'id' => $book['id'],
            'title' => htmlspecialchars($book['title']),
            'author' => htmlspecialchars($book['author'] ?? '-'),
            'category' => htmlspecialchars($book['category']),
            'total' => $book['copies'],
            'borrowed' => $book['borrowed_count'],
            'available' => $available,
            'status' => $available > 0 ? 'Tersedia' : 'Habis'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
```

---

## 2. FILE: /public/api/get-stats-members.php

Endpoint untuk fetch data anggota perpustakaan.

```php
<?php
header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.name,
            m.nisn,
            m.email,
            m.status,
            m.created_at,
            (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND returned_at IS NULL AND school_id = :sid) as current_borrows
        FROM members m
        WHERE m.school_id = :sid
        ORDER BY m.created_at DESC
    ");
    
    $stmt->execute(['sid' => $school_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($members as $member) {
        $data[] = [
            'id' => $member['id'],
            'name' => htmlspecialchars($member['name']),
            'nisn' => htmlspecialchars($member['nisn'] ?? '-'),
            'email' => htmlspecialchars($member['email'] ?? '-'),
            'status' => $member['status'] == 'active' ? 'Aktif' : 'Nonaktif',
            'current_borrows' => $member['current_borrows'],
            'joined_date' => date('d M Y', strtotime($member['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
```

---

## 3. FILE: /public/api/get-stats-borrowed.php

Endpoint untuk fetch data buku yang sedang dipinjam.

```php
<?php
header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            b.title,
            b.author,
            m.name as member_name,
            m.nisn,
            br.borrowed_at,
            br.due_at,
            br.status,
            DATEDIFF(br.due_at, NOW()) as days_remaining
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid AND br.returned_at IS NULL
        ORDER BY br.borrowed_at DESC
    ");
    
    $stmt->execute(['sid' => $school_id]);
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($borrows as $borrow) {
        $days = $borrow['days_remaining'];
        $status_display = 'Sedang Dipinjam';
        if ($days < 0) {
            $status_display = 'TERLAMBAT (' . abs($days) . ' hari)';
        } elseif ($days <= 3) {
            $status_display = 'Akan Jatuh Tempo (' . $days . ' hari)';
        }
        
        $data[] = [
            'id' => $borrow['id'],
            'book_title' => htmlspecialchars($borrow['title']),
            'book_author' => htmlspecialchars($borrow['author'] ?? '-'),
            'member_name' => htmlspecialchars($borrow['member_name']),
            'member_nisn' => htmlspecialchars($borrow['nisn'] ?? '-'),
            'borrowed_date' => date('d M Y', strtotime($borrow['borrowed_at'])),
            'due_date' => date('d M Y', strtotime($borrow['due_at'])),
            'days_remaining' => $borrow['days_remaining'],
            'status' => $status_display
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
```

---

## 4. FILE: /public/api/get-stats-overdue.php

Endpoint untuk fetch data peminjaman yang terlambat.

```php
<?php
header('Content-Type: application/json');
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            b.title,
            b.author,
            m.name as member_name,
            m.nisn,
            br.borrowed_at,
            br.due_at,
            br.status,
            DATEDIFF(NOW(), br.due_at) as days_overdue
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE br.school_id = :sid AND br.returned_at IS NULL AND br.status = 'overdue'
        ORDER BY br.due_at ASC
    ");
    
    $stmt->execute(['sid' => $school_id]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($overdue as $item) {
        $data[] = [
            'id' => $item['id'],
            'book_title' => htmlspecialchars($item['title']),
            'book_author' => htmlspecialchars($item['author'] ?? '-'),
            'member_name' => htmlspecialchars($item['member_name']),
            'member_nisn' => htmlspecialchars($item['nisn'] ?? '-'),
            'borrowed_date' => date('d M Y', strtotime($item['borrowed_at'])),
            'due_date' => date('d M Y', strtotime($item['due_at'])),
            'days_overdue' => $item['days_overdue']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
```

---

## 5. FILE: /assets/js/stats-modal.js

JavaScript untuk modal management dan AJAX calls.

```javascript
// Modal Management System
const modalManager = {
    currentModal: null,
    
    init() {
        // Setup modal close listeners
        document.addEventListener('DOMContentLoaded', () => {
            const overlay = document.getElementById('statsModal');
            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        this.closeModal();
                    }
                });

                const closeBtn = document.querySelector('.modal-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        this.closeModal();
                    });
                }
            }

            // Setup card click listeners
            this.setupCardListeners();
        });
    },

    setupCardListeners() {
        const stats = document.querySelectorAll('.stat');
        stats.forEach(stat => {
            stat.addEventListener('click', () => {
                const type = stat.dataset.statType;
                this.openModal(type);
            });
        });
    },

    openModal(type) {
        const overlay = document.getElementById('statsModal');
        const container = document.querySelector('.modal-container');
        
        if (!overlay || !container) return;

        // Reset content
        const body = document.querySelector('.modal-body');
        body.innerHTML = '<div class="modal-loading">Memuat data...</div>';

        // Show overlay
        overlay.classList.add('active');

        // Set title based on type
        const titles = {
            'books': 'Daftar Semua Buku',
            'members': 'Daftar Anggota',
            'borrowed': 'Buku yang Sedang Dipinjam',
            'overdue': 'Peminjaman Terlambat'
        };
        
        document.querySelector('.modal-header h2').textContent = titles[type] || 'Detail Data';

        // Fetch data based on type
        this.fetchAndDisplayData(type);
    },

    closeModal() {
        const overlay = document.getElementById('statsModal');
        if (overlay) {
            overlay.classList.remove('active');
        }
    },

    async fetchAndDisplayData(type) {
        const endpoints = {
            'books': '/perpustakaan-online/public/api/get-stats-books.php',
            'members': '/perpustakaan-online/public/api/get-stats-members.php',
            'borrowed': '/perpustakaan-online/public/api/get-stats-borrowed.php',
            'overdue': '/perpustakaan-online/public/api/get-stats-overdue.php'
        };

        try {
            const response = await fetch(endpoints[type] || endpoints.books);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.displayData(type, result.data);
            } else {
                this.displayError(result.message || 'Terjadi kesalahan saat memuat data');
            }
        } catch (error) {
            console.error('Error:', error);
            this.displayError('Gagal memuat data. Silakan coba lagi.');
        }
    },

    displayData(type, data) {
        const body = document.querySelector('.modal-body');

        if (!data || data.length === 0) {
            body.innerHTML = '<div class="modal-empty">Tidak ada data untuk ditampilkan</div>';
            return;
        }

        let html = '<table class="modal-table">';

        // Create table based on type
        if (type === 'books') {
            html += `
                <thead>
                    <tr>
                        <th>Judul Buku</th>
                        <th class="col-hide-mobile">Penulis</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(book => {
                html += `
                    <tr>
                        <td><strong>${book.title}</strong></td>
                        <td class="col-hide-mobile">${book.author}</td>
                        <td>${book.category}</td>
                        <td>${book.available}/${book.total}</td>
                        <td>
                            <span class="status-badge ${book.available > 0 ? 'available' : 'unavailable'}">
                                ${book.status}
                            </span>
                        </td>
                    </tr>
                `;
            });
        } else if (type === 'members') {
            html += `
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="col-hide-mobile">NISN</th>
                        <th class="col-hide-mobile">Email</th>
                        <th>Status</th>
                        <th>Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(member => {
                html += `
                    <tr>
                        <td><strong>${member.name}</strong></td>
                        <td class="col-hide-mobile">${member.nisn}</td>
                        <td class="col-hide-mobile">${member.email}</td>
                        <td>
                            <span class="status-badge ${member.status === 'Aktif' ? 'active' : 'inactive'}">
                                ${member.status}
                            </span>
                        </td>
                        <td>${member.current_borrows}</td>
                    </tr>
                `;
            });
        } else if (type === 'borrowed') {
            html += `
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th class="col-hide-mobile">Peminjam</th>
                        <th class="col-hide-mobile">Tgl Peminjaman</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(borrow => {
                const isOverdue = borrow.days_remaining < 0;
                html += `
                    <tr>
                        <td>
                            <strong>${borrow.book_title}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${borrow.book_author}</div>
                        </td>
                        <td class="col-hide-mobile">${borrow.member_name}</td>
                        <td class="col-hide-mobile">${borrow.borrowed_date}</td>
                        <td>${borrow.due_date}</td>
                        <td>
                            <span class="status-badge ${isOverdue ? 'overdue' : 'borrowed'}">
                                ${borrow.status}
                            </span>
                        </td>
                    </tr>
                `;
            });
        } else if (type === 'overdue') {
            html += `
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th class="col-hide-mobile">Peminjam</th>
                        <th class="col-hide-mobile">Tgl Peminjaman</th>
                        <th>Jatuh Tempo</th>
                        <th>Terlambat</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <strong>${item.book_title}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${item.book_author}</div>
                        </td>
                        <td class="col-hide-mobile">${item.member_name}</td>
                        <td class="col-hide-mobile">${item.borrowed_date}</td>
                        <td>${item.due_date}</td>
                        <td>
                            <span class="status-badge overdue">
                                ${item.days_overdue} hari
                            </span>
                        </td>
                    </tr>
                `;
            });
        }

        html += '</tbody></table>';
        body.innerHTML = html;
    },

    displayError(message) {
        const body = document.querySelector('.modal-body');
        body.innerHTML = `<div class="modal-empty" style="color: var(--danger);">${message}</div>`;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    modalManager.init();
});
```

---

## 6. CSS ADDITIONS (Added to /assets/css/index.css)

```css
/* Interactive Stats Card */
.stat {
  cursor: pointer;
  transition: all 0.3s ease;
}

.stat:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
  border-color: var(--accent);
}

.stat::after {
  content: attr(data-tooltip);
  position: absolute;
  bottom: 120%;
  left: 50%;
  transform: translateX(-50%);
  background: #1f2937;
  color: #fff;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

.stat::before {
  content: '';
  border: 6px solid transparent;
  border-top-color: #1f2937;
  opacity: 0;
}

.stat:hover::after,
.stat:hover::before {
  opacity: 1;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: none;
  z-index: 1000;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.modal-overlay.active {
  display: flex;
  opacity: 1;
  align-items: center;
  justify-content: center;
}

.modal-container {
  background: var(--surface);
  border-radius: 12px;
  max-width: 900px;
  width: 90%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
  transform: scale(0.95);
  transition: transform 0.3s ease;
}

.modal-overlay.active .modal-container {
  transform: scale(1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  transition: all 0.2s;
}

.modal-close:hover {
  background: var(--border);
  border-radius: 6px;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px;
}

.modal-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.modal-table th {
  padding: 12px;
  text-align: left;
  font-weight: 600;
  background: var(--border);
}

.modal-table td {
  padding: 12px;
  border-bottom: 1px solid var(--border);
}

.modal-table tbody tr:hover {
  background: var(--border);
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.available {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.unavailable {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.borrowed {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.overdue {
  background: #fee2e2;
  color: #991b1b;
}

.modal-loading {
  text-align: center;
  padding: 40px;
  color: var(--muted);
}

.modal-empty {
  text-align: center;
  padding: 40px 20px;
  color: var(--muted);
}

@media (max-width: 768px) {
  .modal-container {
    width: 95%;
  }
  
  .col-hide-mobile {
    display: none;
  }
}
```

---

## 7. HTML UPDATES (in /public/index.php)

### Card dengan Tooltip:
```html
<div class="stats">
    <div class="stat" data-stat-type="books" data-tooltip="Total seluruh buku yang sudah terdaftar di perpustakaan">
        <small>Total Buku</small><strong><?= $total_books ?></strong>
    </div>
    <div class="stat" data-stat-type="members" data-tooltip="Total seluruh anggota perpustakaan yang terdaftar">
        <small>Total Anggota</small><strong><?= $total_members ?></strong>
    </div>
    <div class="stat" data-stat-type="borrowed" data-tooltip="Total buku yang sedang dipinjam oleh anggota">
        <small>Dipinjam</small><strong><?= $total_borrowed ?></strong>
    </div>
    <div class="stat alert" data-stat-type="overdue" data-tooltip="Total peminjaman yang sudah melewati batas waktu pengembalian">
        <small>Terlambat</small><strong><?= $total_overdue ?></strong>
    </div>
</div>
```

### Modal HTML:
```html
<div class="modal-overlay" id="statsModal">
    <div class="modal-container">
        <div class="modal-header">
            <h2>Detail Data</h2>
            <button class="modal-close" type="button">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-loading">Memuat data...</div>
        </div>
    </div>
</div>
```

### Script Include:
```html
<script src="../assets/js/stats-modal.js"></script>
```

---

Semua kode lengkap sudah terintegrasi. Tidak ada perubahan pada struktur database!
