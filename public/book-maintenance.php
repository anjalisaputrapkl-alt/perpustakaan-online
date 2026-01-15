<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/maintenance/MaintenanceController.php';

$controller = new MaintenanceController($pdo);

// Handle AJAX requests - SEBELUM include header!
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
  $controller->handleAjax();
  exit; // Pastikan stop setelah AJAX
}

// Include header hanya untuk non-AJAX requests
include __DIR__ . '/partials/header.php';

// Get all records and books
$records = $controller->getAll();
$books = $controller->getBooks();
$totalRecords = $controller->getCount();

?>
<script src="../assets/js/theme.js"></script>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
  .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
  }
  .status-good { background: #d4edda; color: #155724; }
  .status-worn { background: #fff3cd; color: #856404; }
  .status-damaged { background: #f8d7da; color: #721c24; }
  .status-missing { background: #e2e3e5; color: #383d41; }
  .status-repair { background: #cce5ff; color: #004085; }
  .status-replaced { background: #d1ecf1; color: #0c5460; }

  .form-group {
    margin-bottom: 16px;
  }
  .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
    font-size: 14px;
  }
  .form-group textarea {
    resize: vertical;
    min-height: 80px;
  }
  .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
  }
  .btn-primary {
    background: #0ea5e9;
    color: white;
  }
  .btn-primary:hover {
    background: #0284c7;
  }
  .btn-secondary {
    background: #6b7280;
    color: white;
  }
  .btn-secondary:hover {
    background: #4b5563;
  }
  .btn-danger {
    background: #ef4444;
    color: white;
  }
  .btn-danger:hover {
    background: #dc2626;
  }
  .btn-sm {
    padding: 4px 8px;
    font-size: 12px;
  }

  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
  }
  .modal.active {
    display: flex !important;
  }
  .modal-content {
    background: white;
    padding: 24px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  }
  .modal-header {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    border-bottom: 1px solid #eee;
    padding-bottom: 12px;
  }
  .modal-body {
    margin-bottom: 16px;
  }
  .modal-footer {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
  }

  .table-responsive {
    overflow-x: auto;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
  }
  table th {
    background: #f3f4f6;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #ddd;
    font-size: 13px;
  }
  table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
  }
  table tr:hover {
    background: #f9fafb;
  }
  
  .action-buttons {
    display: flex;
    gap: 4px;
  }

  .stats {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
  }
  .stat-card {
    background: white;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #0ea5e9;
    flex: 1;
  }
  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #0ea5e9;
  }
  .stat-label {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
  }

  .toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 16px;
    background: #333;
    color: white;
    border-radius: 4px;
    z-index: 2000;
    animation: slideIn 0.3s;
  }
  @keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
</style>

<div style="background: #f5f5f5; min-height: 100vh; padding: 20px;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <header style="margin-bottom: 24px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h1 style="margin: 0; font-size: 24px;">📚 Book Maintenance</h1>
          <p style="margin: 4px 0 0 0; color: #666; font-size: 14px;">Kelola kondisi dan status buku perpustakaan</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()" style="background: #0ea5e9; color: white; padding: 10px 16px; font-size: 14px; cursor: pointer; border: none; border-radius: 4px; font-weight: 600;">+ Tambah Catatan</button>
      </div>
    </header>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-value"><?php echo $totalRecords; ?></div>
      <div class="stat-label">Total Catatan</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" id="goodCount">0</div>
      <div class="stat-label">Status Baik</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" id="damagedCount">0</div>
      <div class="stat-label">Rusak/Perlu Perbaikan</div>
    </div>
  </div>

  <!-- Table -->
  <div style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0;">Daftar Catatan Maintenance</h3>
    
    <?php if (empty($records)): ?>
      <p style="text-align: center; color: #999; padding: 32px 0;">Belum ada catatan maintenance. <a href="#" onclick="openAddModal(); return false;">Buat catatan pertama</a></p>
    <?php else: ?>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Judul Buku</th>
              <th>Penulis</th>
              <th>Status</th>
              <th>Catatan</th>
              <th>Terakhir Update</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['id']); ?></td>
                <td><?php echo htmlspecialchars($r['book_title']); ?></td>
                <td><?php echo htmlspecialchars($r['book_author']); ?></td>
                <td>
                  <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $r['status'])); ?>">
                    <?php echo htmlspecialchars($r['status']); ?>
                  </span>
                </td>
                <td style="max-width: 200px; word-break: break-word;">
                  <?php echo $r['notes'] ? htmlspecialchars(substr($r['notes'], 0, 50)) . (strlen($r['notes']) > 50 ? '...' : '') : '<em style="color: #999;">-</em>'; ?>
                </td>
                <td style="font-size: 12px; color: #666;">
                  <?php echo date('d M Y H:i', strtotime($r['updated_at'])); ?>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo $r['id']; ?>)">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteRecord(<?php echo $r['id']; ?>)">Hapus</button>
                  </div>
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

