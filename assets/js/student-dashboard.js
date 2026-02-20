let currentBookData = null;
let favorites = new Set();

// Load favorites on page load
async function loadFavorites() {
    try {
        const response = await fetch('/perpustakaan-online/public/api/favorites.php?action=get_favorites');
        const data = await response.json();
        if (data.success && data.data) {
            data.data.forEach(fav => {
                favorites.add(fav.id_buku);
                const btn = document.querySelector(`[data-book-id="${fav.id_buku}"] .btn-love`);
                if (btn) {
                    btn.classList.add('loved');
                    const icon = btn.querySelector('iconify-icon');
                    if (icon) icon.setAttribute('icon', 'mdi:heart');
                }
            });
        }
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
}

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
                favorites.delete(bookId);
            } else {
                btn.classList.add('loved');
                icon.setAttribute('icon', 'mdi:heart');
                favorites.add(bookId);
            }
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Gagal mengubah favorite');
    }
}

// Navigation Sidebar Toggle
const navToggle = document.getElementById('navToggle');
const navSidebar = document.getElementById('navSidebar');

if (navToggle && navSidebar) {
    navToggle.addEventListener('click', () => {
        navSidebar.classList.toggle('active');
    });

    // Close sidebar when clicking on a link
    document.querySelectorAll('.nav-sidebar-menu a').forEach(link => {
        link.addEventListener('click', () => {
            navSidebar.classList.remove('active');
        });
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', (e) => {
        if (!navSidebar.contains(e.target) && !navToggle.contains(e.target)) {
            navSidebar.classList.remove('active');
        }
    });

    // Close sidebar on window resize if >= 768px
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            navSidebar.classList.remove('active');
        }
    });
}

// Load favorites when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadFavorites();
});

// Modal functions
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
    document.getElementById('modalBookCopies').textContent = bookData.copies || '0';
    document.getElementById('modalBookShelf').textContent = `Rak ${bookData.shelf || '-'} / Baris ${bookData.row_number || '-'} / Kolom ${bookData.lokasi_rak || '-'}`;

    // Set status
    const isAvailable = (bookData.copies || 1) > 0;
    const statusEl = document.getElementById('modalBookStatus');
    if (isAvailable) {
        statusEl.textContent = 'Tersedia';
        statusEl.className = 'modal-book-status available';
    } else {
        statusEl.textContent = 'Tidak Tersedia';
        statusEl.className = 'modal-book-status unavailable';
    }

    // Enable/disable borrow button
    const borrowBtn = document.getElementById('modalBorrowBtn');
    borrowBtn.disabled = !isAvailable;

    // Show modal
    document.getElementById('bookModal').classList.add('active');
}

function closeBookModal() {
    document.getElementById('bookModal').classList.remove('active');
    currentBookData = null;
}

function borrowFromModal() {
    if (currentBookData) {
        borrowBook(currentBookData.id, currentBookData.title);
        closeBookModal();
    }
}

// Close modal when clicking outside
document.getElementById('bookModal').addEventListener('click', (e) => {
    if (e.target.id === 'bookModal') {
        closeBookModal();
    }
});

function borrowBook(bookId, bookTitle) {
    if (!confirm('Apakah Anda ingin meminjam ' + bookTitle + '?')) {
        return;
    }

    fetch('api/borrow-book.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'book_id=' + bookId
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Buku berhasil dipinjam! Silakan ambil di perpustakaan.');
                location.reload();
            } else {
                alert(data.message || 'Gagal meminjam buku');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
}

// Request return function
function requestReturn(borrowId) {
    if (!confirm('Apakah Anda ingin mengajukan pengembalian buku ini?')) {
        return;
    }

    fetch('api/student-request-return.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Permintaan pengembalian telah dikirim ke admin!');
                location.reload();
            } else {
                alert(data.message || 'Gagal mengajukan pengembalian');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
}
