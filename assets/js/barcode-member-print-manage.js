const container = document.getElementById('barcodeContainer');
const selectedCountEl = document.getElementById('selectedCount');

/**
 * Update the display of member barcodes based on selected checkboxes
 */
function updateDisplay() {
    const selected = Array.from(document.querySelectorAll('.member-checkbox:checked')).map(cb => ({
        id: cb.value,
        nisn: cb.dataset.nisn,
        name: cb.dataset.name
    }));

    selectedCountEl.textContent = selected.length;

    if (selected.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">Pilih anggota di atas untuk menampilkan barcode</p>';
        return;
    }

    container.innerHTML = selected.map(member => `
        <div class="barcode-card">
            <h3>${escapeHtml(member.name)}</h3>
            <div class="barcode-image">
                 <svg class="barcode-render"
                     jsbarcode-format="CODE128"
                     jsbarcode-value="${escapeHtml(member.nisn)}"
                     jsbarcode-displayValue="true"
                     jsbarcode-fontSize="14"
                     jsbarcode-width="2"
                     jsbarcode-height="50"
                     jsbarcode-margin="5">
                </svg>
            </div>
            <div class="barcode-info">
                <strong>NISN</strong>
                ${escapeHtml(member.nisn)}
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
 * Select all member checkboxes
 */
function selectAll() {
    document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = true);
    updateDisplay();
}

/**
 * Deselect all member checkboxes
 */
function deselectAll() {
    document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = false);
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
