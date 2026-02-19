let scanner = null;
let lastScannedTime = 0;
const SCAN_DELAY = 2500;
let toastTimeout = null;

function initScanner() {
    scanner = new Html5Qrcode("reader");
    scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 280, height: 130 } },
        onScanSuccess,
        (err) => { }
    ).catch(err => showToast('Gagal akses kamera', 'error'));
}

function onScanSuccess(text) {
    const now = Date.now();
    if (now - lastScannedTime < SCAN_DELAY) return;
    lastScannedTime = now;

    // Parse barcode to handle prefixes and hyphens (NISN-, ISBN-, etc)
    const parsedBarcode = parseBarcode(text.trim());
    processReturn(parsedBarcode);
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

async function processReturn(barcode) {
    document.getElementById('loadingOverlay').style.display = 'flex';
    try {
        const res = await fetch('api/process-return.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode: barcode })
        });
        const result = await res.json();

        if (result.success) {
            displayReturn(result.data);
            showToast('Sukses!', 'success');
            document.getElementById('soundSuccess').play();
        } else {
            showToast(result.message, 'error');
            document.getElementById('soundError').play();
        }
    } catch (e) {
        showToast('Error koneksi', 'error');
    }
    document.getElementById('loadingOverlay').style.display = 'none';
}

function displayReturn(data) {
    const card = document.getElementById('returnCard');
    const header = document.getElementById('cardHeader');
    const fineArea = document.getElementById('fineArea');

    card.style.display = 'block';
    card.className = data.fine_amount > 0 ? 'return-card late' : 'return-card';

    header.textContent = data.fine_amount > 0 ? 'KEMBALI TERLAMBAT' : 'BUKU KEMBALI';
    document.getElementById('bookTitle').textContent = data.book_title;
    document.getElementById('memberInfo').textContent = data.member_name + ' (' + data.member_nisn + ')';

    if (data.fine_amount > 0) {
        document.getElementById('soundWarning').play();
        fineArea.innerHTML = `
            <div class="fine-badge">
                <iconify-icon icon="mdi:alert-circle"></iconify-icon>
                Denda: Rp ${data.fine_amount.toLocaleString('id-ID')}
            </div>
        `;
    } else {
        fineArea.innerHTML = '';
    }

    // Stay on page: Auto-hide success card after 5 seconds to allow next scan
    setTimeout(() => {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.display = 'none';
            card.style.opacity = '1';
        }, 500);
    }, 5000);
}

function showToast(msg, type) {
    const toast = document.getElementById('toastMessage');
    toast.textContent = msg;
    toast.className = 'toast show ' + type;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toast.classList.remove('show'), 3000);
}

window.addEventListener('load', initScanner);
