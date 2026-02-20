function showLibraryCard(data) {
    currentMemberData = data;
    document.getElementById('modal-name').textContent = data.name;
    document.getElementById('modal-nisn').textContent = 'ID: ' + data.nisn;

    const photoEl = document.getElementById('modal-photo');
    if (data.foto) {
        photoEl.src = data.foto;
    } else {
        photoEl.src = '../assets/images/default-avatar.svg';
    }

    try {
        // EXACT options from student-barcodes.php
        JsBarcode("#card-barcode", data.nisn, {
            format: "CODE128",
            displayValue: true,
            fontSize: 14,
            width: 2.5,
            height: 50,
            margin: 5
        });
    } catch (e) {
        console.error("Barcode error:", e);
    }

    document.getElementById('libraryCardModal').classList.add('active');
}

function printLibraryCard() {
    if (!currentMemberData) return;
    renderPrintWindow([currentMemberData]);
}

function printAllCards() {
    if (confirm("Cetak kartu untuk " + allMembersData.length + " anggota?")) {
        renderPrintWindow(allMembersData);
    }
}

function renderPrintWindow(members) {
    const printWindow = window.open('', '_blank', 'width=900,height=600');
    const schoolName = schoolData.name || 'PERPUSTAKAAN DIGITAL';
    const schoolLogo = schoolData.logo || '';

    // Build card HTML with new design
    const cardsHtml = members.map(m => {
        const photoSrc = m.foto ? m.foto : '../assets/images/default-avatar.svg';
        const barcodeValue = m.nisn || m.id;

        // Generate barcode off-screen for each card
        const tempSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        try {
            JsBarcode(tempSvg, barcodeValue, {
                format: "CODE128",
                displayValue: true,
                width: 2,
                height: 40,
                fontSize: 14,
                margin: 0
            });
        } catch (e) { console.error(e); }

        return '<div class="library-card">' +
            '<!-- Decor -->' +
            '<div class="decor-circle-1"></div>' +
            '<div class="decor-circle-2"></div>' +

            '<div class="card-header">' +
            '<div class="school-logo">' +
            (schoolLogo ? '<img src="' + schoolLogo + '">' : '<iconify-icon icon="mdi:school"></iconify-icon>') +
            '</div>' +
            '<div class="school-info">' +
            '<h2>' + schoolName.toUpperCase() + '</h2>' +
            '</div>' +
            '</div>' +
            '<div class="card-body">' +
            '<div class="profile-row">' +
            '<img src="' + photoSrc + '" class="avatar">' +
            '<div class="data">' +
            '<div class="name">' + m.name + '</div>' +
            '<div class="label">Nomor Anggota</div>' +
            '<div class="value">ID: ' + barcodeValue + '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="barcode-area">' +
            tempSvg.outerHTML +
            '</div>' +
            '</div>';
    }).join('');

    printWindow.document.write('<html>' +
        '<head>' +
        '<title>Cetak Kartu Perpustakaan</title>' +
        '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">' +
        '<script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>' +
        '<style>' +
        '@page { margin: 10mm; size: A4; }' +
        'body { font-family: "Inter", sans-serif; background: #fff; margin: 0; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
        '.print-grid { ' +
        'display: grid; ' +
        'grid-template-columns: ' + (members.length === 1 ? '1fr' : 'repeat(2, 1fr)') + '; ' +
        'gap: 10px; ' +
        'justify-items: center;' +
        'align-content: start;' +
        '}' +
        '.library-card {' +
        'width: 85.6mm;' +
        'height: 54mm; /* International Standard ID card size */' +
        'border: 1px solid #ddd;' +
        'border-radius: 8mm;' +
        'overflow: hidden;' +
        'display: flex;' +
        'flex-direction: column;' +
        'background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;' +
        'color: white !important;' +
        'padding: 5mm;' +
        'position: relative;' +
        'box-shadow: none;' +
        'page-break-inside: avoid;' +
        '}' +

        '/* Decor for print */' +
        '.decor-circle-1 {' +
        'position: absolute; top: -15mm; right: -15mm; width: 60mm; height: 60mm;' +
        'background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);' +
        'border-radius: 50%; pointer-events: none;' +
        '}' +
        '.decor-circle-2 {' +
        'position: absolute; bottom: -10mm; left: -10mm; width: 40mm; height: 40mm;' +
        'background: rgba(255,255,255,0.05);' +
        'border-radius: 50%; pointer-events: none;' +
        '}' +

        '.card-header {' +
        'display: flex;' +
        'align-items: center;' +
        'gap: 4mm;' +
        'padding-bottom: 3mm;' +
        'border-bottom: 0.5px solid rgba(255,255,255,0.2);' +
        'position: relative; z-index: 2;' +
        'margin-bottom: 3mm;' +
        '}' +
        '.school-logo {' +
        'width: 12mm; height: 12mm;' +
        'background: white; border-radius: 3mm;' +
        'display: flex; align-items: center; justify-content: center;' +
        'padding: 0;' +
        '}' +
        '.school-logo img { width: 8.5mm; height: 8.5mm; object-fit: contain; }' +
        '.school-logo iconify-icon { font-size: 8mm; color: #1e3a8a; }' +
        '.school-info h2 { font-size: 10pt; margin: 0; font-weight: 800; text-transform: uppercase; color: white; }' +

        '.card-body { flex: 1; display: flex; flex-direction: column; position: relative; z-index: 2; }' +
        '.profile-row { display: flex; gap: 4mm; align-items: center; }' +
        '.avatar { ' +
        'width: 22mm; height: 26mm; ' +
        'border-radius: 3mm; object-fit: cover; ' +
        'border: 1mm solid rgba(255,255,255,0.3); ' +
        'background: rgba(255,255,255,0.1);' +
        '}' +
        '.data { flex: 1; display: flex; flex-direction: column; justify-content: center; }' +
        '.name { font-size: 14pt; font-weight: 800; color: white; margin-bottom: 1mm; line-height: 1.1; }' +
        '.label { font-size: 8pt; color: rgba(255,255,255,0.9); margin-bottom: 0; }' +
        '.value { font-size: 9pt; font-weight: 500; color: rgba(255,255,255,0.9); }' +

        '.barcode-area { ' +
        'margin-top: auto; ' +
        'background: white; ' +
        'border-radius: 3mm; ' +
        'padding: 2mm 4mm; ' +
        'text-align: center; ' +
        'position: relative; z-index: 2;' +
        'display: flex; justify-content: center; align-items: center;' +
        'height: 10mm;' +
        '}' +
        '.barcode-area svg { width: 100%; height: 100%; }' +
        '</style>' +
        '</head>' +
        '<body>' +
        '<div class="print-grid">' +
        cardsHtml +
        '</div>' +
        "<script>" +
        "window.onload = function() {" +
        "setTimeout(() => {" +
        "window.print();" +
        "window.close();" +
        "}, 800);" + // Slight increase for iconify to load
        "};" +
        "</script>" +
        '</body>' +
        '</html>');
    printWindow.document.close();
}

