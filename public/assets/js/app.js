/**
 * Evulery.Pro - Dashboard JS
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Sidebar toggle (mobile)
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var toggle = document.getElementById('sidebar-toggle');
    var sidebarClose = document.getElementById('sidebar-close');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }

    if (toggle) {
        toggle.addEventListener('click', openSidebar);
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking a nav link (mobile)
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', closeSidebar);
        });
    }
});
