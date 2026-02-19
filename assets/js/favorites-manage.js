// ============================================
// DATA MANAGEMENT
// ============================================

let allFavorites = window.allFavorites || [];
let filteredFavorites = [...allFavorites];
let currentFilters = {
    search: '',
    category: '',
    sort: 'original'
};

// Extract unique categories
function getUniqueCategories() {
    const categories = new Set();
    allFavorites.forEach(fav => {
        if (fav.buku_kategori) {
            categories.add(fav.buku_kategori);
        }
    });
    return Array.from(categories).sort();
}

// Initialize category filter options
function initializeCategoryFilter() {
    const select = document.getElementById('categoryFilter');
    if (!select) return;
    const categories = getUniqueCategories();

    // Clear current except first
    while (select.options.length > 1) {
        select.remove(1);
    }

    categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat;
        option.textContent = cat;
        select.appendChild(option);
    });
}

// ============================================
// SEARCH FUNCTIONALITY
// ============================================

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            currentFilters.search = e.target.value.toLowerCase();

            // Show/hide clear button
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) {
                clearBtn.style.display = currentFilters.search ? 'flex' : 'none';
            }

            applyFilters();
        });
    }
}

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        currentFilters.search = '';
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.style.display = 'none';
        applyFilters();
    }
}

// ============================================
// FILTER FUNCTIONALITY
// ============================================

function applyFilters() {
    const catSelect = document.getElementById('categoryFilter');
    if (catSelect) currentFilters.category = catSelect.value;

    // Start with all favorites
    filteredFavorites = [...allFavorites];

    // Apply search filter
    if (currentFilters.search) {
        filteredFavorites = filteredFavorites.filter(fav => {
            const title = (fav.judul || '').toLowerCase();
            const category = (fav.buku_kategori || '').toLowerCase();
            return title.includes(currentFilters.search) ||
                category.includes(currentFilters.search);
        });
    }

    // Apply category filter
    if (currentFilters.category) {
        filteredFavorites = filteredFavorites.filter(fav =>
            fav.buku_kategori === currentFilters.category
        );
    }

    // Apply sorting
    applySortingInternal();

    // Update UI
    updateFavoritesDisplay();
    updateFilterStats();
}

// ============================================
// SORTING FUNCTIONALITY
// ============================================

function applySorting() {
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) currentFilters.sort = sortSelect.value;
    applyFilters(); // Re-apply everything including sort
}

function applySortingInternal() {
    const sortType = currentFilters.sort;

    if (sortType === 'a-z') {
        filteredFavorites.sort((a, b) =>
            (a.judul || '').localeCompare(b.judul || '', 'id-ID')
        );
    } else if (sortType === 'z-a') {
        filteredFavorites.sort((a, b) =>
            (b.judul || '').localeCompare(a.judul || '', 'id-ID')
        );
    } else if (sortType === 'newest') {
        filteredFavorites.sort((a, b) => (b.id_buku || 0) - (a.id_buku || 0));
    }
    // else 'original' - already handled by spreading allFavorites at start of applyFilters
}

// ============================================
// CLEAR ALL FILTERS
// ============================================

function clearAllFilters() {
    const sInput = document.getElementById('searchInput');
    if (sInput) sInput.value = '';

    const cFilter = document.getElementById('categoryFilter');
    if (cFilter) cFilter.value = '';

    const sSelect = document.getElementById('sortSelect');
    if (sSelect) sSelect.value = 'original';

    const csBtn = document.getElementById('clearSearchBtn');
    if (csBtn) csBtn.style.display = 'none';

    const cfBtn = document.getElementById('clearFiltersBtn');
    if (cfBtn) cfBtn.style.display = 'none';

    currentFilters = {
        search: '',
        category: '',
        sort: 'original'
    };

    filteredFavorites = [...allFavorites];
    updateFavoritesDisplay();
    updateFilterStats();
}

