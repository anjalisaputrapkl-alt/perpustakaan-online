document.addEventListener("DOMContentLoaded", function () {
    console.log("📌 Dashboard Loaded");

    // Initialize Dashboard Stats (assumes initLoadDashboardStats is defined in another linked file like index.js)
    if (typeof initLoadDashboardStats === 'function') {
        initLoadDashboardStats();
    }

    // Initialize modal manager
    if (typeof modalManager !== 'undefined') {
        modalManager.init();
    }
});
