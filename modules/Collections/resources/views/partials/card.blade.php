{{-- Collection card | نویسنده: علی فصیحی (Ali Fasihi) --}}
<a href="{{ $collection->url() }}" class="glass group relative block overflow-hidden rounded-[var(--radius-card)] p-2.5 transition duration-300 hover:-translate-y-1">
    <div class="relative aspect-[4/3] overflow-hidden rounded-[1rem] bg-gradient-to-br from-cream to-rose-100">
        @php($cover = $collection->media->first() ?? $collection->products->first()?->media->first())
        @if ($cover)
            <x-media.picture :media="$cover" :alt="'کالکشن '.$collection->name" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                             class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-[1.04]" />
        @else
            <x-site.lotus :stroke="0.6" class="absolute inset-0 m-auto size-24 text-caramel/50" />
        @endif
    </div>
    <div class="px-2 pt-3 pb-1.5">
        <p class="text-lg font-black text-navy group-hover:text-rose">کالکشن {{ $collection->name }}</p>
        <p class="mt-1 text-sm text-muted">
            {{ $collection->tagline ?: \App\Core\Support\PersianText::toPersianDigits((string) $collection->products_count).' محصول' }}
        </p>
    </div>
</a>
