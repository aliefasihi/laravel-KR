{{-- Magazine categories (every design's blog index) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if ($postCategories->isNotEmpty())
    <nav aria-label="دسته‌های مجله" class="flex flex-wrap gap-2">
        <a href="{{ route('content.blog') }}" @class(['rounded-full px-4 py-1.5 text-sm font-bold transition', 'bg-navy text-white' => ! $category, 'bg-surface/70 text-muted hover:text-navy' => $category]) @if (! $category) aria-current="page" @endif>همه</a>
        @foreach ($postCategories as $item)
            <a href="{{ $item->url() }}" @class(['rounded-full px-4 py-1.5 text-sm font-bold transition', 'bg-navy text-white' => $category?->is($item), 'bg-surface/70 text-muted hover:text-navy' => ! $category?->is($item)]) @if ($category?->is($item)) aria-current="page" @endif>
                {{ $item->name }} <span class="opacity-60">{{ \App\Core\Support\PersianText::toPersianDigits($item->posts_count) }}</span>
            </a>
        @endforeach
    </nav>
@endif