// ============================================
// UI UPDATE
// ============================================

function updateFavoritesDisplay() {
    const favoritesList = document.getElementById('favoritesList');
    if (!favoritesList) return;

    // Clear existing items
    favoritesList.innerHTML = '';

    if (filteredFavorites.length === 0) {
        // Show empty state for filters
        const emptyDiv = document.createElement('div');
        emptyDiv.className = 'empty-filtered-state';
        emptyDiv.innerHTML = `
            <div class="empty-filtered-state-icon">
                <iconify-icon icon="mdi:magnify-off"></iconify-icon>
            </div>
            <h3>Tidak ada hasil</h3>
            <p>Coba ubah filter atau pencarian Anda</p>
            <button class="btn-reset" onclick="clearAllFilters()">Reset Filter</button>
        `;
        favoritesList.appendChild(emptyDiv);
        favoritesList.style.gridColumn = '1 / -1';
    } else {
        // Render books
        filteredFavorites.forEach((fav, index) => {
            const card = createBookCard(fav);
            card.classList.add('fade-in');
            card.style.animationDelay = `${index * 50}ms`;
            favoritesList.appendChild(card);
        });
        favoritesList.style.gridColumn = 'auto';
    }
}

function updateFilterStats() {
    const activeCount = filteredFavorites.length;
    const totalCount = allFavorites.length;

    // Update counter badge
    const countBadge = document.getElementById('favoritesCountBadge');
    if (countBadge) {
        countBadge.textContent = `${activeCount} Buku`;
    }

    // Update stats text
    const activeCountSpan = document.getElementById('activeCount');
    const totalCountSpan = document.getElementById('totalCount');
    if (activeCountSpan) activeCountSpan.textContent = activeCount;
    if (totalCountSpan) totalCountSpan.textContent = totalCount;

    // Show/hide clear filters button
    const hasActiveFilters = currentFilters.search ||
        currentFilters.category ||
        currentFilters.sort !== 'original';
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (clearBtn) {
        clearBtn.style.display = hasActiveFilters ? 'flex' : 'none';
    }
}

// ============================================
// CREATE BOOK CARD ELEMENT
// ============================================

function createBookCard(fav) {
    const card = document.createElement('div');
    card.className = 'book-card-vertical';
    card.setAttribute('data-favorite-id', fav.id_favorit);
    card.setAttribute('data-book-id', fav.id_buku);

    const avgRating = fav.avg_rating ? parseFloat(fav.avg_rating).toFixed(1) : '0';
    const totalReviews = parseInt(fav.total_reviews) || 0;

    card.innerHTML = `
        <div class="book-cover-container">
            ${fav.cover ?
            `<img src="../img/covers/${fav.cover}" alt="${fav.judul}" loading="lazy">` :
            `<div class="no-image-placeholder"><iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon></div>`
        }
            <button class="btn-love loved" onclick="toggleFavorite(event, ${fav.id_buku}, '${fav.judul.replace(/'/g, "\\'")}')">
                <iconify-icon icon="mdi:heart"></iconify-icon>
            </button>
        </div>
        <div class="book-card-body">
            <div class="book-category">${fav.buku_kategori || 'Umum'}</div>
            <div class="book-title" title="${fav.judul}">${fav.judul}</div>
            <div class="book-author">${fav.penulis || '-'}</div>
            
            <div class="book-card-footer">
                <div class="shelf-info">
                    <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon> 
                    <span style="font-weight: 700;">${avgRating}</span>
                    <span style="opacity: 0.6; margin-left: 2px;">(${totalReviews})</span>
                    ${fav.shelf ? `<span style="opacity: 0.6; font-size: 10px; margin-left: auto;" title="${fav.lokasi_rak || ''}">• Rak ${fav.shelf}${fav.row_number ? ' / ' + fav.row_number : ''}</span>` : ''}
                </div>
                
                <div class="action-buttons">
                    <button class="btn-icon-sm" onclick="viewDetail(${fav.id_buku})" title="Detail">
                        <iconify-icon icon="mdi:eye"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    `;

    return card;
}

