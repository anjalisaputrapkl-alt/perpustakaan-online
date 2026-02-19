const container = document.getElementById('barcodeContainer');
const selectedCountEl = document.getElementById('selectedCount');
const searchBox = document.getElementById('searchBox');

/**
 * Update the display of barcodes based on selected checkboxes
 */
function updateDisplay() {
    const selected = Array.from(document.querySelectorAll('.book-checkbox:checked')).map(cb => ({
        id: cb.value,
        isbn: cb.dataset.isbn,
        title: cb.dataset.title,
        author: cb.dataset.author
    }));

    selectedCountEl.textContent = selected.length;

    if (selected.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">Pilih buku di atas untuk menampilkan barcode</p>';
        return;
    }

    container.innerHTML = selected.map(book => `
        <div class="barcode-card">
            <h3>${escapeHtml(book.title)}</h3>
            <div class="barcode-image">
                <svg class="barcode-render"
                     jsbarcode-format="CODE128"
                     jsbarcode-value="${escapeHtml(book.isbn)}"
                     jsbarcode-displayValue="true"
                     jsbarcode-fontSize="14"
                     jsbarcode-width="2"
                     jsbarcode-height="50"
                     jsbarcode-margin="5">
                </svg>
            </div>
            <div class="barcode-info">
                <strong>ISBN</strong>
                ${escapeHtml(book.isbn || '-')}
                ${book.author ? '<div style="margin-top: 3px; font-size: 9px;">' + escapeHtml(book.author) + '</div>' : ''}
            </div>
        </div>
    `).join('');

    // Initialize Barcodes
    initBarcodes();
}

/**
 * Initialize barcodes on the page
 */
function initBarcodes() {
    try {
        if (typeof JsBarcode !== 'undefined') {
            JsBarcode(".barcode-render").init();
        }
    } catch (e) {
        console.error("Barcode rendering failed", e);
    }
}

/**
 * Filter the book list based on search query
 */
function filterBooks() {
    const query = searchBox.value.toLowerCase();
    const items = document.querySelectorAll('.checkbox-item');

    items.forEach(item => {
        const label = item.querySelector('label').textContent.toLowerCase();
        item.style.display = label.includes(query) ? 'flex' : 'none';
    });
}

/**
 * Select all visible book checkboxes
 */
function selectAll() {
    document.querySelectorAll('.checkbox-item').forEach(item => {
        if (item.style.display !== 'none') {
            const cb = item.querySelector('.book-checkbox');
            if (cb) cb.checked = true;
        }
    });
    updateDisplay();
}

/**
 * Deselect all book checkboxes
 */
function deselectAll() {
    document.querySelectorAll('.book-checkbox').forEach(cb => cb.checked = false);
    updateDisplay();
}

/**
 * Simple HTML escape function
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initial display update
document.addEventListener('DOMContentLoaded', () => {
    updateDisplay();
});
