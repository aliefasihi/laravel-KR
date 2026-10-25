{{-- Site footer — «بازار»: service icons row, link columns with the newsletter, contact strip, trust badge | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $navCategories = ($navCategories ?? collect())->take(6);
    $services = [
        ['ضمانت اصالت کالا', 'M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Zm-3 9 2 2 4-4'],
        ['ارسال به سراسر کشور', 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z'],
        ['پرداخت امن بانکی', 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm0 3h18M7 15h4'],
        ['بسته‌بندی ضدضربه', 'M21 8 12 3 3 8v8l9 5 9-5V8ZM3 8l9 5 9-5M12 13v8'],
        ['مشاوره پیش از خرید', 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z'],
    ];
@endphp

<footer class="mt-16 border-t border-line bg-surface">
    <div class="mx-auto max-w-[88rem] px-3 sm:px-5">
        <ul class="grid grid-cols-2 gap-y-6 border-b border-line py-8 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($services as [$title, $icon])
                <li class="flex flex-col items-center gap-2 text-center text-xs font-bold text-muted">
                    <svg class="size-9 text-deep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    {{ $title }}
                </li>
            @endforeach
        </ul>

        <div class="grid gap-10 py-10 lg:grid-cols-[1fr_1fr_1fr_1.6fr]">
            <div>
                <h2 class="text-sm font-black text-navy">با {{ $store['name'] }}</h2>
                <ul class="mt-4 space-y-3 text-[13px] text-muted">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-deep">درباره ما</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-deep">تماس با ما</a></li>
                    <li><a href="{{ route('content.blog') }}" class="hover:text-deep">مجله</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-black text-navy">دسته‌بندی‌ها</h2>
                <ul class="mt-4 space-y-3 text-[13px] text-muted">
                    @foreach ($navCategories as $category)
                        <li><a href="{{ $category->url() }}" class="hover:text-deep">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-black text-navy">خدمات مشتریان</h2>
                <ul class="mt-4 space-y-3 text-[13px] text-muted">
                    <li><a href="{{ route('catalog.shop') }}" class="hover:text-deep">همه‌ی کالاها</a></li>
                    <li><a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="hover:text-deep">کالاهای تخفیف‌دار</a></li>
                    <li><a href="{{ route('checkout.cart') }}" class="hover:text-deep">سبد خرید</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-black text-navy">از تخفیف‌ها باخبر شوید</h2>
                @if (Route::has('newsletter.subscribe') && class_exists(\Livewire\Livewire::class))
                    <div class="mt-4"><livewire:content.newsletter /></div>
                @endif
                <div class="mt-6 flex text-muted"><x-site.socials class="hover:!text-deep" /></div>
            </div>
        </div>

        <div class="grid items-center gap-6 rounded-2xl bg-cream p-5 sm:p-6 lg:grid-cols-[1fr_auto]">
            <div>
                <x-site.logo large />
                <p class="mt-3 max-w-3xl text-[13px] leading-7 text-muted">{{ $store['about'] }}</p>
                <p class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-[13px] text-muted">
                    <span>تلفن: <a href="tel:{{ $store['phone'] }}" class="font-bold text-navy" dir="ltr">{{ PersianText::toPersianDigits($store['phone']) }}</a></span>
                    <span>ایمیل: <a href="mailto:{{ $store['email'] }}" class="font-latin text-navy">{{ $store['email'] }}</a></span>
                    <span>{{ $store['address'] }}</span>
                </p>
            </div>
            @if ($store['enamad_url'])
                <a href="{{ $store['enamad_url'] }}" target="_blank" rel="noopener" referrerpolicy="origin"
                   class="flex size-28 flex-col items-center justify-center gap-1 rounded-xl border border-line bg-surface text-center text-[11px] font-bold text-navy">
                    <svg class="size-9 text-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    نماد اعتماد
                </a>
            @endif
        </div>
        <p class="py-6 text-center text-xs text-subtle">© {{ PersianText::toPersianDigits(Jalali::year()) }} — کلیه‌ی حقوق این سایت متعلق به فروشگاه {{ $store['name'] }} است.</p>
    </div>
</footer>
