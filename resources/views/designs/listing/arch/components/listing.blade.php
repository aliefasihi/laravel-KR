{{-- Product listing — «طاق»: filters in a soft cream column beside a roomy three-column grid; a rounded bottom
     sheet on phones; sorting as quiet text links | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['products', 'filters', 'sorts', 'action', 'brands' => collect(), 'filtered' => false, 'counts' => [], 'priceRange' => null, 'hidden' => [], 'filterAttributes' => collect(), 'valueCounts' => []])
@php
    use App\Core\Support\PersianText;
    $activeCount = count($filters['brand']) + (int) $filters['in_stock'] + (int) $filters['sale'] + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + array_sum(array_map('count', $filters['attr'] ?? []));
    $reset = $action.($hidden ? '?'.http_build_query($hidden) : '');
@endphp

<div class="grid gap-10 lg:grid-cols-[16rem_1fr] [&>*]:min-w-0" x-data="{ sheet: false }" @keydown.escape.window="sheet = false">
    {{-- Filters --}}
    <aside :class="sheet ? 'max-lg:fixed max-lg:inset-0 max-lg:z-[60] max-lg:flex max-lg:items-end' : 'max-lg:hidden'">
        <div class="absolute inset-0 bg-navy/30 lg:hidden" @click="sheet = false" aria-hidden="true"></div>
        <form method="get" action="{{ $action }}" x-data="autoSubmit" @change="if (window.innerWidth >= 1024) submit()"
              class="relative max-h-[85vh] w-full space-y-7 overflow-y-auto rounded-t-[2.5rem] bg-cream p-6 lg:sticky lg:top-28 lg:max-h-none lg:rounded-[2rem]">
            @include('listing.filters')
        </form>
    </aside>

    <section aria-label="محصولات" class="space-y-10">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 border-b border-line pb-4 text-sm">
            <button type="button" @click="sheet = true" class="inline-flex items-center gap-2 rounded-full bg-deep px-4 py-2 text-white lg:hidden">
                فیلترها
                @if ($activeCount)
                    <span class="grid size-5 place-items-center rounded-full bg-white text-[11px] text-deep">{{ PersianText::toPersianDigits($activeCount) }}</span>
                @endif
            </button>
            <span class="text-muted">{{ PersianText::toPersianDigits($products->total()) }} محصول</span>
            <nav class="rail ms-auto min-w-0 !mx-0 !gap-5 !px-0 !pb-0" aria-label="مرتب‌سازی">
                @foreach ($sorts as $key => $label)
                    <a href="{{ $action.'?'.http_build_query(array_filter(['sort' => $key] + request()->except('sort', 'page'))) }}"
                       @class(['shrink-0 border-b pb-1 transition', 'border-rose font-medium text-rose' => $filters['sort'] === $key, 'border-transparent text-muted hover:text-navy' => $filters['sort'] !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        @if ($products->isEmpty())
            <div class="flex flex-col items-center gap-4 rounded-[2.5rem] bg-cream px-6 py-16 text-center">
                <p class="text-xl font-light text-navy">با این فیلترها چیزی پیدا نشد</p>
                <a href="{{ $reset }}" class="rounded-full bg-deep px-6 py-2.5 text-sm text-white">حذف فیلترها</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-x-6 gap-y-14 md:grid-cols-3">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 3" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
</div>
