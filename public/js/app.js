/* HR PRO — lightweight progressive-enhancement helpers (no framework). */
(function () {
    'use strict';

    // --- Mobile sidebar toggle ---------------------------------------------
    const sidebar = document.getElementById('sidebar');
    const scrim = document.getElementById('scrim');
    document.querySelectorAll('[data-toggle="sidebar"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            sidebar && sidebar.classList.toggle('is-open');
            scrim && scrim.classList.toggle('is-open');
        });
    });
    scrim && scrim.addEventListener('click', function () {
        sidebar && sidebar.classList.remove('is-open');
        scrim.classList.remove('is-open');
    });

    // --- Sidebar rail (desktop): collapse to icons, remembered -------------
    // The class lives on <html> so the inline script in the layout can apply
    // it before first paint; this only handles the toggle and persistence.
    const navLinks = document.querySelectorAll('.sidebar .nav__link[data-label]');

    // Collapsed icons need their name on hover, and the sidebar clips its own
    // overflow, so the browser's own tooltip does the job.
    function syncRailLabels(railed) {
        navLinks.forEach(function (link) {
            if (railed) {
                link.setAttribute('title', link.dataset.label);
            } else {
                link.removeAttribute('title');
            }
        });
    }
    syncRailLabels(document.documentElement.classList.contains('is-rail'));

    document.querySelectorAll('[data-toggle="rail"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const railed = document.documentElement.classList.toggle('is-rail');
            syncRailLabels(railed);
            try { localStorage.setItem('hrpro.rail', railed ? '1' : '0'); } catch (e) { /* private mode */ }
        });
    });

    // --- Dropdown menus -----------------------------------------------------
    document.querySelectorAll('[data-dropdown]').forEach(function (root) {
        const trigger = root.querySelector('[data-dropdown-trigger]');
        const menu = root.querySelector('[data-dropdown-menu]');
        if (!trigger || !menu) return;
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function () { menu.style.display = 'none'; });
    });

    // --- Confirm before destructive form submits ---------------------------
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // --- Auto-dismiss flash alerts -----------------------------------------
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });
    document.querySelectorAll('.alert__close').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('.alert').remove(); });
    });

    // --- Live clock (attendance page) --------------------------------------
    const clock = document.getElementById('live-clock');
    if (clock) {
        const tick = function () {
            const d = new Date();
            clock.textContent = d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        };
        tick();
        setInterval(tick, 1000);
    }
})();
