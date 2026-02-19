let currentStudentData = null; // Can be a single object or an array
let searchResultsData = [];
let selectedStudentsMap = new Map();
let searchTimeout;

// Search functionality
document.getElementById('searchInput').addEventListener('input', function (e) {
    const query = e.target.value.trim();
    const emptyState = document.getElementById('emptyState');
    const searchResults = document.getElementById('searchResults');
    const searchResultsWrapper = document.getElementById('searchResultsWrapper');

    clearTimeout(searchTimeout);

    if (query.length < 2) {
        searchResultsWrapper.style.display = 'none';
        searchResults.classList.remove('active');
        emptyState.style.display = 'block';
        searchResultsData = [];
        return;
    }

    emptyState.style.display = 'none';
    searchResults.innerHTML = '<div class="loading"><iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Mencari...</div>';
    searchResultsWrapper.style.display = 'block';
    searchResults.classList.add('active');

    searchTimeout = setTimeout(() => {
        fetch(`api/search-students.php?q=${encodeURIComponent(query)}`)
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || `HTTP error! status: ${res.status}`);
                }
                return data;
            })
            .then(data => {
                if (data.success && data.students && data.students.length > 0) {
                    searchResultsData = data.students;
                    document.getElementById('selectAllStudents').checked = false;

                    searchResults.innerHTML = data.students.map(student => {
                        const isChecked = selectedStudentsMap.has(student.id);
                        const initial = student.name.charAt(0).toUpperCase();
                        const avatarContent = student.foto
                            ? `<img src="${escapeHtml(student.foto)}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`
                            : initial;
                        const avatarStyle = student.foto ? '' : `background: ${getGradient(initial)}`;

                        return `
                        <div class="result-item ${isChecked ? 'selected' : ''}" id="student-item-${student.id}" onclick="triggerCheckbox(${student.id})">
                            <div class="checkbox-container">
                                ${isChecked ? '<iconify-icon icon="mdi:check-bold"></iconify-icon>' : ''}
                            </div>
                            <div class="result-avatar" style="${avatarStyle}">${avatarContent}</div>
                            <div class="result-info">
                                <div class="result-title">${escapeHtml(student.name)}</div>
                                <div class="result-meta">${escapeHtml(student.status || 'Aktif')}</div>
                                <div class="result-extra">NISN: ${escapeHtml(student.nisn || '-')}</div>
                                <div class="result-actions">
                                    <button class="btn-generate" onclick="event.stopPropagation(); initGenerate(${student.id})">
                                        <iconify-icon icon="mdi:qrcode-view"></iconify-icon>
                                        View Barcode
                                    </button>
                                </div>
                            </div>
                            <input type="checkbox" class="student-checkbox" hidden value="${student.id}" 
                                   onchange="toggleSelect(${student.id}, this)" ${isChecked ? 'checked' : ''}>
                        </div>`;
                    }).join('');
                } else {
                    searchResults.innerHTML = '<div class="empty-state" style="padding:20px;"><p>Tidak ada hasil untuk "' + escapeHtml(query) + '"</p></div>';
                }
            })
            .catch(err => {
                console.error('Search error:', err);
                searchResults.innerHTML = `<div class="empty-state" style="padding:20px;"><p>Terjadi kesalahan saat mencari.</p></div>`;
            });
    }, 300);
});

function triggerCheckbox(id) {
    const item = document.getElementById(`student-item-${id}`);
    const cb = item.querySelector('.student-checkbox');
    cb.checked = !cb.checked;
    // Trigger onchange manually
    toggleSelect(id, cb);
}

function toggleSelect(id, cb) {
    const item = document.getElementById(`student-item-${id}`);
    const indicator = item ? item.querySelector('.checkbox-container') : null;

    if (cb.checked) {
        const student = searchResultsData.find(s => s.id === id);
        if (student) {
            selectedStudentsMap.set(id, student);
            if (item) {
                item.classList.add('selected');
                indicator.innerHTML = '<iconify-icon icon="mdi:check-bold"></iconify-icon>';
            }
        }
    } else {
        selectedStudentsMap.delete(id);
        if (item) {
            item.classList.remove('selected');
            indicator.innerHTML = '';
        }
    }
    updateBulkBar();
}

function toggleSelectAll(cb) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(item => {
        const studentId = parseInt(item.value);
        item.checked = cb.checked;
        toggleSelect(studentId, item);
    });
}

function clearSelection() {
    selectedStudentsMap.clear();
    document.querySelectorAll('.result-item.selected').forEach(item => {
        item.classList.remove('selected');
        item.querySelector('.checkbox-container').innerHTML = '';
        const cb = item.querySelector('.student-checkbox');
        if (cb) cb.checked = false;
    });
    document.getElementById('selectAllStudents').checked = false;
    updateBulkBar();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkActionBar');
    const count = selectedStudentsMap.size;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('btnSelectedCount').textContent = count;

    if (count > 0) {
        bar.classList.add('active');
    } else {
        bar.classList.remove('active');
    }
}

function initGenerate(id) {
    const student = searchResultsData.find(s => s.id === id);
    if (!student) return;
    currentStudentData = student;
    showModal(false);
}

function generateBulk() {
    if (selectedStudentsMap.size === 0) return;
    const students = Array.from(selectedStudentsMap.values());
    currentStudentData = students;
    showModal(true);
}

