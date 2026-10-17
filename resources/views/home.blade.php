{{-- Home | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'لوازم آشپزخانه و اکسسوری خانه')
@section('meta_description', 'خرید قابلمه، ماهیتابه، سرویس غذاخوری و اکسسوری خانه از برندهای گلدکیش و ریتون با ارسال به سراسر ایران.')
@section('canonical', url('/'))

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'خانه رمانتیک',
        'url' => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => route('catalog.search').'?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    {{-- Hero: managed banner if one is live, typographic fallback otherwise --}}
    <section class="mx-auto max-w-7xl px-4 pt-6">
        <x-placement key="home.hero" />

        @if (! \Modules\Placement\Models\Placement::hasLive('home.hero'))
            <div class="relative overflow-hidden rounded-[calc(var(--radius-tile)*1.5)] bg-glaze-deep px-6 py-14 text-white sm:px-12 sm:py-20">
                <x-khatam class="pointer-events-none absolute -end-16 -top-16 size-80 text-white/10" />
                <x-khatam class="pointer-events-none absolute end-40 -bottom-24 size-56 text-white/5" />
                <p class="text-sm font-medium text-mist">گلدکیش · ریتون</p>
                <h1 class="mt-3 max-w-2xl font-display text-4xl leading-[1.2] sm:text-6xl sm:leading-[1.15]">آشپزخانه‌ای که دلت می‌خواهد در آن بمانی</h1>
                <p class="mt-5 max-w-xl leading-8 text-white/80">قابلمه، ماهیتابه، سرویس غذاخوری و هر چه برای چیدن یک خانه گرم لازم داری، از برندهایی که سال‌ها در آشپزخانه‌های ایرانی جا افتاده‌اند.</p>
                @if ($categories->isNotEmpty())
                    <a href="{{ $categories->first()->url() }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-bold text-glaze-deep hover:bg-mist">
                        دیدن {{ $categories->first()->name }}
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                    </a>
                @endif
            </div>
        @endif
    </section>

    {{-- Signature: the category tile wall --}}
    @if ($categories->isNotEmpty())
        <section class="mx-auto mt-14 max-w-7xl px-4" aria-labelledby="cats-title">
            <h2 id="cats-title" class="font-display text-3xl">دسته‌بندی‌ها</h2>
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($categories as $category)
                    <x-category.tile :category="$category" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($products->isNotEmpty())
        <section class="mx-auto mt-16 max-w-7xl px-4" aria-labelledby="new-title">
            <div class="flex items-end justify-between">
                <h2 id="new-title" class="font-display text-3xl">تازه‌های فروشگاه</h2>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-4">
                @foreach ($products as $product)
                    <x-product.card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($brands->isNotEmpty())
        <section class="mx-auto mt-16 max-w-7xl px-4" aria-labelledby="brands-title">
            <h2 id="brands-title" class="font-display text-3xl">برندها</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($brands as $brand)
                    <a href="{{ $brand->url() }}" class="group flex items-center justify-between rounded-[var(--radius-tile)] border border-line bg-white px-6 py-6 transition hover:border-glaze">
                        <span>
                            <span class="block font-display text-3xl group-hover:text-glaze">{{ $brand->name_fa }}</span>
                            <span class="text-sm text-ink-soft" dir="ltr">{{ $brand->name_en }}</span>
                        </span>
                        <span class="text-sm text-ink-soft">{{ \App\Core\Support\PersianText::toPersianDigits($brand->products_count) }} محصول</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($posts->isNotEmpty())
        <section class="mx-auto mt-16 max-w-7xl px-4" aria-labelledby="posts-title">
            <div class="flex items-end justify-between">
                <h2 id="posts-title" class="font-display text-3xl">از مجله</h2>
                <a href="{{ route('content.blog') }}" class="text-sm font-medium text-glaze">همه مطالب</a>
            </div>
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post.card :post="$post" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
