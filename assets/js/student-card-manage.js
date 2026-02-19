/**
 * Initialize the barcode on the ID card
 * @param {string} value - The value to encode in the barcode
 */
function initCardBarcode(value) {
    if (!value) return;
    try {
        if (typeof JsBarcode !== 'undefined') {
            JsBarcode("#barcode", value, {
                format: "CODE128",
                lineColor: "#000",
                width: 2,
                height: 50,
                displayValue: true,
                font: "monospace",
                fontSize: 14,
                marginTop: 10,
                marginBottom: 10
            });
        }
    } catch (e) {
        console.error("Barcode initialization failed", e);
        const barcodeEl = document.getElementById('barcode');
        if (barcodeEl) barcodeEl.style.display = 'none';

        // Fallback text
        const div = document.querySelector('.barcode-section');
        if (div) {
            div.innerHTML += '<div style="color:red;font-size:12px">Barcode Error: ' + e.message + '</div>';
        }
    }
}

/**
 * Handle card printing
 */
function printCard() {
    window.print();
}

// Global exposure if needed by inline scripts
window.initCardBarcode = initCardBarcode;
window.printCard = printCard;
