let scanner = null;
let scanMode = 'book'; // Default mode Book
let currentMember = null;
let scannedBooks = []; // Array to store scanned books
let lastScannedTime = 0;
const SCAN_DELAY = 1500;
let toastTimeout = null;

// The defaultBorrowDuration should be set from a global variable initialized in the PHP file.
// Check if it exists, otherwise default to 7.
const defaultBorrowDuration = typeof window.appConfig !== 'undefined' && window.appConfig.defaultBorrowDuration
    ? window.appConfig.defaultBorrowDuration
    : 7;

function initScanner() {
    // Calculate best aspect ratio for camera
    const aspectRatio = window.innerWidth / window.innerHeight;

    scanner = new Html5Qrcode("reader");

    const config = {
        fps: 10,
        qrbox: { width: 280, height: 130 },
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
        }
    };

    scanner.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanError
    ).then(() => {
        showToast('Siap memindai', 'info');
    }).catch(err => {
        showToast('Gagal mengakses kamera', 'error');
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
        /^(?:NISN|nisn|ID|id)[:\-=\s]?(.+)$/,
        /^(?:ISBN|isbn)[:\-=\s]?(.+)$/,
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

    showLoading(true);

    try {
        const response = await fetch('./api/process-barcode.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode: parsedBarcode })
        });

        const data = await response.json();

        if (!data.success) {
            playSound('error');
            showToast('Data tidak ditemukan', 'error');
            showLoading(false);
            return;
        }

        // Handle member scan
        if (scanMode === 'member') {
            if (data.data.type !== 'member') {
                // Intelligent auto-switch
                if (data.data.type === 'book') {
                    playSound('success');
                    showToast('Buku terdeteksi. Mode: Buku', 'info');
                    switchMode('book');
                    scanMode = 'book'; // Force update local var
                    processBarcode(barcode); // Retry with new mode
                    return;
                }
                playSound('error');
                showToast('Bukan kartu anggota!', 'error');
                showLoading(false);
                return;
            }
            currentMember = data.data;
            updateMemberUI();
            playSound('success');

            if (scannedBooks.length > 0) {
                showToast(`Anggota: ${currentMember.name}`, 'success');
            } else {
                showToast(`Hai ${currentMember.name.split(' ')[0]}. Scan buku sekarang.`, 'success');
                switchMode('book');
            }

            // CHECK BORROW LIMIT
            if (currentMember.current_borrow_count >= currentMember.max_pinjam) {
                playSound('error');
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Peminjaman Tercapai!',
                    html: `Anggota <b>${currentMember.name}</b> sudah meminjam <b>${currentMember.current_borrow_count}</b> buku.<br>Batas maksimal adalah <b>${currentMember.max_pinjam}</b> buku.`,
                    confirmButtonColor: '#991b1b'
                });
            }

            // VALIDATE EXISTING CART (Fix for Restricted Books)
            if (currentMember.role === 'student') {
                const validBooks = [];
                let removedCount = 0;

                const newScannedBooks = [];
                scannedBooks.forEach(b => {
                    if (b.access_level === 'teacher_only') {
                        removedCount++;
                    } else {
                        newScannedBooks.push(b);
                    }
                });

                if (removedCount > 0) {
                    scannedBooks = newScannedBooks;
                    updateScannedList();
                    showToast(`${removedCount} buku dihapus (Khusus Guru)`, 'error');
                    playSound('error');
                }
            }
        }
        // Handle book scan
        else if (scanMode === 'book') {
            if (data.data.type !== 'book') {
                // Intelligent auto-switch
                if (data.data.type === 'member') {
                    playSound('success');
                    showToast('Kartu anggota terdeteksi', 'info');
                    switchMode('member');
                    scanMode = 'member';
                    processBarcode(barcode);
                    return;
                }
                playSound('error');
                showToast('Bukan kode buku!', 'error');
                showLoading(false);
                return;
            }

            if (scannedBooks.some(b => b.book_id === data.data.id)) {
                playSound('error');
                showToast('Buku sudah ada', 'error');
            } else {
                console.log('[DEBUG] Book data:', JSON.stringify(data.data));

                // CHECK BORROW LIMIT (Member's active count + currently scanned in session)
                if (currentMember) {
                    const totalPotential = currentMember.current_borrow_count + scannedBooks.length;
                    if (totalPotential >= currentMember.max_pinjam) {
                        playSound('error');
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menambah Buku',
                            html: `Limit peminjaman anggota tercapai (<b>${currentMember.max_pinjam}</b> buku).`,
                            confirmButtonColor: '#991b1b'
                        });
                        showLoading(false);
                        return;
                    }
                }

                // CHECK ACCESS LEVEL
                if (currentMember && currentMember.role === 'student' && data.data.access_level === 'teacher_only') {
                    playSound('error');
                    showToast('Buku Khusus GURU!', 'error');
                    showLoading(false); // Fix infinite loading
                    return;
                }

                // CHECK IF BOOK IS ALREADY BORROWED
                if (data.data.is_borrowed) {
                    playSound('error');
                    const borrowerMsg = data.data.borrower_name
                        ? `Sedang dipinjam oleh <b>${data.data.borrower_name}</b>.`
                        : 'Buku ini sedang dipinjam oleh anggota lain.';
                    showLoading(false); // hide overlay FIRST so Swal is visible
                    Swal.fire({
                        icon: 'error',
                        title: 'Buku Tidak Tersedia',
                        html: `${borrowerMsg}<br>Buku harus dikembalikan terlebih dahulu.`,
                        confirmButtonColor: '#991b1b'
                    });
                    return;
                }

                scannedBooks.push({
                    book_id: data.data.id,
                    book_title: data.data.name,
                    cover_image: data.data.cover_image,
                    access_level: data.data.access_level // Store access_level
                });
                updateScannedList();
                playSound('success');
                showToast('Buku ditambahkan', 'success');

            }
        }

    } catch (error) {
        console.error(error);
        playSound('error');
        showToast('Error koneksi', 'error');
    }

    showLoading(false);
}

