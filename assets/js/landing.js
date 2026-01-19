// ============================================
// HAMBURGER MENU - Mobile Navigation
// ============================================

// Global function to toggle navigation menu
window.toggleNav = function() {
  const nav = document.querySelector('.main-nav');
  const toggle = document.querySelector('.nav-toggle');
  if (!nav || !toggle) return;
  
  nav.classList.toggle('active');
  
  // Update aria-expanded for accessibility
  const isActive = nav.classList.contains('active');
  toggle.setAttribute('aria-expanded', isActive);
  toggle.setAttribute('aria-label', isActive ? 'Close menu' : 'Open menu');
};

// Initialize hamburger button immediately (before DOM ready)
if (document.readyState === 'loading') {
  // DOM not ready yet
  document.addEventListener('DOMContentLoaded', initHamburger);
} else {
  // DOM already ready
  initHamburger();
}

function initHamburger() {
  const hamburgerBtn = document.getElementById('hamburger-btn');
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.main-nav');
  
  // Initialize aria attributes
  if (toggle) {
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
  }
  
  // Attach click event to hamburger button
  if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      window.toggleNav();
    });
  }

  // Close menu when clicking on navigation links
  if (nav) {
    nav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        // Close menu after link click (with small delay for smooth transition)
        if (href.startsWith('#')) {
          setTimeout(() => {
            nav.classList.remove('active');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
          }, 100);
        }
      });
    });
  }

  // Close menu when clicking outside
  document.addEventListener('click', function(e) {
    if (nav && toggle && nav.classList.contains('active')) {
      // Check if click is outside both nav and toggle button
      if (!nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('active');
        toggle.setAttribute('aria-expanded', 'false');
      }
    }
  });

  // Close menu on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && nav && toggle) {
      nav.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });

  // Close menu when window is resized to desktop
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
      if (window.innerWidth >= 769 && nav && nav.classList.contains('active')) {
        nav.classList.remove('active');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
      }
    }, 250);
  });
}