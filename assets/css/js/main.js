// Barangay Connect – Main JS
// assets/js/main.js

document.addEventListener('DOMContentLoaded', function () {

    // ── Page Load Animation ──────────────────────────────────
    document.body.classList.add('page-loaded');

    // ── Page Transition on Link Click ────────────────────────
    const overlay = document.querySelector('.page-transition');

    document.querySelectorAll('a').forEach(function (link) {
        // Only intercept same-origin, non-anchor, non-logout links
        if (link.hostname !== window.location.hostname) return;
        if (link.href.includes('#')) return;
        if (link.href.includes('?action=logout')) return;
        if (link.getAttribute('target') === '_blank') return;

        link.addEventListener('click', function (e) {
            const href = link.getAttribute('href');
            if (!href || href === '#') return;

            e.preventDefault();
            if (overlay) {
                overlay.classList.add('fade-out');
            }
            setTimeout(function () {
                window.location.href = href;
            }, 350);
        });
    });

    // ── Auto-hide Alerts ─────────────────────────────────────
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(function () { alert.remove(); }, 500);
        }, 5000);
    });

    // ── Active Nav Link ──────────────────────────────────────
    document.querySelectorAll('.nav-link').forEach(function (link) {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });

    // ── Sidebar Toggle (mobile) ──────────────────────────────
    window.toggleSidebar = function () {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('sidebar-open');
        }
    };

});