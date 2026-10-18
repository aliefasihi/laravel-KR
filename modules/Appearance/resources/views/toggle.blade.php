{{-- Header light/dark switch | نویسنده: علی فصیحی (Ali Fasihi) --}}
<button type="button" class="btn-icon relative text-navy max-sm:hidden"
        x-data="{ dark: document.documentElement.classList.contains('dark') }"
        @theme-changed.window="dark = $event.detail.dark"
        @click="krTheme.toggle()"
        :aria-label="dark ? 'حالت روشن' : 'حالت تاریک'" :title="dark ? 'حالت روشن' : 'حالت تاریک'" aria-label="تغییر حالت روشن و تاریک">
    {{-- sun --}}
    <svg x-show="dark" x-cloak class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
    {{-- moon --}}
    <svg x-show="! dark" class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7Z"/></svg>
</button>
