{{-- Product listing — «مینیمال»: full-width grid, toolbar with sorting + active filters, filters in a side drawer | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['products', 'filters', 'sorts', 'action', 'brands' => collect(), 'filtered' => false, 'counts' => [], 'priceRange' => null, 'hidden' => [], 'filterAttributes' => collect(), 'valueCounts' => []])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $activeCount = count($filters['brand']) + (int) $filters['in_stock'] + (int) $filters['sale'] + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + array_sum(array_map('count', $filters['attr'] ?? []));
    $reset = $action.($hidden ? '?'.http_build_query($hidden) : '');
    $without = fn (string $key, mixed $value = null) => $action.'?'.http_build_query(array_filter([
        ...request()->except('page', $key),
        ...($value !== null ? [$key => array_values(array_diff((array) request()->query($key, []), [$value]))] : []),
    ]));
    $chips = [];
    foreach ($brands->whereIn('slug', $filters['brand']) as $brand) {
        $chips[] = [$brand->name_fa, $without('brand', $brand->slug)];
    }
    if ($filters['sale']) $chips[] = ['تخفیف‌دار', $without('sale')];
    if ($filters['in_stock']) $chips[] = ['موجود', $without('in_stock')];
    if ($filters['min'] !== null || $filters['max'] !== null) {
        $chips[] = ['قیمت '.($filters['min'] !== null ? 'از '.Money::toman((int) $filters['min'] * 10) : '').($filters['max'] !== null ? ' تا '.Money::toman((int) $filters['max'] * 10) : ''), $action.'?'.http_build_query(request()->except('page', 'min', 'max'))];
    }
@endphp

<div class="space-y-6" x-data="{ sheet: false }" @keydown.escape.window="sheet = false">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3 border-y border-line py-3">
        <button type="button" @click="sheet = true" class="inline-flex items-center gap-2 rounded-full border border-line px-4 py-2 text-sm font-bold text-navy transition hover:border-navy">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            فیلترها
            @if ($activeCount)
                <span class="grid size-5 place-items-center rounded-full bg-rose text-[11px] text-white">{{ PersianText::toPersianDigits($activeCount) }}</span>
            @endif
        </button>
        <span class="text-sm text-muted">{{ PersianText::toPersianDigits($products->total()) }} محصول</span>
        <nav class="rail ms-auto min-w-0 !mx-0 !gap-1 !px-0 !pb-0" aria-label="مرتب‌سازی">
            @foreach ($sorts as $key => $label)
                <a href="{{ $action.'?'.http_build_query(array_filter(['sort' => $key] + request()->except('sort', 'page'))) }}"
                   @class(['shrink-0 rounded-full px-3.5 py-1.5 text-sm transition', 'bg-navy font-bold text-white' => $filters['sort'] === $key, 'text-muted hover:text-navy' => $filters['sort'] !== $key])>{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    @if ($chips)
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($chips as [$label, $url])
                <a href="{{ $url }}" class="inline-flex items-center gap-1.5 rounded-full bg-cream px-3 py-1.5 text-xs font-bold text-navy hover:bg-rose-100">
                    {{ $label }} <span aria-hidden="true">✕</span><span class="sr-only">حذف</span>
                </a>
            @endforeach
            <a href="{{ $reset }}" class="text-xs font-bold text-rose">حذف همه</a>
        </div>
    @endif

    {{-- Filters drawer --}}
    <div x-show="sheet" x-cloak class="fixed inset-0 z-[60] !m-0" role="dialog" aria-modal="true" aria-label="فیلتر محصولات">
        <div class="absolute inset-0 bg-deep/30 backdrop-blur-sm" @click="sheet = false" x-show="sheet" x-transition.opacity></div>
        <form method="get" action="{{ $action }}" x-show="sheet" x-trap.noscroll="sheet"
              x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200" x-transition:leave-end="translate-x-full"
              class="absolute inset-y-0 end-0 w-[22rem] max-w-[90vw] space-y-6 overflow-y-auto bg-surface p-6 shadow-2xl">
            @include('listing.filters', ['drawer' => true])
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        </form>
    </div>

    {{-- Results --}}
    <section aria-label="محصولات" class="space-y-10">
        @if ($products->isEmpty())
            <div class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-line px-6 py-16 text-center">
                <p class="font-bold text-navy">با این فیلترها محصولی پیدا نشد</p>
                <a href="{{ $reset }}" class="rounded-full border border-navy px-5 py-2 text-sm font-bold text-navy hover:bg-navy hover:text-white">حذف فیلترها</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 4" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
</div>
