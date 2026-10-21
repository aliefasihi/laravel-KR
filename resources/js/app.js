/*
 * Khaneh Romantic — storefront scripts
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Alpine ships with Livewire; small UI behaviours are registered here.
 */

const toFa = (value) => String(value).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
window.toFa = toFa; // usable in Alpine expressions: x-text="toFa(count)"

// Number fields work in any keyboard language: ۰۹۱۲ / ٠٩١٢ become 0912 while typing (before
// Livewire/Alpine see the value). [data-latin] code fields also turn Persian-layout letters into the
// Latin key under them (ق ن → r k). [data-digits=N] keeps only N digits (pasting the whole SMS works).
const toLatinDigits = (value) => value.replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
window.toLatinDigits = toLatinDigits;
const persianKeys = { 'ض': 'q', 'ص': 'w', 'ث': 'e', 'ق': 'r', 'ف': 't', 'غ': 'y', 'ع': 'u', 'ه': 'i', 'خ': 'o', 'ح': 'p', 'ش': 'a', 'س': 's', 'ی': 'd', 'ي': 'd', 'ب': 'f', 'ل': 'g', 'ا': 'h', 'ت': 'j', 'ن': 'k', 'م': 'l', 'ظ': 'z', 'ط': 'x', 'ز': 'c', 'ر': 'v', 'ذ': 'b', 'د': 'n', 'پ': 'm', 'ئ': 'm' };
document.addEventListener('input', (event) => {
    const el = event.target;
    if (!(el instanceof HTMLInputElement) || !el.matches('[type=tel], [inputmode=numeric], [data-latin], [data-digits]')) return;
    let value = toLatinDigits(el.value);
    if (el.matches('[data-latin]')) value = value.replace(/./gu, (c) => persianKeys[c] ?? c).toUpperCase();
    if (el.dataset.digits) value = value.replace(/\D/g, '').slice(0, Number(el.dataset.digits));
    if (value === el.value) return;
    const caret = el.selectionStart === null ? null : Math.min(el.selectionStart - (el.value.length - value.length), value.length);
    el.value = value;
    if (caret !== null && document.activeElement === el) {
        try { el.setSelectionRange(Math.max(0, caret), Math.max(0, caret)); } catch { /* type=tel/number may not support it */ }
    }
}, true);

