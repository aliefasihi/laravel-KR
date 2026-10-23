/**
 * Khaneh Romantic — Exit popup: shows the offer when the visitor is about to leave.
 * Desktop: the mouse leaves through the top of the window. Phones (optional): a quick scroll back
 * up after reading down the page. Not before N seconds on the page, at most once every N days per
 * browser (a new offer counts as new), never while typing or with another dialog open.
 * Loaded only on pages that carry the popup (#exit-popup-config); a separate small file.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

const KEY = 'kr-exit';

export default function exitPopup() {
    const config = JSON.parse(document.getElementById('exit-popup-config')?.textContent || 'null');
    const tpl = document.getElementById('exit-popup-tpl');
    if (!config || !tpl) return;

    const read = () => { try { return JSON.parse(localStorage.getItem(KEY) || '{}'); } catch { return {}; } };
    const write = (v) => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch { /* private mode */ } };
    const saved = read();
    if (saved.v === config.version && saved.until > Date.now()) return;

    const started = Date.now();
    let shown = false;
    const ready = () => !shown && Date.now() - started >= config.delay * 1000
        && ![...document.querySelectorAll('[role="dialog"][aria-modal="true"]')].some((el) => el.checkVisibility?.() ?? el.offsetParent !== null)
        && !document.querySelector('.chat-panel:not([hidden])')
        && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);

    const post = (url, data = {}) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.token },
        body: JSON.stringify(data),
        credentials: 'same-origin',
    });
    const track = (type) => post(config.event, { type }).catch(() => {});

    const show = () => {
        if (!ready()) return;
        shown = true;
        cleanup();
        write({ v: config.version, until: Date.now() + config.days * 864e5 });

        const root = tpl.content.firstElementChild.cloneNode(true);
        const card = root.querySelector('.exit-popup-card');
        root.style.opacity = '0';
        card.style.transform = 'translateY(12px) scale(.96)';
        document.body.appendChild(root);
        requestAnimationFrame(() => {
            root.style.transition = 'opacity .25s ease';
            card.style.transition = 'transform .3s cubic-bezier(.2,.9,.3,1.2)';
            root.style.opacity = '1';
            card.style.transform = 'none';
        });
        const before = document.activeElement;
        (root.querySelector('input, [data-exit-apply], [data-exit-convert]') || root.querySelector('button'))?.focus({ preventScroll: true });
        track('shown');

        let converted = false;
        const close = () => {
            if (!converted) track('closed');
            document.removeEventListener('keydown', onKey);
            root.style.opacity = '0';
            setTimeout(() => root.remove(), 250);
            before?.focus?.({ preventScroll: true });
        };
        const onKey = (e) => { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey);
        root.querySelectorAll('[data-exit-close]').forEach((el) => el.addEventListener('click', close));

        root.querySelectorAll('[data-exit-convert]').forEach((el) => el.addEventListener('click', () => {
            converted = true;
            track('converted');
        }));

        const apply = root.querySelector('[data-exit-apply]');
        apply?.addEventListener('click', async () => {
            converted = true;
            apply.disabled = true;
            try {
                const res = await post(config.apply);
                const data = await res.json();
                if (data.redirect) location.href = data.redirect;
            } catch { apply.disabled = false; }
        });

        const form = root.querySelector('[data-exit-lead]');
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const error = form.querySelector('[data-exit-error]');
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            error.classList.add('hidden');
            try {
                const res = await post(config.lead, { mobile: form.mobile.value });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'خطا؛ دوباره امتحان کنید.');
                converted = true;
                const result = root.querySelector('[data-exit-result]');
                result.querySelector('[data-exit-code]').textContent = data.code;
                result.querySelector('[data-exit-message]').textContent = data.message;
                result.querySelector('[data-exit-go]').href = data.redirect;
                form.classList.add('hidden');
                result.classList.remove('hidden');
                root.querySelector('[data-exit-convert]')?.classList.add('hidden');
            } catch (err) {
                error.textContent = err.message;
                error.classList.remove('hidden');
                button.disabled = false;
            }
        });
    };

    // desktop: leaving through the top of the window (toward the tabs / address bar)
    const onOut = (e) => { if (!e.relatedTarget && e.clientY <= 0) show(); };
    document.addEventListener('mouseout', onOut);

    // phones: a quick flick back up after reading down the page (speed measured over ~100 ms)
    let anchorY = scrollY, anchorT = performance.now(), deepest = 0;
    const onScroll = () => {
        const y = scrollY, t = performance.now();
        deepest = Math.max(deepest, y);
        const span = t - anchorT;
        if (span >= 60) {
            const speed = (anchorY - y) / span;   // px/ms, up = positive
            if (speed > 1.5 && deepest > innerHeight * 1.2 && y < deepest - innerHeight * 0.6) show();
        }
        if (span >= 100) { anchorY = y; anchorT = t; }
    };
    const touch = config.mobile && matchMedia('(pointer: coarse)').matches;
    if (touch) addEventListener('scroll', onScroll, { passive: true });

    function cleanup() {
        document.removeEventListener('mouseout', onOut);
        if (touch) removeEventListener('scroll', onScroll);
    }
}
