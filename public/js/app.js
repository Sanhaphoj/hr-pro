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

    // --- Confirm before consequential form submits -------------------------
    // An in-page dialog in place of the browser's grey confirm() box. A
    // message about deleting gets the red button; anything else the brand one.
    let confirmDialog = null;
    function askConfirm(message, onYes) {
        if (!confirmDialog) {
            confirmDialog = document.createElement('dialog');
            confirmDialog.className = 'confirm';
            confirmDialog.innerHTML =
                '<form method="dialog" class="confirm__box">' +
                '<div class="confirm__icon"></div>' +
                '<p class="confirm__text"></p>' +
                '<div class="confirm__actions">' +
                '<button value="no" class="btn btn--secondary">ยกเลิก</button>' +
                '<button value="yes" class="btn confirm__yes">ยืนยัน</button>' +
                '</div></form>';
            document.body.appendChild(confirmDialog);
            confirmDialog.addEventListener('click', function (e) {
                if (e.target === confirmDialog) confirmDialog.close('no');   // backdrop
            });
        }
        const danger = /ลบ|delete|ยกเลิก/i.test(message);
        confirmDialog.querySelector('.confirm__text').textContent = message;
        confirmDialog.querySelector('.confirm__icon').textContent = danger ? '!' : '?';
        confirmDialog.classList.toggle('is-danger', danger);
        const yes = confirmDialog.querySelector('.confirm__yes');
        yes.className = 'btn confirm__yes ' + (danger ? 'btn--danger' : 'btn--primary');
        yes.textContent = danger ? 'ยืนยัน' : 'ตกลง';
        confirmDialog.returnValue = '';
        confirmDialog.onclose = function () { if (confirmDialog.returnValue === 'yes') onYes(); };
        confirmDialog.showModal();
        yes.focus();
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;
            e.preventDefault();
            const submitter = e.submitter;
            askConfirm(form.getAttribute('data-confirm'), function () {
                form.dataset.confirmed = '1';
                form.requestSubmit ? form.requestSubmit(submitter || undefined) : form.submit();
            });
        });
    });

    // --- Toasts --------------------------------------------------------------
    // Server flashes are rendered straight into the fixed stack in the
    // corner; this makes them leave on their own. window.hrproToast() adds more.
    let toastStack = document.querySelector('.toast-stack');
    if (!toastStack) {
        toastStack = document.createElement('div');
        toastStack.className = 'toast-stack';
        toastStack.setAttribute('aria-live', 'polite');
        document.body.appendChild(toastStack);
    }

    function dismissToast(el) {
        el.classList.add('is-leaving');
        setTimeout(function () { el.remove(); }, 260);
    }
    function mountToast(el) {
        toastStack.appendChild(el);
        el.querySelector('[data-toast-close]')?.addEventListener('click', function () { dismissToast(el); });
        let timer = setTimeout(function () { dismissToast(el); }, 4500);
        el.addEventListener('mouseenter', function () { clearTimeout(timer); });
        el.addEventListener('mouseleave', function () { timer = setTimeout(function () { dismissToast(el); }, 2000); });
    }
    document.querySelectorAll('[data-toast]').forEach(mountToast);
    window.hrproToast = function (message, type) {
        const el = document.createElement('div');
        el.className = 'toast toast--' + (type || 'success');
        el.setAttribute('role', 'status');
        el.innerHTML = '<span></span><button type="button" class="toast__close" aria-label="ปิด" data-toast-close>×</button>';
        el.querySelector('span').textContent = message;
        mountToast(el);
    };

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

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    // --- Chart tooltips ------------------------------------------------------
    // Any element with data-tip="Title|line|line" gets a floating label on
    // hover and on keyboard focus.
    const tip = document.createElement('div');
    tip.className = 'viz-tip';
    tip.setAttribute('role', 'tooltip');
    document.body.appendChild(tip);

    function showTip(target, x, y) {
        const parts = (target.getAttribute('data-tip') || '').split('|');
        tip.innerHTML = '';
        const head = document.createElement('b');
        head.textContent = parts.shift();
        tip.appendChild(head);
        parts.forEach(function (line) {
            const row = document.createElement('div');
            row.textContent = line;
            tip.appendChild(row);
        });
        const w = tip.offsetWidth, h = tip.offsetHeight;
        tip.style.left = Math.min(window.innerWidth - w - 8, Math.max(8, x - w / 2)) + 'px';
        tip.style.top = Math.max(8, y - h - 12) + 'px';
        tip.classList.add('is-on');
    }
    document.addEventListener('mousemove', function (e) {
        const target = e.target.closest && e.target.closest('[data-tip]');
        if (target) showTip(target, e.clientX, e.clientY);
        else tip.classList.remove('is-on');
    });
    document.addEventListener('focusin', function (e) {
        const target = e.target.closest && e.target.closest('[data-tip]');
        if (!target) return;
        const r = target.getBoundingClientRect();
        showTip(target, r.left + r.width / 2, r.top);
    });
    document.addEventListener('focusout', function () { tip.classList.remove('is-on'); });

    // --- Light / dark switch -------------------------------------------------
    function toggleScheme() {
        const el = document.documentElement;
        const next = el.dataset.scheme === 'dark' ? 'light' : 'dark';
        el.dataset.scheme = next;
        const url = document.querySelector('[data-scheme-toggle]')?.dataset.url;
        if (url) {
            fetch(url, {
                method: 'PATCH',
                keepalive: true,   // still saved if the person clicks away at once
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ color_scheme: next }),
            }).catch(function () { /* the page has already switched; next load falls back */ });
        }
    }
    document.querySelectorAll('[data-scheme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', toggleScheme);
    });

    // --- Command palette (Ctrl+K) -------------------------------------------
    const cmdk = document.getElementById('cmdk');
    if (cmdk) {
        const input = cmdk.querySelector('.cmdk__input');
        const list = cmdk.querySelector('.cmdk__list');
        const staticItems = JSON.parse(document.getElementById('cmdk-items').textContent);
        const iconSource = document.getElementById('cmdk-icons').content;
        let remote = [];
        let shown = [];
        let active = 0;
        let pending = null;
        let lastQuery = '';

        const norm = function (s) { return (s || '').toLowerCase().normalize('NFC'); };

        // Every word typed must appear somewhere in the title or keywords;
        // title hits rank above keyword-only hits.
        function score(item, words) {
            const title = norm(item.title), hay = title + ' ' + norm(item.keywords) + ' ' + norm(item.hint);
            let s = 0;
            for (const w of words) {
                if (!hay.includes(w)) return -1;
                s += title.startsWith(w) ? 3 : title.includes(w) ? 2 : 1;
            }
            return s;
        }

        function iconFor(item) {
            if (item.initials) {
                const a = document.createElement('span');
                a.className = 'cmdk__avatar';
                a.style.background = item.color;
                a.textContent = item.initials;
                return a;
            }
            const svg = iconSource.querySelector('[data-icon="' + (item.icon || 'search') + '"]');
            const wrap = document.createElement('span');
            wrap.className = 'cmdk__icon';
            if (svg) wrap.appendChild(svg.cloneNode(true));
            return wrap;
        }

        function render() {
            const q = norm(input.value.trim());
            const words = q.split(/\s+/).filter(Boolean);
            let items = staticItems;
            if (words.length) {
                items = staticItems
                    .map(function (it) { return [it, score(it, words)]; })
                    .filter(function (p) { return p[1] >= 0; })
                    .sort(function (a, b) { return b[1] - a[1]; })
                    .map(function (p) { return p[0]; });
            }
            shown = items.concat(remote);
            active = Math.min(active, Math.max(0, shown.length - 1));
            list.innerHTML = '';

            if (!shown.length) {
                const empty = document.createElement('li');
                empty.className = 'cmdk__empty';
                empty.textContent = pending ? 'กำลังค้นหา…' : 'ไม่พบผลลัพธ์สำหรับ “' + input.value + '”';
                list.appendChild(empty);
                return;
            }

            let group = null;
            shown.forEach(function (item, i) {
                if (item.group !== group) {
                    group = item.group;
                    const g = document.createElement('li');
                    g.className = 'cmdk__group';
                    g.setAttribute('role', 'presentation');
                    g.textContent = group;
                    list.appendChild(g);
                }
                const li = document.createElement('li');
                li.className = 'cmdk__item' + (i === active ? ' is-active' : '');
                li.id = 'cmdk-opt-' + i;
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', i === active ? 'true' : 'false');
                li.appendChild(iconFor(item));
                const text = document.createElement('span');
                text.className = 'cmdk__text';
                const t = document.createElement('span');
                t.textContent = item.title;
                text.appendChild(t);
                if (item.hint) {
                    const h = document.createElement('small');
                    h.textContent = item.hint;
                    text.appendChild(h);
                }
                li.appendChild(text);
                li.addEventListener('mousemove', function () { if (active !== i) { active = i; highlight(); } });
                li.addEventListener('click', function () { run(item); });
                list.appendChild(li);
            });
            input.setAttribute('aria-activedescendant', 'cmdk-opt-' + active);
        }

        function highlight() {
            list.querySelectorAll('.cmdk__item').forEach(function (li, i) {
                li.classList.toggle('is-active', i === active);
                li.setAttribute('aria-selected', i === active ? 'true' : 'false');
                if (i === active) li.scrollIntoView({ block: 'nearest' });
            });
            input.setAttribute('aria-activedescendant', 'cmdk-opt-' + active);
        }

        function run(item) {
            if (!item) return;
            if (item.action === 'scheme') { cmdk.close(); toggleScheme(); return; }
            if (item.url) window.location.href = item.url;
        }

        function searchRemote() {
            const q = input.value.trim();
            if (q === lastQuery) return;
            lastQuery = q;
            clearTimeout(pending);
            if (q.length < 2) { remote = []; pending = null; render(); return; }
            pending = setTimeout(function () {
                fetch(cmdk.dataset.searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : { results: [] }; })
                    .then(function (data) {
                        if (input.value.trim() !== q) return;   // a newer query is on its way
                        remote = data.results || [];
                        pending = null;
                        render();
                    })
                    .catch(function () { pending = null; });
            }, 180);
        }

        function open() {
            if (cmdk.open) return;
            input.value = ''; lastQuery = ''; remote = []; active = 0;
            render();
            cmdk.showModal();
            input.focus();
        }

        input.addEventListener('input', function () { active = 0; render(); searchRemote(); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); active = (active + 1) % Math.max(1, shown.length); highlight(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = (active - 1 + shown.length) % Math.max(1, shown.length); highlight(); }
            else if (e.key === 'Enter') { e.preventDefault(); run(shown[active]); }
        });
        cmdk.addEventListener('click', function (e) { if (e.target === cmdk) cmdk.close(); });

        document.addEventListener('keydown', function (e) {
            const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open(); }
            else if (e.key === '/' && !typing && !cmdk.open) { e.preventDefault(); open(); }
        });
        document.querySelectorAll('[data-cmdk-open]').forEach(function (btn) { btn.addEventListener('click', open); });
    }

    // --- Installable app ----------------------------------------------------
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () { /* the site still works without it */ });
        });
    }
    let installPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        installPrompt = e;
        document.querySelectorAll('[data-install]').forEach(function (btn) { btn.hidden = false; });
    });
    document.querySelectorAll('[data-install]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!installPrompt) return;
            installPrompt.prompt();
            installPrompt.userChoice.finally(function () {
                installPrompt = null;
                document.querySelectorAll('[data-install]').forEach(function (b) { b.hidden = true; });
            });
        });
    });
    window.addEventListener('appinstalled', function () {
        window.hrproToast && window.hrproToast('ติดตั้งแอปเรียบร้อยแล้ว เปิดได้จากหน้าจอหลัก');
    });

    // --- Navigation progress -------------------------------------------------
    // A thin bar along the top while the next page loads, so a slow server
    // (or a sleeping free host) never looks like a dead click.
    const bar = document.createElement('div');
    bar.className = 'navbar-progress';
    document.body.appendChild(bar);
    window.addEventListener('beforeunload', function () {
        bar.classList.add('is-loading');
        // A download (CSV export) fires this too but never leaves the page.
        setTimeout(function () { bar.classList.remove('is-loading'); }, 8000);
    });
    window.addEventListener('pageshow', function () { bar.classList.remove('is-loading'); });
})();
