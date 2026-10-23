{{-- Hook «product.tabs» | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($count = \Modules\Questions\Models\Question::query()->published()->where('product_id', $product->id)->count())
<a href="#questions" class="shrink-0 rounded-full px-4 py-2 text-sm font-bold text-muted transition hover:bg-surface hover:text-navy">
    پرسش و پاسخ @if ($count)({{ \App\Core\Support\PersianText::toPersianDigits($count) }})@endif
</a>
