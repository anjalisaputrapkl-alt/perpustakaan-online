let sessionCount = 0;
let html5QrcodeScanner = null;
const barcodeInput = document.getElementById('barcodeInput');
const sessionHistory = document.getElementById('sessionHistory');
const sessionList = document.getElementById('sessionReturnsList');
const emptyState = document.getElementById('scanEmptyState');
const sessionCountEl = document.getElementById('sessionCount');
const sessionCountBadge = document.getElementById('sessionCountBadge');

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
            if (window.lastScan && now - window.lastScan < 2000) return;
            window.lastScan = now;

            const parsedBarcode = parseBarcode(decodedText);
            processReturn(parsedBarcode);
        },
        (error) => { }
    ).catch(err => console.error("Error starting scanner", err));

    // Focus input
    setTimeout(() => {
        if (barcodeInput) barcodeInput.focus();
    }, 500);
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
        const parsedBarcode = parseBarcode(val);
        processReturn(parsedBarcode);
        barcodeInput.value = '';
    }
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
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode: barcode })
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
    if (el) {
        el.textContent = msg;
        el.style.display = 'block';
        el.style.background = type === 'error' ? 'var(--danger-soft)' : (type === 'success' ? 'var(--success-soft)' : 'var(--info-soft)');
        el.style.color = type === 'error' ? 'var(--danger)' : (type === 'success' ? 'var(--success)' : 'var(--info)');

        if (type === 'success') {
            setTimeout(() => { el.style.display = 'none'; }, 3000);
        }
    }
}

function handleSuccess(data) {
    const successSound = document.getElementById('soundSuccess');
    if (successSound) successSound.play();

    sessionCount++;
    if (sessionCountEl) sessionCountEl.textContent = sessionCount;
    if (sessionCountBadge) sessionCountBadge.textContent = sessionCount;

    // Update Right Column visibility
    if (sessionCount === 1) {
        if (emptyState) emptyState.style.display = 'none';
        if (sessionHistory) sessionHistory.style.display = 'block';
    }

    // Create Table Row
    const row = document.createElement('tr');
    row.className = 'fade-in-row'; // Access animations
    row.style.animation = 'slideInRight 0.3s ease-out';

    // Fine badge logic
    let statusHtml = '';
    if (data.fine_amount > 0) {
        const warningSound = document.getElementById('soundWarning');
        if (warningSound) warningSound.play();
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
    <td>\${statusHtml}</td>
  `;

    // Prepend to top
    if (sessionList) sessionList.insertBefore(row, sessionList.firstChild);

    // Also add to the "Global" Recent History Table at the bottom
    const globalList = document.getElementById('recentReturnsList');
    if (globalList) {
        const globalRow = document.createElement('tr');
        globalRow.style.animation = 'fadeIn 0.5s ease';
        globalRow.innerHTML = '<td style="font-weight: 700;">' + data.book_title + '</td>' +
            '<td>' + data.member_name + '</td>' +
            '<td>' + new Date().toLocaleDateString('id-ID') + ' ' + new Date().toLocaleTimeString('id-ID') + '</td>' +
            '<td>' + (data.fine_amount > 0 ? '<span style="color: var(--danger); font-weight: 700;">Rp ' + data.fine_amount.toLocaleString('id-ID') + '</span>' : '<span style="color: var(--success);">Nihil</span>') + '</td>';
        globalList.insertBefore(globalRow, globalList.firstChild);
        if (globalList.children.length > 10) globalList.removeChild(globalList.lastChild);
    }
}

function handleError(msg) {
    const errorSound = document.getElementById('soundError');
    if (errorSound) errorSound.play();
    console.error(msg);
}
