function showLibraryCard() {
    try {
        // EXACT options from student-barcodes.php
        if (window.appConfig && window.appConfig.userNisn) {
            JsBarcode("#card-barcode", window.appConfig.userNisn, {
                format: "CODE128",
                displayValue: true,
                fontSize: 14,
                width: 2.5,
                height: 50,
                margin: 5
            });
        }
    } catch (e) {
        console.error("Barcode error:", e);
    }

    document.getElementById('libraryCardModal').classList.add('active');
}

function closeLibraryCardModal() {
    document.getElementById('libraryCardModal').classList.remove('active');
}

// Global click handler
window.onclick = function (event) {
    const modal = document.getElementById('libraryCardModal');
    if (event.target == modal) {
        closeLibraryCardModal();
    }
}

// File upload functionality
document.addEventListener('DOMContentLoaded', () => {
    const uploadSection = document.getElementById('uploadSection');
    const fotoInput = document.getElementById('fotoInput');
    const uploadForm = uploadSection ? uploadSection.querySelector('form') : null;

    if (uploadSection && fotoInput && uploadForm) {
        // Prevent default drag and drop behavior
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadSection.classList.add('drag-over');
        });

        uploadSection.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadSection.classList.remove('drag-over');
        });

        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadSection.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fotoInput.files = files;
                uploadForm.submit();
            }
        });

        // Handle file selection from file manager
        fotoInput.addEventListener('change', (e) => {
            if (fotoInput.files.length > 0) {
                uploadForm.submit();
            }
        });
    }

    // Toggle sidebar on hamburger menu click
    const navToggle = document.getElementById('navToggle');
    const navSidebar = document.querySelector('.nav-sidebar');

    if (navToggle && navSidebar) {
        navToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            navSidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside of it
        document.addEventListener('click', function (event) {
            if (navSidebar.classList.contains('active')) {
                if (!navSidebar.contains(event.target) && event.target !== navToggle && !navToggle.contains(event.target)) {
                    navSidebar.classList.remove('active');
                }
            }
        });
    }
});