function closeLibraryCardModal() {
    document.getElementById('libraryCardModal').classList.remove('active');
    currentMemberData = null;
}

// Close on outside click
window.onclick = function (event) {
    const modal = document.getElementById('libraryCardModal');
    if (event.target == modal) {
        closeLibraryCardModal();
    }
}

function updateMemberLabels() {
    const roleSelect = document.getElementById('role-select');
    const idLabel = document.getElementById('id-label');
    const idInput = document.getElementById('id-input');
    const maxPinjamInput = document.querySelector('input[name="max_pinjam"]');

    if (!roleSelect || !schoolData) return;

    const role = roleSelect.value;
    let defaultLimit = 3;

    if (role === 'teacher') {
        idLabel.textContent = 'NUPTK / ID Guru';
        idInput.placeholder = 'Nomor Unik Pendidik dan Tenaga Kependidikan';
        defaultLimit = schoolData.max_books_teacher || 10;
    } else if (role === 'employee') {
        idLabel.textContent = 'NIP / ID Karyawan';
        idInput.placeholder = 'Nomor Induk Pegawai';
        defaultLimit = schoolData.max_books_employee || 5;
    } else {
        idLabel.textContent = 'NISN Anggota';
        idInput.placeholder = 'Nomor Induk Anggota';
        defaultLimit = schoolData.max_books_student || 3;
    }

    if (maxPinjamInput) {
        maxPinjamInput.placeholder = 'Default: ' + defaultLimit;

        // Auto-update value ONLY if it's a NEW member addition (not edit mode)
        const isEdit = window.location.search.includes('action=edit');
        if (!isEdit) {
            maxPinjamInput.value = defaultLimit;
        }
    }
}

// Call on load
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('role-select')) {
        updateMemberLabels();
    }
});

// Simplified form state handling
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('member-form');
    if (form) {
        // Only clear password field for security
        const passwordField = form.querySelector('input[name="password"]');
        if (passwordField) {
            passwordField.value = '';
        }
    }
});

