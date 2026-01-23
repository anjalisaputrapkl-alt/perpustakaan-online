// Mobile menu toggle
const navToggle = document.getElementById('navToggle');
const navSidebar = document.querySelector('.nav-sidebar');

if (navToggle) {
    navToggle.addEventListener('click', function () {
        navSidebar.classList.toggle('active');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.nav-sidebar') && !event.target.closest('.nav-toggle')) {
            navSidebar.classList.remove('active');
        }
    });
}

// FAQ Toggle
const faqItems = document.querySelectorAll('.faq-item');
faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    question.addEventListener('click', function () {
        // Close other open items
        faqItems.forEach(otherItem => {
            if (otherItem !== item) {
                otherItem.classList.remove('active');
            }
        });
        // Toggle current item
        item.classList.toggle('active');
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });
});
