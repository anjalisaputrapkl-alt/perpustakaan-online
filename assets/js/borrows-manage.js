let html5QrcodeScanner = null;
let currentScanMode = 'book';
let currentMember = null;
let scannedBooks = [];
let isScanning = false;
let lastScanTime = 0;
const SCAN_COOLDOWN = 1500;

function toggleScanner() {
    // Robust mobile detection
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(navigator.userAgent) || window.innerWidth <= 1024;

    // If mobile, go to dedicated mobile scanner page
    if (isMobile) {
        console.log('Detected mobile device, redirecting to scan-mobile.php');
        window.location.href = 'scan-mobile.php';
        return;
    }

    const section = document.getElementById('scannerSection');
    const btnText = document.getElementById('scannerToggleText');

    if (section.style.display === 'none') {
        section.style.display = 'block';
        btnText.textContent = 'Tutup Peminjaman';
        initScanner();
    } else {
        stopScanner();
        section.style.display = 'none';
        btnText.textContent = 'Mulai Peminjaman Baru';
    }
}

function initScanner() {
    if (html5QrcodeScanner) return;

    html5QrcodeScanner = new Html5Qrcode("reader");
    html5QrcodeScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 150 } },
        onScanSuccess,
        (error) => { }
    ).catch(err => {
        console.error("Error starting scanner", err);
        showScanStatus("Gagal membuka kamera: " + err, 'error');
    });

    // Initialize Due Date
    const date = new Date();
    date.setDate(date.getDate() + (window.defaultBorrowDays || 7));
    document.getElementById('borrowDueDate').valueAsDate = date;

    setScanMode('book');
}

function stopScanner() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
        }).catch(err => console.error(err));
    }
}

function setScanMode(mode) {
    currentScanMode = mode;
    document.getElementById('btnModeBook').className = mode === 'book' ? 'scanner-mode-btn active' : 'scanner-mode-btn';
    document.getElementById('btnModeMember').className = mode === 'member' ? 'scanner-mode-btn active' : 'scanner-mode-btn';

    const status = mode === 'book' ? 'Mode: Scan Buku' : 'Mode: Scan Anggota';
    showScanStatus(status, 'info');
}

function showScanStatus(msg, type) {
    const el = document.getElementById('scanStatus');
    el.textContent = msg;
    el.className = 'status-' + type;
    el.style.display = 'block';

    if (type === 'success') {
        setTimeout(() => { el.style.display = 'none'; }, 3000);
    }
}

function onScanSuccess(decodedText, decodedResult) {
    const now = Date.now();
    if (now - lastScanTime < SCAN_COOLDOWN) return;
    lastScanTime = now;

    processScannedCode(decodedText.trim());
}

function parseBarcode(raw) {
    // Common cleaning
    const patterns = [
        /^(?:NISN|nisn|ID|id)[:\-=\s]?(.+)$/,
        /^(?:ISBN|isbn)[:\-=\s]?(.+)$/,
        /^[\*=](.+)[\*=]$/
    ];
    for (let p of patterns) {
        const m = raw.match(p);
        if (m && m[1]) return m[1].trim();
    }
    return raw.trim();
}

async function processScannedCode(rawCode) {
    const code = parseBarcode(rawCode);

    document.getElementById('scannerLoading').style.display = 'flex';

    try {
        const res = await fetch('api/process-barcode.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode: code })
        });
        const data = await res.json();

        if (!data.success) {
            showScanStatus('Data tidak ditemukan', 'error');
            document.getElementById('scannerLoading').style.display = 'none';
            return;
        }

        const item = data.data;

        if (currentScanMode === 'book') {
            if (item.type === 'member') {
                // Auto switch to member mode
                currentMember = item;

                // VALIDATE EXISTING CART (Fix for Bug #816)
                if (currentMember.role === 'student') {
                    const validBooks = [];
                    let removedCount = 0;
                    scannedBooks.forEach(b => {
                        if (b.access_level === 'teacher_only') {
                            removedCount++;
                        } else {
                            validBooks.push(b);
                        }
                    });

                    if (removedCount > 0) {
                        scannedBooks = validBooks;
                        alert(`Peringatan: ${removedCount} buku dihapus dari keranjang karena khusus Guru/Karyawan.`);
                    }
                }

                updateMemberDisplay();
                setScanMode('book'); // Switch back to book to continue scanning books? Or maybe stay? Borrowing flow usually implies books first then member.
                showScanStatus('Anggota terdeteksi: ' + item.name, 'success');
            } else {
                // Validate against current member if exists
                if (currentMember && currentMember.role === 'student' && item.access_level === 'teacher_only') {
                    showScanStatus('Buku KHUSUS GURU! Tidak bisa dipinjam siswa.', 'error');
                    alert('PERINGATAN: Buku ini khusus untuk Guru & Karyawan!');
                    return;
                }

                addBookToCart(item);
                showScanStatus('Buku ditambahkan: ' + item.name, 'success');
            }
        } else { // Member mode
            if (item.type === 'book') {
                addBookToCart(item);
                showScanStatus('Buku ditambahkan (Auto switch to Book Mode)', 'info');
                setScanMode('book');
            } else {
                currentMember = item;

                // VALIDATE EXISTING CART
                if (currentMember.role === 'student') {
                    const validBooks = [];
                    let removedCount = 0;
                    scannedBooks.forEach(b => {
                        if (b.access_level === 'teacher_only') {
                            removedCount++;
                        } else {
                            validBooks.push(b);
                        }
                    });

                    if (removedCount > 0) {
                        scannedBooks = validBooks;
                        alert(`Peringatan: ${removedCount} buku dihapus dari keranjang karena khusus Guru/Karyawan.`);
                    }
                }

                updateMemberDisplay();
                showScanStatus('Anggota di-set: ' + item.name, 'success');
                setScanMode('book');
            }
        }

    } catch (e) {
        showScanStatus('Error: ' + e.message, 'error');
    }

    document.getElementById('scannerLoading').style.display = 'none';
}