function fetchAllStudents() {
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Memproses...';

    fetch('api/search-students.php?all=1')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.students) {
                currentStudentData = data.students;
                showModal(true);
            } else {
                alert('Gagal mengambil data siswa');
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
}

function showModal(isBulk) {
    const modal = document.getElementById('barcodeModal');
    const previewBody = document.getElementById('modalPreviewBody');
    const bulkBody = document.getElementById('modalBulkBody');

    if (isBulk) {
        previewBody.style.display = 'none';
        bulkBody.style.display = 'grid';
        renderBulkPreview();
    } else {
        previewBody.style.display = 'block';
        bulkBody.style.display = 'none';
        renderSinglePreview();
    }

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('barcodeModal').classList.remove('active');
}

function renderSinglePreview() {
    const student = currentStudentData;
    document.getElementById('modal-name').textContent = student.name;
    document.getElementById('modal-nisn').textContent = `NISN: ${student.nisn || '-'}`;
    document.getElementById('modal-photo').src = student.foto || '../assets/images/default-avatar.svg';

    JsBarcode("#card-barcode", student.nisn || student.id.toString(), {
        format: "CODE128",
        width: 2,
        height: 60,
        displayValue: true
    });
}

function renderBulkPreview() {
    const container = document.getElementById('modalBulkBody');
    const students = Array.isArray(currentStudentData) ? currentStudentData : [currentStudentData];

    container.innerHTML = students.map(student => `
        <div class="barcode-card">
            <div style="font-weight:700; font-size:13px; margin-bottom:4px; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(student.name)}</div>
            <div style="font-size:11px; color:var(--muted); margin-bottom:8px; text-align:center;">NISN: ${escapeHtml(student.nisn || '-')}</div>
            <div style="display:flex; justify-content:center; align-items:center;">
                <svg class="bulk-barcode" data-value="${student.nisn || student.id}" style="display:block;"></svg>
            </div>
        </div>
    `).join('');

    document.querySelectorAll('.bulk-barcode').forEach(el => {
        JsBarcode(el, el.dataset.value, {
            format: "CODE128",
            width: 1.5,
            height: 40,
            displayValue: true,
            fontSize: 12
        });
    });
}

function printBarcode() {
    if (!currentStudentData) return;

    let students = Array.isArray(currentStudentData) ? currentStudentData : [currentStudentData];
    const printWindow = window.open('', '_blank', 'width=900,height=700');

    // Generate barcode SVG for each student
    const studentBarcodes = students.map(student => {
        const tempSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        JsBarcode(tempSvg, student.nisn || student.id.toString(), {
            format: "CODE128",
            displayValue: true,
            fontSize: 11,
            width: 1.8,
            height: 48,
            margin: 6
        });
        return {
            name: student.name,
            nisn: student.nisn || student.id,
            svg: tempSvg.outerHTML
        };
    });

    const html = studentBarcodes.map(bc => `
        <div class="barcode-item">
            <div class="item-name">${escapeHtml(bc.name)}</div>
            <div class="item-nisn">NISN: ${escapeHtml(bc.nisn)}</div>
            <div class="svg-wrap">${bc.svg}</div>
        </div>
    `).join('');

    printWindow.document.write(
        '<html>' +
        '<head>' +
        '<title>Cetak Barcode Anggota</title>' +
        '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">' +
        '<style>' +
        '@page { size: A4; margin: 12mm; }' +
        'body { margin: 0; padding: 0; font-family: "Inter", sans-serif; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
        '.print-title { text-align: center; font-size: 14pt; font-weight: 700; margin-bottom: 16px; color: #1e3a8a; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; }' +
        '.barcode-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; justify-items: center; }' +
        '.barcode-item { width: 100%; box-sizing: border-box; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 10px 10px; text-align: center; page-break-inside: avoid; background: #fff; }' +
        '.item-name { font-size: 10pt; font-weight: 700; color: #0f172a; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }' +
        '.item-nisn { font-size: 8pt; color: #64748b; margin-bottom: 8px; font-family: monospace; }' +
        '.svg-wrap { display: flex; justify-content: center; align-items: center; }' +
        '.svg-wrap svg { display: block; margin: 0 auto; max-width: 100%; height: auto; }' +
        '</style>' +
        '</head>' +
        '<body>' +
        '<div class="print-title">Label Barcode Anggota Perpustakaan</div>' +
        '<div class="barcode-grid">' +
        html +
        '</div>' +
        '<script>' +
        'window.onload = function() {' +
        'setTimeout(function() {' +
        'window.print();' +
        'window.close();' +
        '}, 500);' +
        '};' +
        '<\/script>' +
        '</body>' +
        '</html>'
    );
    printWindow.document.close();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getGradient(char) {
    const colors = [
        'linear-gradient(135deg, #6366f1, #a855f7)',
        'linear-gradient(135deg, #3b82f6, #06b6d4)',
        'linear-gradient(135deg, #10b981, #3b82f6)',
        'linear-gradient(135deg, #f59e0b, #ef4444)',
        'linear-gradient(135deg, #ec4899, #8b5cf6)',
    ];
    const index = char.charCodeAt(0) % colors.length;
    return colors[index];
}
