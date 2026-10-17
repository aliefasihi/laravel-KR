/*
 * Khaneh Romantic — storefront scripts
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Alpine ships with Livewire; small UI behaviours are registered here.
 */

const toFa = (value) => String(value).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
window.toFa = toFa; // usable in Alpine expressions: x-text="toFa(count)"

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Auto-playing slider (hero banners). Pauses on hover/focus and when the tab is hidden.
    Alpine.data('slider', (count = 1, interval = 6000) => ({
        active: 0,
        count,
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
        go(index) {
            this.active = (index + this.count) % this.count;
        },
        next() {
            this.go(this.active + 1);
        },
        prev() {
            this.go(this.active - 1);
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
        active: 0,
        count,
        startX: null,
        go(index) {
            this.active = (index + this.count) % this.count;
        },
        next() {
            this.go(this.active + 1);
        },
        prev() {
            this.go(this.active - 1);
        },
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

    // Auto-submit filter forms (category listing) without a JS framework
    Alpine.data('autoSubmit', () => ({
        submit() {
            this.$root.requestSubmit();
        },
    }));

    // Copy the current URL (share)
    Alpine.data('share', () => ({
        copied: false,
        async copy() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch {
                /* clipboard unavailable */
            }
        },
    }));
});
