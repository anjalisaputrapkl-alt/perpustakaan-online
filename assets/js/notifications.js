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
