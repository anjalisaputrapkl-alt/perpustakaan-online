/**
 * UTILITY: Image Preview
 */
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('imagePreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; box-shadow: 0 8px 15px rgba(0,0,0,0.1);">`;
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = `
            <div style="text-align: center;">
                <iconify-icon icon="mdi:camera-outline" style="font-size: 32px; color: var(--muted); opacity: 0.5;"></iconify-icon>
                <div style="font-size: 11px; color: var(--muted); margin-top: 4px; font-weight: 600;">PREVIEW</div>
            </div>`;
    }
}

/**
 * UTILITY: FAQ Accordion
 */
function toggleFaq(element) {
    // Close other FAQs
    const allFaqs = document.querySelectorAll('.faq-item');
    allFaqs.forEach(item => {
        if (item !== element) {
            item.classList.remove('active');
        }
    });
    // Toggle current
    element.classList.toggle('active');
}

/**
 * FEATURE 1: DETAIL MODAL (Mata Icon)
 */
function openDetailModal(index) {
    if (!window.booksData || !window.booksData[index]) {
        alert('Data buku tidak ditemukan!');
        return;
    }

    const book = window.booksData[index];

    // Helper to set text
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) {
            // Force string and handle empty/null
            let content = (val !== null && val !== undefined && val !== '') ? String(val) : '-';
            el.textContent = content;
            console.log(`Set #${id} to "${content}"`);
        } else {
            console.error(`Element #${id} not found in Modal!`);
        }
    };

    console.log('Populating modal with:', book);

    set('detailTitle', book.title);
    set('detailAuthor', book.author);
    set('detailISBN', book.isbn);
    set('detailCategory', book.category);
    set('detailLocation', `Rak ${book.shelf || '-'} / Baris ${book.row_number || '-'} / Kolom ${book.lokasi_rak || '-'}`);

    set('detailCopies', `${book.copies} Salinan`);
    set('detailMaxBorrow', book.max_borrow_days ? `${book.max_borrow_days} Hari` : 'Default Sekolah');

    // Image Handling
    const imgContainer = document.getElementById('detailCover');
    if (imgContainer) {
        console.log('Cover image:', book.cover_image);
        if (book.cover_image && book.cover_image !== '') {
            imgContainer.src = '../img/covers/' + book.cover_image;
            imgContainer.style.display = 'block';
        } else {
            // Use a placeholder if no image
            imgContainer.src = 'https://via.placeholder.com/150x200?text=No+Cover';
            imgContainer.style.display = 'block';
        }
    }

    const modal = document.getElementById('detailModal');
    if (modal) modal.style.display = 'block';
}

function closeDetail() {
    const modal = document.getElementById('detailModal');
    if (modal) modal.style.display = 'none';
}

/**
 * FEATURE 2: STATS MODAL (Statistic Cards)
 */
function showStatDetail(type) {
    const books = window.booksData || [];
    const modal = document.getElementById('statModal');
    const titleEl = document.getElementById('statModalTitle');
    const bodyEl = document.getElementById('statModalBody');

    if (!modal) {
        console.error('Modal element #statModal not found in DOM');
        return;
    }

    let content = '';

    if (type === 'books') {
        titleEl.textContent = 'Daftar Semua Buku';
        content = `<div class="modal-stat-list">`;
        // Get 10 newest
        const recent = books.slice(0, 10);
        if (recent.length === 0) {
            content += `<div class="empty-state">Belum ada buku.</div>`;
        } else {
            recent.forEach(b => {
                content += `
                    <div class="modal-stat-item">
                        <span class="stat-item-label">${b.title}</span>
                        <span class="stat-item-val">${b.category || '-'}</span>
                    </div>
                `;
            });
        }
        content += `</div>`;


    } else if (type === 'categories') {
        titleEl.textContent = 'Statistik Kategori';
        const counts = {};
        books.forEach(b => {
            const cat = b.category || 'Lainnya';
            counts[cat] = (counts[cat] || 0) + 1;
        });
        content = `<div class="modal-stat-list">`;
        for (const [key, val] of Object.entries(counts)) {
            content += `
                <div class="modal-stat-item">
                    <span class="stat-item-label">${key}</span>
                    <span class="stat-item-val">${val} Judul</span>
                </div>
            `;
        }
        content += `</div>`;
    }

    bodyEl.innerHTML = content;
    modal.style.display = 'block';
}

function closeStatModal() {
    document.getElementById('statModal').style.display = 'none';
}

/**
 * FEATURE 3: REAL-TIME SEARCH FOR BOOKS
 */
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchBooksList');
    const clearBtn = document.getElementById('clearBooksSearch');
    const grid = document.querySelector('.books-grid');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.book-card-vertical');
            let hasResults = false;

            // Clear any existing no-results message
            const oldNoResults = document.querySelector('.no-results-message');
            if (oldNoResults) oldNoResults.remove();

            cards.forEach(card => {
                const title = card.querySelector('.book-title').textContent.toLowerCase();
                const author = card.querySelector('.book-author').textContent.toLowerCase();
                const category = card.querySelector('.book-category').textContent.toLowerCase();
                const shelf = card.querySelector('.book-card-footer').textContent.toLowerCase();

                const isMatch = title.includes(query) ||
                    author.includes(query) ||
                    category.includes(query) ||
                    shelf.includes(query);

                if (isMatch) {
                    card.style.display = 'flex';
                    card.classList.remove('search-fade-out');
                    card.classList.add('search-fade-in');
                    hasResults = true;
                } else {
                    card.classList.add('search-fade-out');
                    setTimeout(() => {
                        if (card.classList.contains('search-fade-out')) {
                            card.style.display = 'none';
                        }
                    }, 300);
                }
            });

            // Show/Hide Clear Button
            if (clearBtn) clearBtn.style.display = query.length > 0 ? 'flex' : 'none';

            // No results handling
            if (!hasResults && query.length > 0) {
                const noResults = document.createElement('div');
                noResults.className = 'no-results-message';
                noResults.innerHTML = `
                    <iconify-icon icon="mdi:book-search-outline" style="font-size: 48px; margin-bottom: 16px; display: block; margin-left: auto; margin-right: auto; opacity: 0.2; color: var(--accent);"></iconify-icon>
                    <div style="font-weight: 700; font-size: 18px; color: var(--text);">Buku tidak ditemukan</div>
                    <p style="color: var(--muted); margin-top: 8px;">Tidak ada buku yang sesuai dengan kata kunci "${query}"</p>
                `;
                grid.appendChild(noResults);
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }

        // --- Shortcut Key (Ctrl+K or Cmd+K) ---
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
});

// Global Close Click Outside
window.onclick = function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
