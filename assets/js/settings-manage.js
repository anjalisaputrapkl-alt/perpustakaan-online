// Tab Switching Logic
document.querySelectorAll('.tab-link').forEach(link => {
    link.addEventListener('click', () => {
        const target = link.dataset.tab;

        // Update active tab link
        document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        // Show active tab content
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(target).classList.add('active');

        // Save active tab to localStorage
        localStorage.setItem('active_settings_tab', target);
    });
});

// Restore active tab on load
window.addEventListener('load', () => {
    const savedTab = localStorage.getItem('active_settings_tab');
    if (savedTab) {
        const tabEl = document.querySelector(`.tab-link[data-tab="${savedTab}"]`);
        if (tabEl) tabEl.click();
    }
});

// FAQ Toggle Logic
document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
        const item = q.parentElement;
        item.classList.toggle('active');
    });
});

// Photo Upload Preview
const photoInput = document.getElementById('school_photo');
if (photoInput) {
    photoInput.addEventListener('change', function (e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];

            // Simple preview
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewImg = document.getElementById('preview-img');
                const previewPlaceholder = document.getElementById('preview-placeholder');

                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                }
                if (previewPlaceholder) {
                    previewPlaceholder.style.display = 'none';
                }
            }
            reader.readAsDataURL(file);
        }
    });
}


function openAddSpecialThemeModal() {
    document.getElementById('addSpecialThemeModal').style.display = 'flex';
}

function closeAddSpecialThemeModal() {
    document.getElementById('addSpecialThemeModal').style.display = 'none';
}

// Copy to clipboard helper
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link berhasil disalin ke clipboard!');
    }).catch(err => {
        const temp = document.createElement('input');
        document.body.appendChild(temp);
        temp.value = text;
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('Link berhasil disalin ke clipboard!');
    });
}
