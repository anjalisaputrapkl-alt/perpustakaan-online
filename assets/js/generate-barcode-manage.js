let currentBookData = null; // Can be a single object or an array of results
let searchResultsData = [];
let selectedBooks = new Set();
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
        fetch(`api/barcode-api.php?action=search&q=${encodeURIComponent(query)}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(json => {
                if (json.success) {
                    const books = json.books;
                    searchResultsData = books;
                    document.getElementById('selectAllBooks').checked = false;

                    if (books && books.length > 0) {
                        searchResults.innerHTML = books.map(book => {
                            const isChecked = selectedBooks.has(book.id);
                            const coverSrc = book.cover ? ('../img/covers/' + escapeHtml(book.cover)) : '../assets/images/default-avatar.svg';
                            return `
                            <div class="result-item ${isChecked ? 'selected' : ''}" id="book-item-${book.id}" onclick="triggerCheckbox(${book.id})">
                                <div class="checkbox-container">
                                    ${isChecked ? '<iconify-icon icon="mdi:check-bold"></iconify-icon>' : ''}
                                </div>
                                <img src="${coverSrc}" class="result-thumb" onerror="this.src='../assets/images/default-avatar.svg'" alt="cover">
                                <div class="result-info">
                                    <div class="result-title">${escapeHtml(book.judul)}</div>
                                    <div class="result-meta">${escapeHtml(book.penulis || '-')}</div>
                                    <div class="result-extra">${escapeHtml(book.kode_buku || '-')}</div>
                                    <div class="result-actions">
                                        <button class="btn-generate" onclick="event.stopPropagation(); generateBarcode(${book.id})">
                                            <iconify-icon icon="mdi:qrcode-view" style="font-size:16px"></iconify-icon>
                                            View Barcode
                                        </button>
                                    </div>
                                </div>
                                <input type="checkbox" class="book-checkbox" hidden 
                                       onchange="toggleSelect(${book.id}, this)" ${isChecked ? 'checked' : ''}>
                            </div>`;
                        }).join('');
                    } else {
                        searchResults.innerHTML = '<div class="empty-state" style="padding:20px;"><p>Tidak ada hasil</p></div>';
                    }
                } else {
                    throw new Error(json.error || 'Invalid data');
                }
            })
            .catch(err => {
                console.error('Search error:', err);
                searchResults.innerHTML = `<div class="empty-state" style="padding:20px;"><p>Error: ${escapeHtml(err.message)}</p></div>`;
            });
    }, 300);
});

async function generateAll() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;

    try {
        btn.disabled = true;
        btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Mengambil Data...';

        const response = await fetch('api/barcode-api.php?action=get_all_ids');
        const json = await response.json();

        if (!json.success) throw new Error(json.error || 'Failed to fetch IDs');

        if (json.count === 0) {
            alert('Tidak ada buku untuk di-generate.');
            return;
        }

        if (confirm(`Generate barcode untuk ${json.count} buku? Halaman mungkin akan sedikit lambat.`)) {
            btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Generating...';

            const formData = new FormData();
            formData.append('action', 'generate_bulk');
            json.ids.forEach(id => formData.append('book_ids[]', id));

            const bulkResponse = await fetch('api/barcode-api.php', {
                method: 'POST',
                body: formData
            });
            const bulkJson = await bulkResponse.json();

            if (bulkJson.success) {
                displayModal(bulkJson);
            } else {
                throw new Error(bulkJson.error || 'Bulk generation failed');
            }
        }
    } catch (err) {
        console.error('Generate all error:', err);
        alert('Error: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

function generateBarcode(bookId) {
    const button = event.target;
    button.disabled = true;
    button.textContent = 'Generating...';

    const formData = new FormData();
    formData.append('action', 'generate');
    formData.append('book_id', bookId);

    fetch('api/barcode-api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON. Check server logs.');
            }
        })
        .then(data => {
            if (data.success) {
                currentBookData = data;
                displayModal(data);
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
            button.disabled = false;
            button.innerHTML = 'Generate';
        })
        .catch(err => {
            console.error('Generate error:', err);
            alert('Error: ' + err.message);
            button.disabled = false;
            button.innerHTML = 'Generate';
        });
}

function displayModal(data) {
    const modalBody = document.getElementById('modalPreviewBody');
    const bulkBody = document.getElementById('modalBulkBody');
    const modalTitle = document.querySelector('.modal-title');
    const downloadBtn = document.querySelector('.btn-download');

    // Handle bulk results
    if (data.results) {
        currentBookData = data.results;
        modalTitle.textContent = `Preview Barcode (${data.count} Buku)`;
        modalBody.style.display = 'none';
        bulkBody.style.display = 'grid';
        downloadBtn.style.display = 'none'; // Hide download for bulk

        bulkBody.innerHTML = data.results.map(res => `
            <div class="barcode-card">
                <div style="font-size: 13px; font-weight: 700; color: var(--title-color); margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px;">
                    ${escapeHtml(res.book.judul)}
                </div>
                <div style="font-size: 11px; color: var(--muted); margin-bottom: 10px;">
                    Kode: <strong>${escapeHtml(res.book.kode_buku)}</strong>
                </div>
                <div style="display: flex; justify-content: center; background: #f9fbff; padding: 10px; border-radius: 8px;">
                    <img class="print-target-barcode" src="data:image/png;base64,${res.barcode}" style="max-width: 100%; height: auto;">
                </div>
            </div>
        `).join('');
    }
    // Handle single result
    else {
        currentBookData = data;
        modalTitle.textContent = 'Preview Barcode';
        modalBody.style.display = 'block';
        bulkBody.style.display = 'none';
        downloadBtn.style.display = 'flex'; // Show download for single

        const book = data.book;
        document.getElementById('modalTitle').textContent = book.judul || '-';
        document.getElementById('modalCode').textContent = book.kode_buku || '-';
        document.getElementById('modalAuthor').textContent = book.penulis || '-';
        document.getElementById('barcodeImage').src = 'data:image/png;base64,' + data.barcode;
    }

    document.getElementById('barcodeModal').classList.add('active');
}

function toggleSelect(id, checkbox) {
    const item = document.getElementById(`book-item-${id}`);
    const checkContainer = item.querySelector('.checkbox-container');

    if (checkbox.checked) {
        selectedBooks.add(id);
        item.classList.add('selected');
        checkContainer.innerHTML = '<iconify-icon icon="mdi:check-bold"></iconify-icon>';
    } else {
        selectedBooks.delete(id);
        item.classList.remove('selected');
        checkContainer.innerHTML = '';
    }
    updateBulkBar();
}

function triggerCheckbox(id) {
    const checkbox = document.querySelector(`#book-item-${id} .book-checkbox`);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        toggleSelect(id, checkbox);
    }
}

