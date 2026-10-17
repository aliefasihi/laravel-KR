{{-- Signature: glazed kashi tile for a category | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['category'])

<a href="{{ $category->url() }}" {{ $attributes->class(['kashi-tile p-4 text-white']) }}>
    <x-khatam class="khatam" :ring="false" />
    <span class="font-display text-xl leading-tight drop-shadow-sm sm:text-2xl">{{ $category->name }}</span>
    @if ($category->products_count ?? false)
        <span class="mt-1 text-xs text-white/80">{{ \App\Core\Support\PersianText::toPersianDigits($category->products_count) }} محصول</span>
    @endif
</a>
