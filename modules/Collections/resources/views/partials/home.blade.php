{{-- Collections on the home page: small round bubbles, like the category circles | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($collections = \App\Core\Support\StoreCache::remember('collections.home', fn () => \Modules\Collections\Models\Collection::query()->visible()->with(['media', 'products' => fn ($q) => $q->active()->with('media')->latest('products.id')->limit(1)])
    ->orderBy('sort')->limit(12)->get()))
@if ($collections->isNotEmpty())
    <section class="space-y-5" aria-labelledby="home-collections">
        <x-section-title id="home-collections" accent="کالکشن‌های" :title="config('khaneh.store.name')" ghost="Collections"
                         :link="route('collections.index')" link-label="همه‌ی کالکشن‌ها" />
        <ul class="rail sm:justify-center">
            @foreach ($collections as $collection)
                @php($cover = $collection->media->first() ?? $collection->products->first()?->media->first())
                <li class="w-20 shrink-0 sm:w-24">
                    <a href="{{ $collection->url() }}" class="group flex flex-col items-center gap-2 text-center">
                        <span class="glass relative grid size-20 place-items-center overflow-hidden rounded-full p-1.5 transition duration-300 group-hover:-translate-y-1 group-hover:shadow-[0_18px_34px_-18px_rgb(204_51_102/0.6)] sm:size-24">
                            <span class="absolute inset-1.5 rounded-full bg-gradient-to-br from-cream to-rose-100"></span>
                            @if ($cover)
                                <x-media.picture :media="$cover" alt="" sizes="96px" class="relative size-full rounded-full object-cover transition duration-500 group-hover:scale-110" />
                            @else
                                <x-site.lotus :stroke="0.6" class="relative size-12 text-caramel/60" />
                            @endif
                        </span>
                        <span class="text-xs leading-5 sm:text-[13px] font-bold text-navy transition group-hover:text-rose">{{ $collection->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
