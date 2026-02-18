// Maintenance Stats Modal Manager
const maintenanceStatsModal = {
    init() {
        console.log('maintenanceStatsModal.init() called');

        const overlay = document.getElementById('statsModal');
        console.log('Stats modal overlay found:', !!overlay);

        if (overlay) {
            // Setup overlay click to close
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    console.log('Overlay clicked - closing modal');
                    this.closeModal();
                }
            });

            // Setup close button
            const closeBtn = overlay.querySelector('.modal-close');
            console.log('Close button found:', !!closeBtn);

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    console.log('Close button clicked');
                    this.closeModal();
                });
            }
        }

        // Setup KPI card click listeners
        this.setupCardListeners();

        // Initialize Search
        this.initSearch();
    },

    initSearch() {
        const searchInput = document.getElementById('searchStatsModal');
        const clearBtn = document.getElementById('clearStatsSearch');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase().trim();
                this.filterTable(query);

                if (clearBtn) {
                    clearBtn.style.display = query.length > 0 ? 'flex' : 'none';
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                this.filterTable('');
                clearBtn.style.display = 'none';
                searchInput.focus();
            });
        }

        // Shortcut Ctrl+K
        document.addEventListener('keydown', (e) => {
            const overlay = document.getElementById('statsModal');
            if (overlay && overlay.classList.contains('active')) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    if (searchInput) searchInput.focus();
                }
            }
        });
    },

    filterTable(query) {
        const rows = document.querySelectorAll('#statsModal .modal-table tbody tr');
        let hasResults = false;

        // Clear existing empty state
        const oldEmpty = document.querySelector('#statsModal .modal-body .modal-empty-search');
        if (oldEmpty) oldEmpty.remove();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isMatch = text.includes(query);
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) hasResults = true;
        });

        // Show empty state if no results
        if (!hasResults && query.length > 0) {
            const body = document.querySelector('#statsModal .modal-body');
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'modal-empty modal-empty-search';
            emptyDiv.style.padding = '40px 20px';
            emptyDiv.innerHTML = `
                <iconify-icon icon="mdi:magnify-close" style="font-size: 48px; opacity: 0.2; color: var(--accent); margin-bottom: 12px;"></iconify-icon>
                <div style="font-weight: 600; color: var(--text);">Data tidak ditemukan</div>
                <div style="font-size: 13px; color: var(--muted); margin-top: 4px;">Tidak ada hasil untuk "${query}"</div>
            `;
            body.appendChild(emptyDiv);
        }
    },

    setupCardListeners() {
        const cards = document.querySelectorAll('.kpi-card[data-stat-type]');
        console.log('KPI cards found:', cards.length);

        cards.forEach((card, index) => {
            const type = card.dataset.statType;
            console.log(`Card ${index + 1}: type="${type}"`);

            card.addEventListener('click', () => {
                console.log('Card clicked:', type);
                this.openModal(type);
            });
        });
    },

    openModal(type) {
        const overlay = document.getElementById('statsModal');
        const container = overlay.querySelector('.modal-container');

        if (!overlay || !container) return;

        // Reset content
        const body = overlay.querySelector('.modal-body');
        body.innerHTML = '<div class="modal-loading">Memuat data...</div>';

        // Show overlay
        overlay.classList.add('active');

        // Reset Search Input
        const searchInput = document.getElementById('searchStatsModal');
        const clearBtn = document.getElementById('clearStatsSearch');
        if (searchInput) {
            searchInput.value = '';
            if (clearBtn) clearBtn.style.display = 'none';
        }

        // Set title based on type
        const titles = {
            'reports': 'Semua Laporan Kerusakan',
            'fines': 'Semua Denda (Berdasarkan Jumlah)',
            'pending': 'Denda Tertunda'
        };

        overlay.querySelector('.modal-header h2').textContent = titles[type] || 'Detail Data';

        // Fetch data based on type
        this.fetchAndDisplayData(type);
    },

    closeModal() {
        const overlay = document.getElementById('statsModal');
        if (overlay) {
            overlay.classList.remove('active');
        }
    },

    async fetchAndDisplayData(type) {
        const endpoints = {
            'reports': '/perpustakaan-online/public/api/get-maintenance-reports.php',
            'fines': '/perpustakaan-online/public/api/get-maintenance-fines.php',
            'pending': '/perpustakaan-online/public/api/get-maintenance-pending.php'
        };

        try {
            const url = endpoints[type] || endpoints.reports;
            console.log('Fetching from:', url);

            const response = await fetch(url, {
                credentials: 'include',
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Response:', result);

            if (result.success) {
                this.displayData(type, result.data);
            } else {
                this.displayError(result.message || 'Terjadi kesalahan saat memuat data');
            }
        } catch (error) {
            console.error('Error:', error);
            this.displayError('Gagal memuat data. Silakan coba lagi. Error: ' + error.message);
        }
    },

    displayData(type, data) {
        const body = document.querySelector('#statsModal .modal-body');

        if (!data || data.length === 0) {
            body.innerHTML = '<div class="modal-empty">Tidak ada data untuk ditampilkan</div>';
            return;
        }

        let html = '<table class="modal-table">';

        // Create table based on type
        if (type === 'reports') {
            html += `
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th class="col-hide-mobile">Buku</th>
                        <th>Tipe Kerusakan</th>
                        <th>Denda</th>
                        <th>Status</th>
                        <th class="col-hide-mobile">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <strong>${item.member_name}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${item.nisn}</div>
                        </td>
                        <td class="col-hide-mobile">${item.book_title}</td>
                        <td>
                            <span style="font-size: 12px; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 4px;">
                                ${item.damage_type}
                            </span>
                        </td>
                        <td><strong style="color: #dc2626;">${item.fine_formatted}</strong></td>
                        <td>
                            <span class="status-badge ${item.status_class === 'paid' ? 'active' : 'inactive'}">
                                ${item.status}
                            </span>
                        </td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.created_at}</td>
                    </tr>
                `;
            });
        } else if (type === 'fines') {
            html += `
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tipe Kerusakan</th>
                        <th>Denda</th>
                        <th>Status</th>
                        <th class="col-hide-mobile">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td><strong>${item.member_name}</strong></td>
                        <td>${item.book_title}</td>
                        <td>
                            <span style="font-size: 12px; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 4px;">
                                ${item.damage_type}
                            </span>
                        </td>
                        <td><strong style="color: #dc2626;">${item.fine_formatted}</strong></td>
                        <td>
                            <span class="status-badge ${item.status_class === 'paid' ? 'active' : 'inactive'}">
                                ${item.status}
                            </span>
                        </td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.created_at}</td>
                    </tr>
                `;
            });
        } else if (type === 'pending') {
            html += `
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th class="col-hide-mobile">Buku</th>
                        <th>Tipe Kerusakan</th>
                        <th>Denda</th>
                        <th class="col-hide-mobile">Dipinjam</th>
                        <th class="col-hide-mobile">Dilaporkan</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <strong>${item.member_name}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${item.nisn}</div>
                        </td>
                        <td class="col-hide-mobile">${item.book_title}</td>
                        <td>
                            <span style="font-size: 12px; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 4px;">
                                ${item.damage_type}
                            </span>
                        </td>
                        <td><strong style="color: #dc2626;">${item.fine_formatted}</strong></td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.borrowed_at}</td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.created_at}</td>
                    </tr>
                `;
            });
        }

        html += '</tbody></table>';
        body.innerHTML = html;
    },

    displayError(message) {
        const body = document.querySelector('#statsModal .modal-body');
        body.innerHTML = `<div class="modal-empty" style="color: var(--danger);">${message}</div>`;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    maintenanceStatsModal.init();
});
