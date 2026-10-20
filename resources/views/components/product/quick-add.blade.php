{{-- Card «add to cart»: one tap for single-variant products (the header mini-cart does the work),
     otherwise an arrow to the product page. Shared by every card design. | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['variant' => null, 'single' => false])
@if ($single && $variant)
    {{-- One-tap add: plain Alpine → the header mini-cart handles it (no Livewire component per card). --}}
    <button type="button" x-data="{ busy: false, done: false }"
            @click="busy = true; window.Livewire?.dispatch('cart-add', { variantId: {{ $variant->id }} })"
            @cart-added.window="if ($event.detail.variantId === {{ $variant->id }}) { busy = false; done = $event.detail.added }"
            :disabled="busy"
            {{ $attributes->class(['relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-deep text-white shadow-[0_8px_18px_-8px_rgb(35_40_79/0.9)] transition hover:bg-rose active:scale-95 disabled:opacity-60']) }}
            aria-label="افزودن به سبد خرید">
        <svg x-show="!busy && !done" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4M12 11v5M9.5 13.5h5"/></svg>
        <svg x-show="!busy && done" x-cloak class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
        <svg x-show="busy" x-cloak class="size-[18px] animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    </button>
@elseif ($variant)
    <span {{ $attributes->class(['relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-navy/5 text-navy transition group-hover:bg-deep group-hover:text-white']) }} aria-hidden="true">
        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
    </span>
@endif
