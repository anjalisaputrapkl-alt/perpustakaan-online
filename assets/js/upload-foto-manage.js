document.addEventListener('DOMContentLoaded', () => {
    const uploadArea = document.getElementById('uploadArea');
    const fotoInput = document.getElementById('fotoInput');
    const fileNameSpan = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitBtn');

    if (uploadArea && fotoInput && fileNameSpan && submitBtn) {
        // Click to select file
        uploadArea.addEventListener('click', () => fotoInput.click());

        // File selection
        fotoInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files.length > 0) {
                fileNameSpan.textContent = '✓ ' + files[0].name + ' (' + (files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
                submitBtn.disabled = false;
            } else {
                fileNameSpan.textContent = '';
                submitBtn.disabled = true;
            }
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('active');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('active');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('active');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fotoInput.files = files;
                fileNameSpan.textContent = '✓ ' + files[0].name + ' (' + (files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
                submitBtn.disabled = false;
            }
        });
    }
});
