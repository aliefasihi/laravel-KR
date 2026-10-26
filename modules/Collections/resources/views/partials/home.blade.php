{{-- Collections row on the home page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($collections = \App\Core\Support\StoreCache::remember('collections.home', fn () => \Modules\Collections\Models\Collection::query()->visible()->with(['media', 'products' => fn ($q) => $q->active()->with('media')->latest('products.id')->limit(1)])
    ->withCount(['products' => fn ($q) => $q->active()])->orderBy('sort')->limit(6)->get()))
@if ($collections->isNotEmpty())
    <section class="mx-auto max-w-7xl space-y-5 px-3 sm:px-4" aria-labelledby="home-collections">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-collections" class="text-2xl font-black text-navy">کالکشن‌ها</h2>
            <a href="{{ route('collections.index') }}" class="text-sm font-bold text-rose">همه‌ی کالکشن‌ها</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                @include('collections::partials.card', ['collection' => $collection])
            @endforeach
        </div>
    </section>
@endif
