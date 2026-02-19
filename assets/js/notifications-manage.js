document.addEventListener('DOMContentLoaded', async () => {
    // Seed modal data with current PHP-provided notifications as fallback
    // Note: The global allNotificationsForModal variable is expected to be initialized in the PHP file

    try {
        const resp = await fetch('./api/notifications-all.php');
        const json = await resp.json();
        if (json && json.success && Array.isArray(json.data)) {
            window.allNotificationsForModal = json.data;
            console.log('✓ Loaded ' + window.allNotificationsForModal.length + ' notifications from API');
        } else {
            console.warn('API response invalid:', json);
        }
    } catch (err) {
        console.error('Failed to load full notifications via API:', err);
    }
});

/**
 * Show notifications modal with table view
 * @param {string} title 
 * @param {string} typeFilter 
 */
function showNotificationModal(title, typeFilter) {
    let data = window.allNotificationsForModal || [];

    // Filter by type if specified (case-insensitive, trimmed)
    if (typeFilter && typeFilter !== 'all') {
        const f = typeFilter.toLowerCase().trim();
        data = data.filter(n => (n.jenis_notifikasi || '').toLowerCase().trim() === f);
    }

    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'notification-modal-overlay';
    modal.style.background = 'var(--overlay)';
    modal.style.backdropFilter = 'blur(4px)';
    modal.onclick = (e) => {
        if (e.target === modal) closeNotificationModal(modal);
    };

    // Modal content
    const modalContent = document.createElement('div');
    modalContent.className = 'notification-modal-content';
    modalContent.style.background = 'var(--surface)';
    modalContent.style.color = 'var(--text)';
    modalContent.style.boxShadow = 'var(--shadow-lg)';

    modalContent.innerHTML = `
        <div class="notification-modal-header" style="border-bottom: 1px solid var(--border);">
            <h2 style="color: var(--text);">${title}</h2>
            <button onclick="closeNotificationModal()" class="notification-modal-close" style="background: var(--bg); color: var(--text); border: 1px solid var(--border);">
                <iconify-icon icon="mdi:close" width="20" height="20"></iconify-icon>
            </button>
        </div>
        <div class="notification-modal-body">
            ${data && data.length > 0 ? renderNotificationTableHtml(data) : '<div class="empty-state" style="text-align: center; padding: 40px 20px;">\n<p style="color: var(--text-muted);">Data tidak ditemukan</p>\n</div>'}
        </div>
    `;

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Trigger animation
    setTimeout(() => modal.classList.add('active'), 10);
}

/**
 * Close notification modal
 * @param {HTMLElement} modalElement 
 */
function closeNotificationModal(modalElement) {
    const modal = modalElement || document.querySelector('.notification-modal-overlay.active');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

/**
 * Render notifications as HTML table
 * @param {Array} data 
 * @returns {string}
 */
function renderNotificationTableHtml(data) {
    if (!data || data.length === 0) {
        return '<div class="empty-state"><p>Data tidak ditemukan</p></div>';
    }

    let html = '<table class="notification-modal-table"><thead><tr>';
    html += '<th>Judul</th>';
    html += '<th>Pesan</th>';
    html += '<th>Tipe</th>';
    html += '<th>Tanggal</th>';
    html += '</tr></thead><tbody>';

    data.forEach(item => {
        let typeBadge = '';
        const normalized = (item.jenis_notifikasi || '').toLowerCase().trim();

        switch (normalized) {
            case 'keterlambatan':
                typeBadge = '<span class="notification-badge badge-delay">Keterlambatan</span>';
                break;
            case 'peringatan':
                typeBadge = '<span class="notification-badge badge-warning">Peringatan</span>';
                break;
            case 'borrow':
                typeBadge = '<span class="notification-badge badge-return">Pengembalian</span>';
                break;
            case 'info':
                typeBadge = '<span class="notification-badge badge-info">Informasi</span>';
                break;
            case 'new_book':
                typeBadge = '<span class="notification-badge badge-newbooks">Buku Baru</span>';
                break;
            default:
                typeBadge = '<span class="notification-badge">' + (item.jenis_notifikasi || '-') + '</span>';
        }

        const tanggal = new Date(item.tanggal);
        const formattedDate = tanggal.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });

        html += `<tr>
            <td class="title-cell">${item.judul || '-'}</td>
            <td class="message-cell">${item.pesan || '-'}</td>
            <td>${typeBadge}</td>
            <td>${formattedDate}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    return html;
}

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeNotificationModal();
    }
});
