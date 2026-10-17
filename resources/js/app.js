/*
 * Khaneh Romantic — storefront scripts
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Alpine ships with Livewire; register small UI components here.
 */

document.addEventListener('alpine:init', () => {
    // Product image gallery: thumbnails + swipeable main image
    window.Alpine.data('gallery', (count = 1) => ({
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
    }));

    // Auto-submit filter forms (category listing) without a JS framework
    window.Alpine.data('autoSubmit', () => ({
        submit() {
            this.$root.requestSubmit();
        },
    }));
});