async function submitScannedBooks() {
    if (scannedBooks.length === 0) return;

    if (!currentMember) {
        playSound('error');
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
                due_date: (() => {
                    const d = new Date();
                    d.setDate(d.getDate() + defaultBorrowDuration);
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const dd = String(d.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd} 23:59:59`;
                })(),
                borrows: scannedBooks.map(book => ({
                    member_id: currentMember.id,
                    book_id: book.book_id
                }))
            })
        });

        const data = await response.json();

        if (data.inserted > 0) {
            playSound('success');
            if (data.errors && data.errors.length > 0) {
                // Partial success: some books were accepted, some rejected
                Swal.fire({
                    icon: 'warning',
                    title: `${data.inserted} Buku Berhasil Dipinjam`,
                    html: `<div style="text-align:left;font-size:13px;">`
                        + data.errors.map(e => `⚠️ ${e}`).join('<br>')
                        + `</div>`,
                    confirmButtonColor: '#d97706'
                });
            } else {
                showToast('Peminjaman Berhasil!', 'success');
            }
            // Reset state for next transaction
            scannedBooks = [];
            currentMember = null;
            updateMemberUI();
            updateScannedList();
        } else {
            // All books were rejected (inserted = 0)
            playSound('error');
            const errorList = (data.errors && data.errors.length > 0)
                ? data.errors
                : [data.message || 'Peminjaman gagal'];
            Swal.fire({
                icon: 'error',
                title: 'Peminjaman Gagal',
                html: `<div style="text-align:left;font-size:13px;">`
                    + errorList.map(e => `❌ ${e}`).join('<br>')
                    + `</div>`,
                confirmButtonColor: '#991b1b'
            });
        }

    } catch (error) {
        playSound('error');
        showToast('Error koneksi', 'error');
    }

    showLoading(false);
}

function playSound(type) {
    const audio = document.getElementById(type === 'success' ? 'soundSuccess' : 'soundError');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(e => console.log('Audio play failed', e));
    }
}

function switchMode(mode) {
    scanMode = mode;
    document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById(mode === 'book' ? 'btnModeBook' : 'btnModeMember');
    if (btn) btn.classList.add('active');
}

function updateMemberUI() {
    const badge = document.getElementById('memberBadge');
    const nameEl = document.getElementById('badgeName');

    if (currentMember) {
        if (nameEl) nameEl.textContent = currentMember.name;
        if (badge) badge.classList.add('active');
    } else {
        if (badge) badge.classList.remove('active');
    }
}

function updateScannedList() {
    const container = document.getElementById('scannedListMini');
    const actionBar = document.getElementById('actionBar');
    const backBtn = document.getElementById('backBtnContainer');

    if (!container) return;

    if (scannedBooks.length === 0) {
        container.innerHTML = '<div class="empty-placeholder">Belum ada buku discan</div>';
        if (actionBar) actionBar.style.display = 'none';
        if (backBtn) backBtn.style.display = 'block';
        return;
    }

    if (actionBar) actionBar.style.display = 'grid';
    if (backBtn) backBtn.style.display = 'block';

    const countEl = document.getElementById('btnCount');
    if (countEl) countEl.textContent = scannedBooks.length;

    container.innerHTML = scannedBooks.map((book, index) => {
        let coverHtml = '';
        if (book.cover_image) {
            coverHtml = '<img src="../img/covers/' + escapeHtml(book.cover_image) + '" class="item-cover">';
        } else {
            coverHtml = '<div class="item-cover" style="display:flex;align-items:center;justify-content:center;font-size:10px;color:#888;">NoImg</div>';
        }

        return '<div class="scanned-item">' +
            coverHtml +
            '<div class="item-info">' +
            '<div class="item-title">' + escapeHtml(book.book_title) + '</div>' +
            '<div class="item-meta">Tap hapus untuk membatalkan</div>' +
            '</div>' +
            '<button class="item-remove" onclick="removeBook(' + index + ')">' +
            '<iconify-icon icon="mdi:close"></iconify-icon>' +
            '</button>' +
            '</div>';
    }).join('');

    container.scrollTop = container.scrollHeight;
}

function removeBook(index) {
    scannedBooks.splice(index, 1);
    updateScannedList();
}

function clearScannedBooks() {
    if (confirm('Hapus semua?')) {
        scannedBooks = [];
        updateScannedList();
    }
}

function showToast(msg, type) {
    const toast = document.getElementById('toastMessage');
    if (!toast) return;
    toast.textContent = msg;
    toast.className = 'toast show ' + type;

    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function showLoading(show) {
    const el = document.getElementById('loadingOverlay');
    if (!el) return;
    if (show) {
        el.style.display = 'flex';
        // Trigger reflow
        el.offsetHeight;
        el.style.opacity = '1';
    } else {
        el.style.opacity = '0';
        setTimeout(() => {
            if (el.style.opacity === '0') el.style.display = 'none';
        }, 300);
    }
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