// ============================================
// EXISTING FUNCTIONS (PRESERVED)
// ============================================

// Toggle favorite
async function toggleFavorite(e, bookId, bookTitle) {
    e.preventDefault();
    e.stopPropagation();

    const btn = e.currentTarget;
    const icon = btn.querySelector('iconify-icon');
    const isLoved = btn.classList.contains('loved');

    try {
        const formData = new FormData();
        formData.append('id_buku', bookId);

        const action = isLoved ? 'remove' : 'add';
        const response = await fetch(`/perpustakaan-online/public/api/favorites.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (isLoved) {
                btn.classList.remove('loved');
                icon.setAttribute('icon', 'mdi:heart-outline');
                // Remove from list and update display
                const card = btn.closest('.book-card-vertical');
                if (card) {
                    card.style.opacity = '0.5';
                    setTimeout(() => {
                        allFavorites = allFavorites.filter(f => f.id_buku !== bookId);
                        applyFilters();
                    }, 300);
                }
            } else {
                btn.classList.add('loved');
                icon.setAttribute('icon', 'mdi:heart');
            }
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Gagal mengubah favorite');
    }
}

let currentBookData = null;

// Get book detail and open modal
async function viewDetail(bookId) {
    try {
        const response = await fetch(`api/get-book.php?id=${bookId}`);
        const data = await response.json();

        if (data.success) {
            openBookModal(data.data);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Gagal memuat detail buku');
    }
}

// Open book modal
function openBookModal(bookData) {
    currentBookData = bookData;

    // Set cover image
    const coverImg = document.getElementById('modalBookCover');
    const coverIcon = document.getElementById('modalBookIcon');
    if (bookData.cover_image) {
        coverImg.src = '../img/covers/' + bookData.cover_image;
        coverImg.style.display = 'block';
        coverIcon.style.display = 'none';
    } else {
        coverImg.style.display = 'none';
        coverIcon.style.display = 'block';
    }

    // Set book details
    document.getElementById('modalBookTitle').textContent = bookData.title || '-';
    document.getElementById('modalBookAuthor').textContent = bookData.author || '-';
    document.getElementById('modalBookCategory').textContent = bookData.category || 'Umum';
    document.getElementById('modalBookISBN').textContent = bookData.isbn || '-';
    document.getElementById('modalBookShelf').textContent = (bookData.shelf || '-') + (bookData.row_number ? ' (Baris ' + bookData.row_number + ')' : '');

    // Set lokasi rak spesifik
    let lokasiRakEl = document.getElementById('modalBookLokasiRak');
    let lokasiDetailWrap = document.getElementById('modalLokasiDetail');
    if (bookData.lokasi_rak) {
        if (lokasiRakEl) lokasiRakEl.textContent = bookData.lokasi_rak;
        if (lokasiDetailWrap) lokasiDetailWrap.style.display = 'block';
    } else {
        if (lokasiDetailWrap) lokasiDetailWrap.style.display = 'none';
    }

    // Show modal
    document.getElementById('bookModal').classList.add('active');
}

function closeBookModal() {
    document.getElementById('bookModal').classList.remove('active');
    currentBookData = null;
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.id === 'bookModal') {
        closeBookModal();
    }
});

// Hamburger Toggle
function setupNavbar() {
    const navToggle = document.getElementById('navToggle');
    const navSidebar = document.querySelector('.nav-sidebar');
    if (navToggle && navSidebar) {
        navToggle.addEventListener('click', () => {
            navSidebar.classList.toggle('active');
        });
    }
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    setupNavbar();
    setupSearch();
    if (allFavorites.length > 0) {
        initializeCategoryFilter();
        updateFavoritesDisplay();
        updateFilterStats();
    }
});
