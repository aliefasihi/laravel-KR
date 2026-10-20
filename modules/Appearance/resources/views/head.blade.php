{{-- Light/dark before first paint + the tiny API the switches use | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($default = \App\Core\Settings\Settings::get('appearance.default', 'system'))
@php($bar = [\App\Core\Support\Designs::themeColor(), \App\Core\Support\Designs::themeColor(dark: true)])   {{-- phone browser bar: [light, dark] of the chosen design --}}
<meta name="color-scheme" content="light dark">
<script>
(() => {
    const KEY = 'kr-theme', fallback = @js($default);
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const root = document.documentElement;
    const stored = () => { try { return localStorage.getItem(KEY); } catch (e) { return null; } };
    const mode = () => (['light', 'dark', 'system'].includes(stored()) ? stored() : fallback);
    const apply = (animate) => {
        const dark = mode() === 'dark' || (mode() === 'system' && media.matches);
        if (animate) {
            root.classList.add('theme-switching');
            setTimeout(() => root.classList.remove('theme-switching'), 350);
        }
        root.classList.toggle('dark', dark);
        document.querySelector('meta[name=theme-color]')?.setAttribute('content', dark ? @js($bar[1]) : @js($bar[0]));
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { mode: mode(), dark } }));
    };
    window.krTheme = {
        mode,
        isDark: () => root.classList.contains('dark'),
        set(value) {
            try { localStorage.setItem(KEY, value); } catch (e) {}
            apply(true);
        },
        toggle() { this.set(this.isDark() ? 'light' : 'dark'); },
    };
    media.addEventListener?.('change', () => mode() === 'system' && apply(true));
    apply(false);
    document.addEventListener('DOMContentLoaded', () => apply(false)); // theme-color meta comes later in <head>
})();
</script>
<style>
    .theme-switching, .theme-switching *, .theme-switching *::before, .theme-switching *::after {
        transition: background-color .3s ease, color .3s ease, border-color .3s ease !important;
    }
</style>
