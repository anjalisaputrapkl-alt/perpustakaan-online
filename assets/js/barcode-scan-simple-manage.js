let scanner = null;
let scanMode = 'book'; // Default mode Book
let currentMember = null;
let scannedBooks = []; // Array to store scanned books
let lastScannedTime = 0;
const SCAN_DELAY = 1000;
let statusTimeoutId = null;

function initScanner() {
    console.log('[INIT] Starting scanner...');
    scanner = new Html5Qrcode("reader");

    scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 300, height: 150 } },
        onScanSuccess,
        onScanError
    ).then(() => {
        console.log('[SCANNER] Started');
        showStatus('Kamera aktif - siap memindai barcode', 'info');
    }).catch(err => {
        console.error('[SCANNER] Error:', err);
        showStatus('Gagal mengakses kamera', 'error');
    });
}

function onScanSuccess(text) {
    const now = Date.now();
    if (now - lastScannedTime < SCAN_DELAY) return;
    lastScannedTime = now;

    processBarcode(text.trim());
}

function onScanError(error) {
    // Silently ignore
}

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
    console.log('[PROCESS] Barcode:', parsedBarcode, 'Mode:', scanMode);

    showLoading(true);
    showStatus(`Memproses barcode ${scanMode}...`, 'info');

    try {
        const response = await fetch('./api/process-barcode.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode: parsedBarcode })
        });

        const data = await response.json();
        console.log('[API] Response:', data);

        if (!data.success) {
            showStatus('Barcode tidak ditemukan', 'error');
            showLoading(false);
            return;
        }

        // Handle member scan
        if (scanMode === 'member') {
            if (data.data.type !== 'member') {
                // Intelligent auto-switch attempt
                if (data.data.type === 'book') {
                    showStatus('Terdeteksi Buku. Beralih ke mode Buku...', 'info');
                    switchMode('book');
                    processBarcode(barcode); // Recursive call for book
                    return;
                }
                showStatus('Ini buku, bukan anggota!', 'error');
                showLoading(false);
                return;
            }
            currentMember = data.data;
            displayMember();

            // If we have books, show success
            if (scannedBooks.length > 0) {
                showStatus(`Anggota ${currentMember.name} OK. Siap meminjam.`, 'success');
            } else {
                showStatus(`Halo ${currentMember.name}. Silakan scan buku.`, 'success');
                switchMode('book');
            }
        }
        // Handle book scan
        else if (scanMode === 'book') {
            if (data.data.type !== 'book') {
                // Intelligent auto-switch attempt
                if (data.data.type === 'member') {
                    showStatus('Terdeteksi Anggota. Beralih ke mode Anggota...', 'info');
                    switchMode('member');
                    processBarcode(barcode); // Recursive call for member
                    return;
                }
                showStatus('Ini anggota, bukan buku!', 'error');
                showLoading(false);
                return;
            }

            // Add to local array
            // Check duplicate
            if (scannedBooks.some(b => b.book_id === data.data.id)) {
                showStatus('Buku sudah ada di daftar!', 'info');
            } else {
                scannedBooks.push({
                    book_id: data.data.id,
                    book_title: data.data.name,
                    cover_image: data.data.cover_image,
                    max_borrow_days: data.data.max_borrow_days
                });
                updateScannedList();
                showStatus(data.data.name + ' ditambahkan', 'success');

                // If member is not set, encourage setting member
                if (!currentMember) {
                    showStatus(data.data.name + ' OK. Lanjut buku lain atau Scan Anggota.', 'success');
                }
            }
        }

    } catch (error) {
        console.error('[ERROR]', error);
        showStatus('Error: ' + error.message, 'error');
    }

    showLoading(false);
}

function updateScannedList() {
    const container = document.getElementById('scannedItems');
    const tbody = document.getElementById('scannedItemsList');
    const actionButtons = document.getElementById('actionButtons');
    const scanCount = document.getElementById('scanCount');

    if (scannedBooks.length === 0) {
        if (container) container.style.display = 'none';
        if (actionButtons) actionButtons.style.display = 'none';
        if (scanCount) scanCount.style.display = 'none';
    } else {
        if (container) container.style.display = 'block';
        if (actionButtons) actionButtons.style.display = 'flex';
        if (scanCount) scanCount.style.display = 'block';

        const bookCountEl = document.getElementById('bookCount');
        if (bookCountEl) bookCountEl.textContent = scannedBooks.length;

        if (tbody) {
            tbody.innerHTML = scannedBooks.map((book, index) => {
                let coverHtml = '';
                if (book.cover_image) {
                    coverHtml = '<img src="../img/covers/' + escapeHtml(book.cover_image) + '" style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">';
                } else {
                    coverHtml = '<div style="width: 40px; height: 60px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;"><small>No Img</small></div>';
                }

                let maxDaysHtml = '';
                if (book.max_borrow_days) {
                    maxDaysHtml = '<br><span style="color: #e74c3c; font-size: 10px; font-weight: 700;">Batas: ' + book.max_borrow_days + ' Hari</span>';
                }

                return '<tr>' +
                    '<td style="width: 50px;">' + coverHtml + '</td>' +
                    '<td>' + escapeHtml(book.book_title) + maxDaysHtml + '</td>' +
                    '<td><button class="btn-remove" onclick="removeBook(' + index + ')">Hapus</button></td>' +
                    '</tr>';
            }).join('');
        }
    }
}