function addBookToCart(book) {
    if (scannedBooks.some(b => b.id === book.id)) {
        showScanStatus('Buku sudah ada di keranjang', 'error');
        return;
    }
    scannedBooks.push(book);
    updateCartDisplay();
}

function removeBookFromCart(index) {
    scannedBooks.splice(index, 1);
    updateCartDisplay();
}

function updateMemberDisplay() {
    const el = document.getElementById('scannedMemberInfo');
    if (currentMember) {
        el.style.display = 'block';
        document.getElementById('scannedMemberName').textContent = currentMember.name;
        document.getElementById('scannedMemberNisn').textContent = currentMember.barcode;

        // Highlight logic if needed
    } else {
        el.style.display = 'none';
    }
    updateCartDisplay(); // To check submit button state
}

function escapeHtml(text) {
    if (!text) return text;
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function updateCartDisplay() {
    const list = document.getElementById('scannedBooksList');
    const empty = document.getElementById('scanEmptyState');
    const container = document.getElementById('scannedBooksContainer');

    if (scannedBooks.length === 0) {
        empty.style.display = 'block';
        container.style.display = 'none';
    } else {
        empty.style.display = 'none';
        container.style.display = 'block';

        list.innerHTML = scannedBooks.map((b, i) => `
            <tr>
                <td>
                    ${b.cover_image
                ? `<img src="../img/covers/${escapeHtml(b.cover_image)}" style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">`
                : `<div style="width: 40px; height: 60px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;"><small>No Img</small></div>`
            }
                </td>
                <td>
                    <div style="font-weight: 600; font-size: 13px;">${escapeHtml(b.name)}</div>
                    <div style="font-size: 11px; color: #888;">${escapeHtml(b.barcode)}</div>
                </td>
                <td>
                    <button onclick="removeBookFromCart(${i})" style="color: #ef4444; background: none; border: none; cursor: pointer;">
                        <iconify-icon icon="mdi:close-circle" style="font-size: 18px;"></iconify-icon>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // validate submit button
    const btn = document.getElementById('btnSubmitBorrow');
    if (scannedBooks.length > 0 && currentMember) {
        btn.disabled = false;
        btn.style.opacity = 1;
        btn.textContent = `Pinjam ${scannedBooks.length} Buku`;
    } else {
        btn.disabled = true;
        btn.style.opacity = 0.6;
        if (!currentMember) btn.textContent = 'Scan Anggota Dulu';
        else btn.textContent = 'Scan Buku Dulu';
    }
}

// --- Unified Filtering Logic ---
function initFilter(inputId, targetSelector, isCard = false) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const clearBtn = input.parentElement.querySelector('.search-clear');

    input.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const container = input.closest('.card');
        const items = container.querySelectorAll(targetSelector);
        let visibleCount = 0;

        if (clearBtn) clearBtn.style.display = query.length > 0 ? 'flex' : 'none';

        items.forEach(item => {
            const searchText = item.getAttribute('data-search-content') || item.innerText.toLowerCase();
            const isMatch = searchText.includes(query);

            if (isMatch) {
                item.style.display = isCard ? 'block' : '';
                item.classList.remove('search-fade-out');
                item.classList.add('search-fade-in');
                visibleCount++;
            } else {
                item.classList.add('search-fade-out');
                item.classList.remove('search-fade-in');
                setTimeout(() => {
                    if (item.classList.contains('search-fade-out')) {
                        item.style.display = 'none';
                    }
                }, 300);
            }
        });

        // Handle "No results" message
        let noResults = container.querySelector('.no-results-message');
        if (visibleCount === 0 && query !== '') {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results-message';
                noResults.innerHTML = `
                    <iconify-icon icon="mdi:magnify-close" style="font-size: 32px; margin-bottom: 8px;"></iconify-icon>
                    <p>Tidak ditemukan hasil untuk "${escapeHtml(query)}"</p>
                `;
                const targetParent = isCard ? container : container.querySelector('tbody');
                if (isCard) container.appendChild(noResults);
                else {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 10;
                    cell.style.border = 'none';
                    cell.appendChild(noResults);
                    row.className = 'no-results-row';
                    row.appendChild(cell);
                    targetParent.appendChild(row);
                }
            }
        } else if (noResults) {
            if (isCard) noResults.remove();
            else noResults.closest('tr').remove();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initFilter('searchPending', '.pending-borrow-card', true);
    initFilter('searchRequests', '.borrows-table tbody tr:not(.no-results-row)');
    initFilter('searchActive', '.borrows-table tbody tr:not(.no-results-row)');
    initFilter('searchHistory', '.borrows-table tbody tr:not(.no-results-row)');

    // Global Shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const firstSearch = document.querySelector('.search-input');
            if (firstSearch) firstSearch.focus();
        }
    });
});

