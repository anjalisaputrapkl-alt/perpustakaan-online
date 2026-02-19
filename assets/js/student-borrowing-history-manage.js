// Store modal data
const modalDataStore = window.modalDataStore || {};

/**
 * Show borrowing modal with table view
 * Opens overlay modal with filtered borrowing data
 */
function showBorrowingModal(title, dataKey) {
    const data = modalDataStore[dataKey] || [];

    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'borrowing-modal-overlay';
    modal.onclick = (e) => {
        if (e.target === modal) closeBorrowingModal(modal);
    };

    // Modal content
    const modalContent = document.createElement('div');
    modalContent.className = 'borrowing-modal-content';
    modalContent.innerHTML = `
        <div class="borrowing-modal-header">
            <h2>${title}</h2>
            <button onclick="closeBorrowingModal()" class="borrowing-modal-close">
                <iconify-icon icon="mdi:close" width="20" height="20"></iconify-icon>
            </button>
        </div>
        <div class="borrowing-modal-body">
            ${data && data.length > 0 ? renderBorrowingTableHtml(data) : '<div class="empty-state"><p>Data tidak ditemukan</p></div>'}
        </div>
    `;

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Trigger animation
    setTimeout(() => modal.classList.add('active'), 10);
}

/**
 * Close borrowing modal
 */
function closeBorrowingModal(modalElement) {
    const modal = modalElement || document.querySelector('.borrowing-modal-overlay.active');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

/**
 * Render borrowing data as HTML table
 */
function renderBorrowingTableHtml(data) {
    if (!data || data.length === 0) {
        return '<div class="empty-state"><p>Data tidak ditemukan</p></div>';
    }

    let html = '<table class="borrowing-modal-table"><thead><tr>';
    html += '<th>Judul Buku</th>';
    html += '<th>Tanggal Pinjam</th>';
    html += '<th>Tanggal Kembali</th>';
    html += '<th>Status</th>';
    html += '</tr></thead><tbody>';

    data.forEach(item => {
        let statusBadge = '';
        switch (item.status) {
            case 'borrowed':
                statusBadge = '<span class="badge badge-borrowed">Dipinjam</span>';
                break;
            case 'returned':
                statusBadge = '<span class="badge badge-returned">Dikembalikan</span>';
                break;
            case 'overdue':
                statusBadge = '<span class="badge badge-overdue">Telat</span>';
                break;
            default:
                statusBadge = '<span class="badge">' + item.status + '</span>';
        }

        html += `<tr>
            <td class="title-cell">${item.title || '-'}</td>
            <td>${formatDateModal(item.borrowed_at)}</td>
            <td>${item.returned_at ? formatDateModal(item.returned_at) : '-'}</td>
            <td>${statusBadge}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    return html;
}

/**
 * Format date untuk modal display
 */
function formatDateModal(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const d = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const y = date.getFullYear();
    return `${d}/${m}/${y}`;
}

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeBorrowingModal();
    }
});
