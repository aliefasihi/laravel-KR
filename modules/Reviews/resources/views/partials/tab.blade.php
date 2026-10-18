{{-- Hook «product.tabs» | نویسنده: علی فصیحی (Ali Fasihi) --}}
<a href="#reviews" class="shrink-0 rounded-full px-4 py-2 text-sm font-bold text-muted transition hover:bg-surface hover:text-navy">
    نظرات @if ($product->rating_count)({{ \App\Core\Support\PersianText::toPersianDigits($product->rating_count) }})@endif
</a>