// Search functionality
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('memberSearch');
    const tableBody = document.getElementById('membersTableBody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const rows = tableBody.getElementsByClassName('member-row');

            Array.from(rows).forEach(row => {
                const name = row.querySelector('.member-name').textContent.toLowerCase();
                const nisn = row.querySelector('td:first-child span').textContent.toLowerCase();
                const email = row.querySelector('.member-email').textContent.toLowerCase();

                if (name.includes(query) || nisn.includes(query) || email.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// ============================================
// STATS MODAL
// ============================================

const statModalMeta = {
    semua: { label: 'Semua Anggota', icon: 'mdi:account-group', color: '#3b82f6', bg: 'rgba(59,130,246,0.12)' },
    siswa: { label: 'Siswa / Pelajar', icon: 'mdi:account-school', color: '#10b981', bg: 'rgba(16,185,129,0.12)' },
    staf: { label: 'Guru & Karyawan', icon: 'mdi:account-tie', color: '#f59e0b', bg: 'rgba(245,158,11,0.12)' },
    aktif: { label: 'Sedang Meminjam', icon: 'mdi:book-open-variant', color: '#ef4444', bg: 'rgba(239,68,68,0.12)' },
};

let currentStatKey = 'semua';
let currentStatData = [];

function showMembersStatModal(key) {
    currentStatKey = key;
    currentStatData = (typeof membersStatData !== 'undefined' && membersStatData[key]) ? membersStatData[key] : [];
    const meta = statModalMeta[key] || statModalMeta.semua;

    // Update header
    document.getElementById('statModalTitle').textContent = meta.label;
    document.getElementById('statModalCount').textContent = currentStatData.length + ' anggota';
    const iconWrap = document.getElementById('statModalIconWrap');
    iconWrap.style.background = meta.bg;
    const icon = document.getElementById('statModalIcon');
    icon.setAttribute('icon', meta.icon);
    icon.style.color = meta.color;

    // Clear search
    const searchEl = document.getElementById('statModalSearch');
    if (searchEl) searchEl.value = '';

    // Render list
    renderStatModalList(currentStatData, meta);

    // Show modal
    const modal = document.getElementById('membersStatModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function closeMembersStatModal() {
    const modal = document.getElementById('membersStatModal');
    modal.classList.remove('active');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function filterStatModal(query) {
    const meta = statModalMeta[currentStatKey] || statModalMeta.semua;
    const q = query.toLowerCase();
    const filtered = currentStatData.filter(m =>
        (m.name || '').toLowerCase().includes(q) ||
        (m.nisn || '').toLowerCase().includes(q) ||
        (m.email || '').toLowerCase().includes(q)
    );
    renderStatModalList(filtered, meta);
    document.getElementById('statModalCount').textContent =
        filtered.length + ' dari ' + currentStatData.length + ' anggota';
}

function renderStatModalList(data, meta) {
    const body = document.getElementById('statModalBody');
    if (!data || data.length === 0) {
        body.innerHTML = `
            <div style="padding:48px 24px; text-align:center; color:#94a3b8;">
                <iconify-icon icon="mdi:account-off-outline" style="font-size:48px; display:block; margin-bottom:12px;"></iconify-icon>
                <p style="font-size:14px; margin:0;">Tidak ada anggota ditemukan</p>
            </div>`;
        return;
    }

    const colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#d946ef', '#f43f5e'];
    const roleLabel = { student: 'Siswa', teacher: 'Guru', employee: 'Karyawan' };
    const roleBadgeColor = { student: '#3b82f6', teacher: '#f59e0b', employee: '#8b5cf6' };

    const rows = data.map(m => {
        const initial = (m.name || '?')[0].toUpperCase();
        const bg = colors[(m.name || '').length % colors.length];
        const role = m.role || 'student';
        const roleLbl = roleLabel[role] || role;
        const roleColor = roleBadgeColor[role] || '#64748b';
        const active = parseInt(m.active_borrows) || 0;
        const max = parseInt(m.max_pinjam) || 3;

        return `<div class="stat-modal-row" onclick="location.href='members.php?action=edit&id=${m.id}'" title="Klik untuk edit">
            <div style="width:38px; height:38px; border-radius:50%; background:${bg}; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:15px; flex-shrink:0;">${initial}</div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:600; font-size:14px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escHtml(m.name || '-')}</div>
                <div style="font-size:12px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escHtml(m.nisn || '')} • ${escHtml(m.email || '-')}</div>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0;">
                <span style="background:${roleColor}1a; color:${roleColor}; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px;">${roleLbl}</span>
                ${active > 0 ? `<span style="font-size:11px; color:#ef4444; font-weight:500;">${active}/${max} pinjam</span>` : ''}
            </div>
        </div>`;
    }).join('');

    body.innerHTML = rows;
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Close stat modal when clicking backdrop
document.addEventListener('click', function (e) {
    const modal = document.getElementById('membersStatModal');
    if (modal && e.target === modal) closeMembersStatModal();
});

// ESC key support for stat modal
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('membersStatModal');
        if (modal && modal.classList.contains('active')) closeMembersStatModal();
    }
});