function resetScannerSession() {
    scannedBooks = [];
    currentMember = null;
    updateMemberDisplay();
    updateCartDisplay();
    showScanStatus('Sesi direset', 'info');
}

async function submitBorrow() {
    if (!currentMember || scannedBooks.length === 0) return;

    const dueDate = document.getElementById('borrowDueDate').value;
    if (!dueDate) {
        alert('Pilih tanggal pengembalian');
        return;
    }

    document.getElementById('scannerLoading').style.display = 'flex';

    try {
        const payload = {
            borrows: scannedBooks.map(b => ({
                member_id: currentMember.id,
                book_id: b.id
            })),
            due_date: dueDate + ' 23:59:59'
        };

        const res = await fetch('api/submit-borrow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (result.success && result.inserted > 0) {
            let msg = result.message;
            if (result.errors && result.errors.length > 0) {
                msg += '\n\nPeringatan beberapa item gagal:\n' + result.errors.join('\n');
            }
            alert(msg);
            location.reload();
        } else if (result.success && result.inserted === 0) {
            // All failed
            const errorMsg = result.errors && result.errors.length > 0 ? result.errors.join('\n') : result.message;
            alert('Gagal meminjam:\n' + errorMsg);
        } else {
            alert('Gagal: ' + (result.message || 'Error saving'));
        }

    } catch (e) {
        console.error(e);
        alert('Error submitting borrow');
    }

    document.getElementById('scannerLoading').style.display = 'none';
}

/**
 * Statistics Logic
 */
let initialPendingCount = window.initialPendingCount || 0;
const CHECK_INTERVAL = 5000; // Check every 5 seconds

function checkPendingScans() {
    fetch('api/check-pending-scans.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > initialPendingCount) {
                // New scans detected!
                const sound = document.getElementById('scanNotificationSound');
                if (sound) sound.play().catch(e => console.log('Sound play blocked'));

                // Reload page to show new scans
                setTimeout(() => {
                    location.reload();
                }, 800);
            } else if (data.success) {
                // Update count for next check
                initialPendingCount = data.count;
            }
        })
        .catch(err => console.log('Sync error'));
}

// Approval functions
function approveAllBorrowWithDue(borrowIdsJson, dueInputId) {
    const borrowIds = JSON.parse(borrowIdsJson);
    const globalDueDays = parseInt(document.getElementById(dueInputId).value);

    if (isNaN(globalDueDays) || globalDueDays < 1) {
        alert('Batas pinjam tidak valid.');
        return;
    }

    if (!confirm(`Setujui ${borrowIds.length} peminjaman dengan batas ${globalDueDays} hari?`)) return;

    let approved = 0;
    let failed = 0;
    let errors = [];

    Promise.all(borrowIds.map(async (borrowId) => {
        // Find book_id for this borrow from window data if needed, but here we just approve
        // We'll trust the server to handle the due_at calculation if possible, 
        // but the current implementation sends a fixed due_at.

        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + globalDueDays);
        const dueString = dueDate.toISOString().slice(0, 10) + ' ' + dueDate.toTimeString().slice(0, 8);

        return fetch('api/approve-borrow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'borrow_id=' + borrowId + '&due_at=' + encodeURIComponent(dueString)
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) approved++;
                else {
                    failed++;
                    errors.push(data.message);
                }
            })
            .catch(err => {
                failed++;
                errors.push(err.message);
            });
    })).then(() => {
        if (failed === 0) {
            alert(`✓ ${approved} peminjaman telah diterima!`);
            location.reload();
        } else {
            alert(`${approved} diterima, ${failed} gagal.\n\nContoh error: ${errors[0]}`);
            location.reload();
        }
    });
}

function toggleSelectAll(studentId, source) {
    const checkboxes = document.querySelectorAll('.book-checkbox-' + studentId);
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function approveSelectedBorrows(studentId) {
    const checkboxes = document.querySelectorAll('.book-checkbox-' + studentId + ':checked');
    if (checkboxes.length === 0) {
        alert('Pilih setidaknya satu buku untuk disetujui.');
        return;
    }

    const selectedIds = [];
    checkboxes.forEach((cb) => {
        selectedIds.push(cb.value);
    });

    // Call the original function with selected IDs
    approveAllBorrowWithDue(JSON.stringify(selectedIds), 'dueDays_' + studentId);
}

// Start polling if not on mobile
window.addEventListener('load', () => {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(navigator.userAgent) || window.innerWidth <= 1024;
    if (!isMobile) {
        setInterval(checkPendingScans, CHECK_INTERVAL);
    }
});
