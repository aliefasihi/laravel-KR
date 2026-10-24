{{-- Card «add to cart»: one tap for single-variant products (the header mini-cart does the work),
     otherwise an arrow to the product page. Shared by every card design. | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- No Alpine component per card: one delegated handler in app.js (quickAdd) runs every button on the page,
     and the icons come from the sprite in the layout (x-site.icons). --}}
@props(['variant' => null, 'single' => false])
@if ($single && $variant)
    <button type="button" data-quick-add="{{ $variant->id }}"
            {{ $attributes->class(['relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-deep text-white shadow-[0_8px_18px_-8px_rgb(35_40_79/0.9)] transition hover:bg-rose active:scale-95 disabled:opacity-60']) }}
            aria-label="افزودن به سبد خرید">
        <svg class="size-[18px]" aria-hidden="true"><use href="#i-cart"/></svg>
    </button>
@elseif ($variant)
    <span {{ $attributes->class(['relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-navy/5 text-navy transition group-hover:bg-deep group-hover:text-white']) }} aria-hidden="true">
        <svg class="size-4"><use href="#i-chevron"/></svg>
    </span>
@endif