function toggleSelectAll(checkbox) {
    searchResultsData.forEach(book => {
        const itemCheckbox = document.querySelector(`#book-item-${book.id} .book-checkbox`);
        if (itemCheckbox) {
            itemCheckbox.checked = checkbox.checked;
            toggleSelect(book.id, itemCheckbox);
        }
    });
}

function updateBulkBar() {
    const bar = document.getElementById('bulkActionBar');
    const count = selectedBooks.size;

    if (count > 0) {
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('btnSelectedCount').textContent = count;
        bar.style.display = 'flex';
        // Trigger reflow for animation
        setTimeout(() => bar.classList.add('active'), 10);
    } else {
        bar.classList.remove('active');
        setTimeout(() => {
            if (!bar.classList.contains('active')) bar.style.display = 'none';
        }, 300);
    }
}

function clearSelection() {
    selectedBooks.clear();
    document.querySelectorAll('.result-item.selected').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.book-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllBooks').checked = false;
    updateBulkBar();
}

function generateBulk() {
    const ids = Array.from(selectedBooks);
    if (ids.length === 0) return;

    const btn = document.querySelector('.btn-bulk-generate');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Processing...';

    const formData = new FormData();
    formData.append('action', 'generate_bulk');
    formData.append('book_ids', JSON.stringify(ids));

    fetch('api/barcode-api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayModal(data);
            } else {
                alert('Error: ' + data.error);
            }
            btn.disabled = false;
            btn.innerHTML = originalContent;
        })
        .catch(err => {
            console.error(err);
            alert('Connection error');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
}

function closeModal() {
    document.getElementById('barcodeModal').classList.remove('active');
    // reset scroll position of modal content
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) modalContent.scrollTop = 0;
}

function downloadBarcodes() {
    if (!currentBookData || Array.isArray(currentBookData)) return;

    const book = currentBookData.book;
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Set canvas size for barcode only
    canvas.width = 600;
    canvas.height = 400;

    // White background
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Title
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('BARCODE BUKU', canvas.width / 2, 40);

    // Book info
    ctx.font = '12px Arial';
    ctx.fillText(`${book.judul}`, canvas.width / 2, 80);
    ctx.fillText(`Kode: ${book.kode_buku}`, canvas.width / 2, 110);

    // Draw Barcode
    const barcodeImg = new Image();
    const barcodeImageElement = document.getElementById('barcodeImage');
    if (barcodeImageElement) {
        barcodeImg.src = barcodeImageElement.src;
        barcodeImg.onload = function () {
            ctx.drawImage(barcodeImg, 50, 150, 500, 200);

            // Download
            canvas.toBlob(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `barcode-${book.kode_buku}.png`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            });
        };
    }
}

function printBarcodes() {
    if (!currentBookData) return;

    let barcodes = [];
    if (Array.isArray(currentBookData)) {
        barcodes = currentBookData.map(res => ({
            base64: res.barcode,
            title: res.book.judul,
            code: res.book.kode_buku
        }));
    } else {
        barcodes = [{
            base64: currentBookData.barcode,
            title: currentBookData.book.judul,
            code: currentBookData.book.kode_buku
        }];
    }

    const printWindow = window.open('', '_blank', 'width=800,height=600');

    const barcodeHtml = barcodes.map(bc => `
        <div class="barcode-item">
            <div class="info">${escapeHtml(bc.title)} (${escapeHtml(bc.code)})</div>
            <img src="data:image/png;base64,${bc.base64}">
        </div>
    `).join('');

    printWindow.document.write(`
        <html>
        <head>
            <title>Cetak Barcode</title>
            <style>
                body { margin: 20px; font-family: sans-serif; }
                .barcode-item { 
                    margin-bottom: 30px; 
                    text-align: center; 
                    page-break-inside: avoid;
                    border: 1px dashed #eee;
                    padding: 10px;
                    display: inline-block;
                    width: 300px;
                }
                .info { 
                    font-size: 11px; 
                    font-weight: bold; 
                    margin-bottom: 5px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                img { max-width: 100%; height: auto; }
                @page { margin: 10mm; }
            </style>
        </head>
        <body>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
                ${barcodeHtml}
            </div>
            <script>
                window.onload = function() {
                    setTimeout(() => {
                        window.print();
                        window.close();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
const barcodeModal = document.getElementById('barcodeModal');
if (barcodeModal) {
    barcodeModal.addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
}