<!-- Modal Add/Edit -->
<div id="maintenanceModal" class="modal">
  <div class="modal-content">
    <div class="modal-header" id="modalTitle">Tambah Catatan Maintenance</div>
    <div class="modal-body">
      <form id="maintenanceForm">
        <input type="hidden" id="recordId" name="id" value="">
        
        <div class="form-group">
          <label for="bookId">Pilih Buku <span style="color: red;">*</span></label>
          <select id="bookId" name="book_id" required>
            <option value="">-- Pilih Buku --</option>
            <?php foreach ($books as $b): ?>
              <option value="<?php echo $b['id']; ?>">
                <?php echo htmlspecialchars($b['title']) . ' - ' . htmlspecialchars($b['author']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Status <span style="color: red;">*</span></label>
          <select id="status" name="status" required>
            <option value="">-- Pilih Status --</option>
            <option value="Good">Good (Bagus)</option>
            <option value="Worn Out">Worn Out (Aus)</option>
            <option value="Damaged">Damaged (Rusak)</option>
            <option value="Missing">Missing (Hilang)</option>
            <option value="Need Repair">Need Repair (Perlu Perbaikan)</option>
            <option value="Replaced">Replaced (Diganti)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="notes">Catatan / Keterangan</label>
          <textarea id="notes" name="notes" placeholder="Deskripsikan kondisi buku secara detail..."></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
      <button class="btn btn-primary" onclick="saveRecord()">Simpan</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.style.background = type === 'success' ? '#10b981' : '#ef4444';
  toast.innerText = msg;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'slideIn 0.3s reverse';
    setTimeout(() => toast.remove(), 300);
  }, 2500);
}

function openAddModal() {
  document.getElementById('recordId').value = '';
  document.getElementById('maintenanceForm').reset();
  document.getElementById('modalTitle').innerText = 'Tambah Catatan Maintenance';
  document.getElementById('maintenanceModal').classList.add('active');
}

function openEditModal(id) {
  fetch('?action=get&id=' + id)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.data) {
        const record = data.data;
        document.getElementById('recordId').value = record.id;
        document.getElementById('bookId').value = record.book_id;
        document.getElementById('status').value = record.status;
        document.getElementById('notes').value = record.notes || '';
        document.getElementById('modalTitle').innerText = 'Edit Catatan Maintenance';
        document.getElementById('maintenanceModal').classList.add('active');
      }
    });
}

function closeModal() {
  document.getElementById('maintenanceModal').classList.remove('active');
}

function saveRecord() {
  const id = document.getElementById('recordId').value;
  const bookId = document.getElementById('bookId').value;
  const status = document.getElementById('status').value;
  const notes = document.getElementById('notes').value;

  if (!bookId || !status) {
    showToast('Buku dan Status harus dipilih!', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('action', id ? 'update' : 'add');
  formData.append('book_id', bookId);
  formData.append('status', status);
  formData.append('notes', notes);
  if (id) formData.append('id', id);

  console.log('Sending request:', {
    action: id ? 'update' : 'add',
    book_id: bookId,
    status: status,
    notes: notes,
    id: id
  });

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => {
      console.log('Response status:', r.status);
      return r.text().then(text => {
        console.log('Response text:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error('Invalid JSON: ' + text);
        }
      });
    })
    .then(data => {
      console.log('Parsed data:', data);
      if (data.success) {
        showToast(data.message);
        closeModal();
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || 'Terjadi kesalahan', 'error');
      }
    })
    .catch(err => {
      console.error('Fetch error:', err);
      showToast('Error: ' + err.message, 'error');
    });
}

function deleteRecord(id) {
  if (!confirm('Yakin hapus catatan ini?')) return;

  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.text().then(text => {
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error('Invalid JSON: ' + text);
      }
    }))
    .then(data => {
      if (data.success) {
        showToast(data.message);
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || 'Terjadi kesalahan', 'error');
      }
    })
    .catch(err => {
      showToast('Error: ' + err.message, 'error');
    });
}

// Update stats on page load
document.addEventListener('DOMContentLoaded', () => {
  const records = <?php echo json_encode($records); ?>;
  const good = records.filter(r => r.status === 'Good').length;
  const damaged = records.filter(r => 
    ['Damaged', 'Need Repair', 'Missing'].includes(r.status)
  ).length;
  
  document.getElementById('goodCount').innerText = good;
  document.getElementById('damagedCount').innerText = damaged;
});

// Close modal on outside click
document.getElementById('maintenanceModal').addEventListener('click', (e) => {
  if (e.target.id === 'maintenanceModal') closeModal();
});
</script>
