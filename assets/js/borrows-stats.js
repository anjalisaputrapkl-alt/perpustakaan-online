/**
 * Borrows Stats Controller
 * Handles clicking on stat cards and displaying the modal with detailed data
 */

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('statsModal');
    if (!modal) return;

    const modalBody = modal.querySelector('.modal-body');
    const modalTitle = modal.querySelector('.modal-header h2');
    const closeBtn = modal.querySelector('.modal-close');
    const statCards = document.querySelectorAll('.stat-card.clickable');

    // API Config
    const apiConfig = {
        'total': {
            title: 'Daftar Semua Peminjaman',
            endpoint: 'api/get-borrows-total.php'
        },
        'active': {
            title: 'Daftar Peminjaman Sedang Dipinjam',
            endpoint: 'api/get-borrows-active.php'
        },
        'overdue': {
            title: 'Daftar Peminjaman Terlambat',
            endpoint: 'api/get-borrows-overdue.php'
        },
        'pending_confirmation': {
            title: 'Form Menunggu Konfirmasi',
            endpoint: 'api/get-borrows-pending-confirmation.php'
        },
        'pending_return': {
            title: 'Pengembalian Menunggu Konfirmasi',
            endpoint: 'api/get-borrows-pending-return.php'
        }
    };

    // Card Click Event
    statCards.forEach(card => {
        card.addEventListener('click', () => {
            const type = card.getAttribute('data-stat-type');
            const config = apiConfig[type];
            if (config) {
                openModal(config);
            }
        });
    });

    // Close Modal Event
    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Modal Search Logic
    const modalSearch = document.getElementById('searchModal');
    const modalClear = document.getElementById('clearModalSearch');

    if (modalSearch) {
        modalSearch.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = modalBody.querySelectorAll('.modal-table tbody tr:not(.no-results-row)');
            let visibleCount = 0;

            if (modalClear) modalClear.style.display = query.length > 0 ? 'flex' : 'none';

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    row.classList.remove('search-fade-out');
                    row.classList.add('search-fade-in');
                    visibleCount++;
                } else {
                    row.classList.add('search-fade-out');
                    row.classList.remove('search-fade-in');
                    setTimeout(() => {
                        if (row.classList.contains('search-fade-out')) {
                            row.style.display = 'none';
                        }
                    }, 300);
                }
            });

            // Handle No Results in Modal
            let noResults = modalBody.querySelector('.no-results-message');
            if (visibleCount === 0 && query !== '') {
                if (!noResults) {
                    const tr = document.createElement('tr');
                    tr.className = 'no-results-row';
                    tr.innerHTML = `<td colspan="10" style="border:none;">
                        <div class="no-results-message">
                            <iconify-icon icon="mdi:magnify-close" style="font-size: 32px; margin-bottom: 8px;"></iconify-icon>
                            <p>Tidak ditemukan hasil untuk "${query}"</p>
                        </div>
                    </td>`;
                    modalBody.querySelector('.modal-table tbody').appendChild(tr);
                }
            } else if (noResults) {
                noResults.closest('tr').remove();
            }
        });

        if (modalClear) {
            modalClear.addEventListener('click', () => {
                modalSearch.value = '';
                modalSearch.dispatchEvent(new Event('input'));
                modalSearch.focus();
            });
        }
    }

    function openModal(config) {
        modalTitle.textContent = config.title;
        modalBody.innerHTML = '<div class="modal-loading">Memuat data...</div>';
        if (modalSearch) {
            modalSearch.value = '';
            if (modalClear) modalClear.style.display = 'none';
        }
        modal.style.display = 'flex';

        fetch(config.endpoint)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    renderData(res.data);
                } else {
                    modalBody.innerHTML = `<div style="text-align:center;color:red;padding:20px;">Error: ${res.message}</div>`;
                }
            })
            .catch(err => {
                modalBody.innerHTML = `<div style="text-align:center;color:red;padding:20px;">Error: ${err.message}</div>`;
            });
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function renderData(data) {
        if (!data || data.length === 0) {
            modalBody.innerHTML = `
                <div style="text-align:center;padding:60px 20px;color:var(--muted);">
                    <iconify-icon icon="mdi:database-off" style="font-size: 48px; opacity: 0.2; display: block; margin: 0 auto 16px;"></iconify-icon>
                    Tidak ada data untuk kategori ini.
                </div>
            `;
            return;
        }

        let html = `
            <div class="borrows-table-wrap" style="border:none;">
                <table class="modal-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 0;">Buku</th>
                            <th class="col-hide-mobile">Peminjam</th>
                            <th>Jatuh Tempo</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.forEach(item => {
            let statusClass = 'badge-active';
            let statusLabel = item.status;

            if (item.status === 'overdue') {
                statusClass = 'badge-inactive';
                statusLabel = 'Terlambat';
            } else if (item.status === 'returned') {
                statusClass = 'badge-active';
                statusLabel = 'Kembali';
            } else if (item.status === 'pending_confirmation') {
                statusClass = 'badge-inactive';
                statusLabel = 'Ditinjau';
            } else if (item.status === 'pending_return') {
                statusClass = 'badge-inactive';
                statusLabel = 'Proses';
            } else {
                statusLabel = 'Pinjam';
            }

            const dueDate = item.due_at ? new Date(item.due_at).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            }) : '-';

            html += `
                <tr>
                    <td style="padding-left: 0;">
                        <div style="font-weight:700; color:var(--text); line-height:1.2;">${item.title}</div>
                        <div style="font-size:11px; color:var(--muted); margin-top: 2px;">ISBN: ${item.isbn}</div>
                    </td>
                    <td class="col-hide-mobile">
                        <div style="font-weight:600; color:var(--text);">${item.member_name}</div>
                        <div style="font-size:11px; color:var(--muted); margin-top: 2px;">NISN: ${item.nisn}</div>
                    </td>
                    <td style="font-weight:600; color:var(--text);">${dueDate}</td>
                    <td style="text-align: center;"><span class="student-badge ${statusClass}">${statusLabel}</span></td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        modalBody.innerHTML = html;
    }
});
