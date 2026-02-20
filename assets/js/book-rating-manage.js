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

    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnHtml = submitBtn.innerHTML;

    const formData = new FormData(this);
    try {
        // Disable button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<iconify-icon icon="mdi:loading" class="animate-spin"></iconify-icon> Mengirim...';

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
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
    }
});
