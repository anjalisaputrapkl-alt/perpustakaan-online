document.getElementById('ratingForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const rating = this.querySelector('input[name="rating"]:checked');
    if (!rating) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Rating',
            text: 'Silakan pilih bintang dulu bro!',
            background: '#112255',
            color: '#fff'
        });
        return;
    }

    const formData = new FormData(this);
    try {
        const res = await fetch(this.action, { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: json.message,
                background: 'var(--card)',
                color: 'var(--text)'
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: json.message,
                background: 'var(--card)',
                color: 'var(--text)'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Gagal kirim data',
            background: 'var(--card)',
            color: 'var(--text)'
        });
    }
});
