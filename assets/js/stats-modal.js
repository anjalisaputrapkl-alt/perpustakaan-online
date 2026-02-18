// Modal Management System
const modalManager = {
    currentModal: null,

    init() {
        console.log('modalManager.init() called');

        const overlay = document.getElementById('statsModal');
        console.log('Modal overlay found:', !!overlay);

        if (overlay) {
            // Setup overlay click to close
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    console.log('Overlay clicked - closing modal');
                    this.closeModal();
                }
            });

            // Setup close button
            const closeBtn = document.querySelector('.modal-close');
            console.log('Close button found:', !!closeBtn);

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    console.log('Close button clicked');
                    this.closeModal();
                });
            }

            // --- Search Logic for Dashboard Modals ---
            const modalSearch = document.getElementById('searchModalTab');
            const modalClear = document.getElementById('clearModalTabSearch');
            const modalBody = document.querySelector('.modal-body');

            if (modalSearch) {
                modalSearch.addEventListener('input', function () {
                    const query = this.value.toLowerCase().trim();
                    const table = modalBody.querySelector('.modal-table');
                    if (!table) return;

                    const rows = table.querySelectorAll('tbody tr:not(.no-results-row)');
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

                    // Handle No Results
                    let noResults = modalBody.querySelector('.no-results-row');
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
                            table.querySelector('tbody').appendChild(tr);
                        }
                    } else if (noResults) {
                        noResults.remove();
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
        }

        // Setup card click listeners
        this.setupCardListeners();
    },

    setupCardListeners() {
        const stats = document.querySelectorAll('.stat');
        console.log('Stats cards found:', stats.length);

        stats.forEach((stat, index) => {
            const type = stat.dataset.statType;
            console.log(`Card ${index + 1}: type="${type}"`);

            stat.addEventListener('click', () => {
                console.log('Card clicked:', type);
                this.openModal(type);
            });
        });
    },

    openModal(type) {
        const overlay = document.getElementById('statsModal');
        const container = document.querySelector('.modal-container');

        if (!overlay || !container) return;

        // Reset content
        const body = document.querySelector('.modal-body');
        body.innerHTML = '<div class="modal-loading">Memuat data...</div>';

        // Show overlay
        overlay.classList.add('active');

        // Reset Search
        const modalSearch = document.getElementById('searchModalTab');
        const modalClear = document.getElementById('clearModalTabSearch');
        if (modalSearch) {
            modalSearch.value = '';
            if (modalClear) modalClear.style.display = 'none';
        }

        // Set title based on type
        const titles = {
            'books': 'Daftar Semua Buku',
            'members': 'Daftar Anggota',
            'borrowed': 'Buku yang Sedang Dipinjam',
            'overdue': 'Peminjaman Terlambat'
        };

        document.querySelector('.modal-header h2').textContent = titles[type] || 'Detail Data';

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
            'books': '/perpustakaan-online/public/api/get-stats-books.php',
            'members': '/perpustakaan-online/public/api/get-stats-members.php',
            'borrowed': '/perpustakaan-online/public/api/get-stats-borrowed.php',
            'overdue': '/perpustakaan-online/public/api/get-stats-overdue.php'
        };

        try {
            const url = endpoints[type] || endpoints.books;
            console.log('Fetching from:', url); // Debug log

            const response = await fetch(url, {
                credentials: 'include',
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Response:', result); // Debug log

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
        const body = document.querySelector('.modal-body');

        if (!data || data.length === 0) {
            body.innerHTML = '<div class="modal-empty">Tidak ada data untuk ditampilkan</div>';
            return;
        }

        let html = '<table class="modal-table">';

        // Create table based on type
        if (type === 'books') {
            html += `
                <thead>
                    <tr>
                        <th>Judul Buku</th>
                        <th class="col-hide-mobile">Penulis</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(book => {
                html += `
                    <tr>
                        <td><strong>${book.title}</strong></td>
                        <td class="col-hide-mobile">${book.author}</td>
                        <td>${book.category}</td>
                        <td>${book.available}/${book.total}</td>
                        <td>
                            <span class="status-badge ${book.available > 0 ? 'available' : 'unavailable'}">
                                ${book.status}
                            </span>
                        </td>
                    </tr>
                `;
            });
        } else if (type === 'members') {
            html += `
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="col-hide-mobile">NISN</th>
                        <th class="col-hide-mobile">Email</th>
                        <th>Status</th>
                        <th>Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(member => {
                html += `
                    <tr>
                        <td><strong>${member.name}</strong></td>
                        <td class="col-hide-mobile">${member.nisn}</td>
                        <td class="col-hide-mobile">${member.email}</td>
                        <td>
                            <span class="status-badge ${member.status === 'Aktif' ? 'active' : 'inactive'}">
                                ${member.status}
                            </span>
                        </td>
                        <td>${member.current_borrows}</td>
                    </tr>
                `;
            });
        } else if (type === 'borrowed') {
            html += `
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th class="col-hide-mobile">Peminjam</th>
                        <th class="col-hide-mobile">Tgl Peminjaman</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(borrow => {
                const isOverdue = borrow.days_remaining < 0;
                html += `
                    <tr>
                        <td>
                            <strong>${borrow.book_title}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${borrow.book_author}</div>
                        </td>
                        <td class="col-hide-mobile">${borrow.member_name}</td>
                        <td class="col-hide-mobile">${borrow.borrowed_date}</td>
                        <td>${borrow.due_date}</td>
                        <td>
                            <span class="status-badge ${isOverdue ? 'overdue' : 'borrowed'}">
                                ${borrow.status}
                            </span>
                        </td>
                    </tr>
                `;
            });
        } else if (type === 'overdue') {
            html += `
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th class="col-hide-mobile">Peminjam</th>
                        <th class="col-hide-mobile">Tgl Peminjaman</th>
                        <th>Jatuh Tempo</th>
                        <th>Terlambat</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <strong>${item.book_title}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${item.book_author}</div>
                        </td>
                        <td class="col-hide-mobile">${item.member_name}</td>
                        <td class="col-hide-mobile">${item.borrowed_date}</td>
                        <td>${item.due_date}</td>
                        <td>
                            <span class="status-badge overdue">
                                ${item.days_overdue} hari
                            </span>
                        </td>
                    </tr>
                `;
            });
        }

        html += '</tbody></table>';
        body.innerHTML = html;
    },

    displayError(message) {
        const body = document.querySelector('.modal-body');
        body.innerHTML = `<div class="modal-empty" style="color: var(--danger);">${message}</div>`;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    modalManager.init();
});