// Shared by the slider and the product gallery: an index that wraps around.
const cycle = (count) => ({
    active: 0,
    count,
    go(index) {
        this.active = (index + this.count) % this.count;
    },
    next() {
        this.go(this.active + 1);
    },
    prev() {
        this.go(this.active - 1);
    },
});

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Auto-playing slider (hero banners). Pauses on hover/focus and when the tab is hidden.
    Alpine.data('slider', (count = 1, interval = 6000) => ({
        ...cycle(count),
        timer: null,
        init() {
            this.play();
            document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.play()));
        },
        play() {
            this.stop();
            if (this.count > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.timer = setInterval(() => this.next(), interval);
            }
        },
        stop() {
            clearInterval(this.timer);
        },
    }));

    // Countdown to an ISO date (special offers). Shows Persian digits.
    Alpine.data('countdown', (until) => ({
        parts: { d: '۰', h: '۰۰', m: '۰۰', s: '۰۰' },
        done: false,
        init() {
            const end = new Date(until).getTime();
            const tick = () => {
                const left = Math.max(0, end - Date.now());
                this.done = left === 0;
                const pad = (n) => toFa(String(n).padStart(2, '0'));
                this.parts = {
                    d: toFa(Math.floor(left / 864e5)),
                    h: pad(Math.floor((left / 36e5) % 24)),
                    m: pad(Math.floor((left / 6e4) % 60)),
                    s: pad(Math.floor((left / 1e3) % 60)),
                };
            };
            tick();
            setInterval(tick, 1000);
        },
    }));

    // Product gallery: thumbnails + swipe on touch
    Alpine.data('gallery', (count = 1) => ({
        ...cycle(count),
        startX: null,
        touchStart(e) {
            this.startX = e.touches[0].clientX;
        },
        touchEnd(e) {
            if (this.startX === null) return;
            const dx = e.changedTouches[0].clientX - this.startX;
            if (Math.abs(dx) > 40) dx < 0 ? this.prev() : this.next(); // RTL: swipe left = previous
            this.startX = null;
        },
    }));

    // Horizontal rail with prev/next buttons
    Alpine.data('rail', () => ({
        scroll(dir) {
            const el = this.$refs.track;
            el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
        },
    }));

    // Infinite, auto-playing product rail (HomeSliders). The items are rendered once; the browser
    // copies them before and after so scrolling never hits an end, and quietly jumps back by one
    // set once a scroll settles (same picture, so the jump is invisible). RTL-aware.
    // Autoplay: one card every `interval` ms, only while on screen, never while the shopper is
    // touching/hovering/focusing it, and never with "reduce motion".
    Alpine.data('loopRail', (interval = 4000, autoplay = true) => ({
        timer: null,
        held: false,
        paused: false,
        visible: false,
        count: 0,
        sign: 1,
        init() {
            const track = this.$refs.track;
            const items = [...track.children];
            // everything already fits on screen: a plain row, nothing to loop
            if (items.length < 2 || track.scrollWidth <= track.clientWidth + 8) return;

            const copy = (el) => {
                const clone = el.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.setAttribute('data-clone', '');
                clone.querySelectorAll('a, button, input, [tabindex]').forEach((n) => n.setAttribute('tabindex', '-1'));
                clone.querySelectorAll('[id]').forEach((n) => n.removeAttribute('id'));
                return clone;
            };
            items.forEach((el) => track.append(copy(el)));
            [...items].reverse().forEach((el) => track.prepend(copy(el)));
            this.count = items.length;
            this.sign = getComputedStyle(track).direction === 'rtl' ? -1 : 1;

            // start on the real items (middle set)
            requestAnimationFrame(() => this.jump(this.setWidth()));

            let settle;
            track.addEventListener('scroll', () => {
                clearTimeout(settle);
                settle = setTimeout(() => this.normalize(), 140);
            }, { passive: true });

            const hold = () => { this.held = true; this.stop(); };
            const release = () => { this.held = false; this.play(); };
            track.addEventListener('pointerdown', hold, { passive: true });
            track.addEventListener('touchstart', hold, { passive: true });
            track.addEventListener('touchend', () => setTimeout(release, 3000), { passive: true });
            track.addEventListener('pointerup', (e) => e.pointerType === 'mouse' || setTimeout(release, 3000));

            new IntersectionObserver(([entry]) => {
                this.visible = entry.isIntersecting;
                this.visible ? this.play() : this.stop();
            }, { threshold: 0.25 }).observe(track);
            document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.play()));
            window.addEventListener('resize', () => this.normalize(), { passive: true });
        },
        // width of one full set of items (incl. gaps): distance between item 0 and its next copy
        setWidth() {
            const kids = this.$refs.track.children;
            return Math.abs(kids[this.count * 2].offsetLeft - kids[this.count].offsetLeft);
        },
        itemWidth() {
            const kids = this.$refs.track.children;
            return Math.abs(kids[this.count + 1].offsetLeft - kids[this.count].offsetLeft);
        },
        position() {
            return Math.abs(this.$refs.track.scrollLeft);
        },
        jump(to) {
            const track = this.$refs.track;
            track.style.scrollBehavior = 'auto';
            track.style.scrollSnapType = 'none';
            track.scrollLeft = this.sign * to;
            track.offsetHeight; // apply before restoring snap/smooth
            track.style.scrollSnapType = '';
            track.style.scrollBehavior = '';
        },
        normalize() {
            const w = this.setWidth();
            if (!w) return;
            const p = this.position();
            if (p < w * 0.5) this.jump(p + w);
            else if (p >= w * 1.5) this.jump(p - w);
        },
        step(dir = 1) {
            this.$refs.track.scrollBy({ left: this.sign * dir * this.itemWidth(), behavior: 'smooth' });
        },
        play() {
            this.stop();
            if (!autoplay || this.held || !this.visible || this.paused || !this.count) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.timer = setInterval(() => this.step(1), interval);
        },
        stop() {
            clearInterval(this.timer);
            this.timer = null;
        },
        pause() {
            this.paused = true;
            this.stop();
        },
        resume() {
            this.paused = false;
            this.play();
        },
    }));

    // OTP login (Customer module): resend countdown, auto-submit when the code is complete,
    // and WebOTP — Chrome on Android fills the code from the SMS (its last line "@domain #code").
    Alpine.data('otpLogin', (retryIn = 0, length = 5) => ({
        left: retryIn,
        timer: null,
        get clock() {
            const s = Math.max(0, this.left);
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        },
        init() {
            if (this.left > 0) this.start(this.left);
        },
        start(seconds) {
            this.left = seconds ?? 0;
            clearInterval(this.timer);
            this.timer = setInterval(() => --this.left <= 0 && clearInterval(this.timer), 1000);
            this.webOtp();
        },
        autoSubmit(input) {
            const digits = toLatinDigits(input.value).replace(/\D/g, '');
            if (digits.length === length) {
                this.$wire.set('code', digits, false);
                this.$wire.verify();
            }
        },
        webOtp() {
            if (!('OTPCredential' in window) || this.otpPending) return;
            this.otpPending = true;
            const ac = new AbortController();
            setTimeout(() => ac.abort(), 120000);
            navigator.credentials.get({ otp: { transport: ['sms'] }, signal: ac.signal })
                .then((otp) => {
                    if (!otp?.code) return;
                    this.$wire.set('code', otp.code, false);
                    this.$wire.verify();
                })
                .catch(() => {})
                .finally(() => (this.otpPending = false));
        },
    }));

    // Auto-submit filter forms (category listing) without a JS framework
    Alpine.data('autoSubmit', () => ({
        submit() {
            this.$root.requestSubmit();
        },
    }));

    // Instant search: debounced fetch of /search/suggest, cached per term, keyboard navigation.
    Alpine.data('searchBox', (initial = '', endpoint = '/search/suggest') => ({
        q: initial,
        open: false,
        loading: false,
        active: -1,
        data: { products: [], categories: [], brands: [] },
        cache: new Map(),
        timer: null,
        controller: null,
        input() {
            clearTimeout(this.timer);
            const term = this.q.trim();
            if (term.length < 2) {
                this.open = false;
                return;
            }
            this.timer = setTimeout(() => this.fetch(term), 160);
        },
        focus() {
            if (this.q.trim().length >= 2) {
                this.cache.has(this.q.trim()) ? (this.open = true) : this.input();
            }
        },
        async fetch(term) {
            if (this.cache.has(term)) {
                return this.show(term, this.cache.get(term));
            }
            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;
            try {
                const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                    signal: this.controller.signal,
                });
                if (!response.ok) return;
                const data = await response.json();
                this.cache.set(term, data);
                this.show(term, data);
            } catch (error) {
                if (error.name !== 'AbortError') console.warn('search suggest failed', error);
            } finally {
                this.loading = false;
            }
        },
        show(term, data) {
            if (term !== this.q.trim()) return; // a newer keystroke won
            this.data = data;
            this.active = -1;
            this.open = true;
        },
        links() {
            return [...this.$root.querySelectorAll('[data-suggest-link]')];
        },
        move(step) {
            const count = this.links().length;
            if (!this.open || count === 0) return;
            this.active = (this.active + step + count) % count;
        },
        enter(event) {
            if (this.open && this.active >= 0) {
                event.preventDefault();
                this.links()[this.active]?.click();
            }
        },
    }));

    // Copy the current URL (share)
    // «Recently viewed»: the ids live only in this browser (localStorage «kr-recent», newest first,
    // 12 at most); the row's HTML comes from /recently-viewed?ids=… in the live design.
    Alpine.data('recentlyViewed', (current = 0, endpoint = '/recently-viewed') => ({
        html: '',
        key: 'kr-recent',
        read() {
            try { return JSON.parse(localStorage.getItem(this.key) || '[]').filter(Number.isInteger); } catch { return []; }
        },
        write(ids) {
            try { localStorage.setItem(this.key, JSON.stringify(ids)); } catch { /* private mode */ }
        },
        async init() {
            let ids = this.read();
            if (current) {
                ids = [current, ...ids.filter((id) => id !== current)].slice(0, 12);
                this.write(ids);
            }
            const others = ids.filter((id) => id !== current).slice(0, 10);
            if (!others.length) return;
            try {
                const response = await fetch(endpoint + '?ids=' + others.join(','), { headers: { Accept: 'text/html' } });
                if (response.status === 200) this.html = await response.text();
            } catch { /* offline: no row */ }
        },
        clear() {
            this.write(current ? [current] : []);
            this.html = '';
        },
    }));

    // «Install the app» banner (PWA module): from the third page view, not in the installed app,
    // not again for 30 days after «بعداً». Android/desktop Chrome: the browser's own install dialog
    // (its event is caught early in the <head>); iPhone Safari: how to add it to the home screen.
    Alpine.data('pwaInstall', () => ({
        open: false,
        ios: false,
        key: 'kr-pwa',
        state() {
            try { return JSON.parse(localStorage.getItem(this.key) || '{}'); } catch { return {}; }
        },
        save(state) {
            try { localStorage.setItem(this.key, JSON.stringify(state)); } catch { /* private mode */ }
        },
        init() {
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) return;
            const state = this.state();
            if (state.until && state.until > Date.now()) return;
            state.views = (state.views || 0) + 1;
            this.save(state);
            if (state.views < 3) return;

            const ua = navigator.userAgent;
            this.ios = /iphone|ipad|ipod/i.test(ua) && /safari/i.test(ua) && !/crios|fxios|edgios|opios/i.test(ua);
            const show = () => setTimeout(() => (this.open = true), 3000);
            if (this.ios) return show();
            if (window.krInstallPrompt) return show();
            window.addEventListener('kr-installable', show, { once: true });
            window.addEventListener('appinstalled', () => this.later(3650));
        },
        async install() {
            const prompt = window.krInstallPrompt;
            if (!prompt) return this.later();
            prompt.prompt();
            const choice = await prompt.userChoice.catch(() => ({}));
            window.krInstallPrompt = null;
            this.later(choice.outcome === 'accepted' ? 3650 : 30);
        },
        later(days = 30) {
            this.open = false;
            this.save({ ...this.state(), until: Date.now() + days * 864e5 });
        },
    }));

    Alpine.data('share', () => ({
        copied: false,
        async copy(text = null) {   // copy(): this page's address; copy('…'): any text
            try {
                await navigator.clipboard.writeText(typeof text === 'string' ? text : window.location.href);   // @click="copy" passes the event
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch {
                /* clipboard unavailable */
            }
        },
    }));
});
