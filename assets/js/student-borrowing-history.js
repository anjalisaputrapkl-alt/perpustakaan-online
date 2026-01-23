// Toggle sidebar on mobile
const navToggle = document.getElementById('navToggle');
const navSidebar = document.querySelector('.nav-sidebar');

if (navToggle && navSidebar) {
    navToggle.addEventListener('click', () => {
        navSidebar.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!navSidebar.contains(e.target) && !navToggle.contains(e.target)) {
            navSidebar.classList.remove('active');
        }
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
