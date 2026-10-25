{{-- Site footer — «طاق»: an arch-topped newsletter panel rising into a deep olive footer | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $navCategories = ($navCategories ?? collect())->take(6);
    $promises = [
        ['ضمانت اصالت', 'M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Zm-3 9 2 2 4-4'],
        ['ارسال به سراسر ایران', 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z'],
        ['پرداخت امن', 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm0 3h18M7 15h4'],
        ['مشاوره پیش از خرید', 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z'],
    ];
@endphp

<footer class="mt-28">
    <ul class="mx-auto grid max-w-7xl grid-cols-2 gap-y-8 px-4 pb-20 sm:px-6 lg:grid-cols-4">
        @foreach ($promises as [$title, $icon])
            <li class="flex flex-col items-center gap-3 text-center">
                <span class="arch-frame grid h-16 w-14 place-items-center bg-cream text-deep">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                </span>
                <span class="text-sm font-medium text-navy">{{ $title }}</span>
            </li>
        @endforeach
    </ul>

    <div class="bg-[#2f3a25] text-[#efe9dc]">
        <section class="mx-auto -translate-y-14 px-4 sm:px-6" aria-labelledby="newsletter-title">
            <div class="mx-auto max-w-3xl rounded-t-[12rem] bg-[#3f4a32] px-6 pt-16 pb-10 text-center sm:px-16">
                <h2 id="newsletter-title" class="text-3xl font-light sm:text-4xl">نامه‌ای از {{ $store['name'] }}</h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-white/70">ایده‌های چیدمان و خبر کالکشن‌های تازه، ماهی یک‌بار؛ بی‌مزاحمت.</p>
                @if (Route::has('newsletter.subscribe') && class_exists(\Livewire\Livewire::class))
                    <div class="mx-auto mt-6 max-w-md"><livewire:content.newsletter /></div>
                @endif
            </div>
        </section>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-12 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
            <div>
                <x-site.logo light large />
                <p class="mt-5 text-sm leading-7 text-white/65">{{ $store['about'] }}</p>
                <div class="mt-4 flex text-white/75"><x-site.socials class="hover:!bg-white/10 hover:!text-white" /></div>
            </div>
            <div>
                <h2 class="text-sm font-medium text-[#c9a66b]">دسته‌بندی‌ها</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    @foreach ($navCategories as $category)
                        <li><a href="{{ $category->url() }}" class="hover:text-white">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-medium text-[#c9a66b]">دسترسی سریع</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li><a href="{{ route('catalog.shop') }}" class="hover:text-white">فروشگاه</a></li>
                    <li><a href="{{ route('content.blog') }}" class="hover:text-white">مجله</a></li>
                    <li><a href="{{ route('pages.about') }}" class="hover:text-white">درباره ما</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-white">تماس با ما</a></li>
                    <li><a href="{{ route('pages.returns') }}" class="hover:text-white">شرایط مرجوعی</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-white">حریم خصوصی</a></li>
                    <li><a href="{{ route('checkout.cart') }}" class="hover:text-white">سبد خرید</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-medium text-[#c9a66b]">در تماس باشیم</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                    <li><a href="tel:{{ $store['phone'] }}" class="font-medium text-white" dir="ltr">{{ PersianText::toPersianDigits($store['phone']) }}</a></li>
                    <li><a href="mailto:{{ $store['email'] }}" class="font-latin">{{ $store['email'] }}</a></li>
                    <li class="leading-7">{{ $store['address'] }}</li>
                </ul>
                @if ($store['enamad_url'])
                    <a href="{{ $store['enamad_url'] }}" target="_blank" rel="noopener" referrerpolicy="origin"
                       class="mt-5 inline-flex items-center gap-2 rounded-full bg-[#efe9dc] px-4 py-2.5 text-xs font-medium text-[#2f3a25]">نماد اعتماد الکترونیکی</a>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10 px-4 py-5 text-center text-xs text-white/45">
            © {{ PersianText::toPersianDigits(Jalali::year()) }} — همه‌ی حقوق برای {{ $store['name'] }} محفوظ است.
        </div>
    </div>
</footer>
