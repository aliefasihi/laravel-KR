{{-- Quick add button | نویسنده: علی فصیحی (Ali Fasihi) --}}
<button type="button" wire:click="add" wire:loading.attr="disabled"
        class="grid size-10 shrink-0 place-items-center rounded-full bg-navy text-white shadow-[0_8px_18px_-8px_rgb(35_40_79/0.9)] transition hover:bg-rose active:scale-95 disabled:opacity-60"
        aria-label="افزودن به سبد خرید">
    <svg wire:loading.remove wire:target="add" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        @if ($added)
            <path d="m5 12 5 5 9-10"/>
        @else
            <path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4M12 11v5M9.5 13.5h5"/>
        @endif
    </svg>
    <svg wire:loading wire:target="add" class="size-[18px] animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
</button>
