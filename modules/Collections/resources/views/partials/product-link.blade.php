{{-- «از کالکشن …» on the product page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($collections = isset($product) ? $product->collections()->where('is_active', true)->get(['collections.id', 'name', 'slug']) : collect())
@if ($collections->isNotEmpty())
    <p class="flex flex-wrap items-center gap-2 text-sm text-muted">
        <x-site.lotus class="size-4 text-caramel" />
        از کالکشن
        @foreach ($collections as $collection)
            <a href="{{ $collection->url() }}" class="font-bold text-navy underline decoration-caramel/50 underline-offset-4 hover:text-rose">{{ $collection->name }}</a>
        @endforeach
    </p>
@endif