function removeBook(index) {
    scannedBooks.splice(index, 1);
    updateScannedList();
}

function clearScannedBooks() {
    if (confirm('Hapus semua buku yang sudah di-scan?')) {
        scannedBooks = [];
        updateScannedList();
        showStatus('Daftar buku telah dihapus', 'info');
    }
}

async function submitScannedBooks() {
    if (scannedBooks.length === 0) {
        showStatus('Tidak ada buku untuk dikirim', 'error');
        return;
    }

    if (!currentMember) {
        showStatus('Harap scan KARTU ANGGOTA terlebih dahulu!', 'error');
        switchMode('member');
        // Pulse member button
        const btn = document.getElementById('btnModeMember');
        if (btn) {
            btn.style.borderColor = 'red';
            setTimeout(() => btn.style.borderColor = '', 1000);
        }
        return;
    }

    const btnSubmit = document.getElementById('btnSubmit');
    const btnClear = document.getElementById('btnClear');
    if (btnSubmit) btnSubmit.disabled = true;
    if (btnClear) btnClear.disabled = true;

    showLoading(true);
    showStatus('Mengirim data...', 'info');

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
        console.log('[SUBMIT] Response:', data);

        if (data.success) {
            showStatus('Selesai! Buku berhasil dipinjam untuk ' + currentMember.name, 'success');
            scannedBooks = [];
            updateScannedList();
            currentMember = null;
            const memberDisplay = document.getElementById('memberDisplay');
            if (memberDisplay) memberDisplay.classList.remove('show');
            switchMode('book'); // Ready for next person
        } else {
            showStatus('Error: ' + (data.message || 'Gagal menyimpan'), 'error');
            if (btnSubmit) btnSubmit.disabled = false;
            if (btnClear) btnClear.disabled = false;
        }
    } catch (error) {
        console.error('[SUBMIT] Error:', error);
        showStatus('Error: ' + error.message, 'error');
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnClear) btnClear.disabled = false;
    }

    showLoading(false);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function switchMode(mode) {
    scanMode = mode;
    const btnMember = document.getElementById('btnModeMember');
    const btnBook = document.getElementById('btnModeBook');

    if (mode === 'member') {
        if (btnMember) {
            btnMember.classList.add('active');
            btnMember.classList.remove('inactive');
        }
        if (btnBook) {
            btnBook.classList.remove('active');
            btnBook.classList.add('inactive');
        }
    } else {
        if (btnMember) {
            btnMember.classList.remove('active');
            btnMember.classList.add('inactive');
        }
        if (btnBook) {
            btnBook.classList.add('active');
            btnBook.classList.remove('inactive');
        }
    }
}

function displayMember() {
    const nameEl = document.getElementById('memberName');
    const nisnEl = document.getElementById('memberNisn');
    const displayEl = document.getElementById('memberDisplay');
    if (nameEl) nameEl.textContent = currentMember.name;
    if (nisnEl) nisnEl.textContent = currentMember.barcode;
    if (displayEl) displayEl.classList.add('show');
}

function showStatus(message, type = 'info') {
    const div = document.getElementById('statusMessage');
    if (!div) return;
    if (statusTimeoutId) clearTimeout(statusTimeoutId);

    div.textContent = message;
    div.className = 'status-message ' + type;
    div.style.display = 'block';

    if (type === 'success') {
        statusTimeoutId = setTimeout(() => {
            div.style.display = 'none';
        }, 4000);
    }
}

function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) return;
    if (show) {
        overlay.classList.add('show');
    } else {
        overlay.classList.remove('show');
    }
}

// Event Listeners
const btnLogout = document.getElementById('btnLogout');
if (btnLogout) {
    btnLogout.addEventListener('click', () => {
        if (confirm('Logout?')) {
            if (scanner) scanner.stop();
            location.href = './logout.php';
        }
    });
}

const btnModeMember = document.getElementById('btnModeMember');
if (btnModeMember) {
    btnModeMember.addEventListener('click', () => {
        switchMode('member');
    });
}

const btnModeBook = document.getElementById('btnModeBook');
if (btnModeBook) {
    btnModeBook.addEventListener('click', () => {
        if (!currentMember) {
            showStatus('Scan anggota dulu!', 'error');
        } else {
            switchMode('book');
        }
    });
}

// Initialization
window.addEventListener('load', () => {
    console.log('[PAGE] Load complete');
    initScanner();
});

window.addEventListener('beforeunload', () => {
    if (scanner) scanner.stop();
});
