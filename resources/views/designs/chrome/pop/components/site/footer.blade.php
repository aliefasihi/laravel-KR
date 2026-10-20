{{-- Site footer — «پاپ»: coloured promise stickers, a loud newsletter block, a framed black footer | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $navCategories = ($navCategories ?? collect())->take(6);
    $promises = [
        ['اصل اصل', 'همه از نمایندگی رسمی', 'bg-caramel text-[#141414]', '-rotate-2'],
        ['ارسال به کل ایران', 'بسته‌بندی ضدضربه', 'bg-pop-teal text-white', 'rotate-1'],
        ['پرداخت امن', 'درگاه بانکی و اینماد', 'bg-pop-pink text-[#141414]', '-rotate-1'],
        ['مشاوره رایگان', 'واتس‌اپ و تلفن', 'bg-pop-lilac text-[#141414]', 'rotate-2'],
    ];
@endphp

<footer class="mt-24 px-3 pb-3 sm:px-5">
    <div class="mx-auto max-w-7xl space-y-6">
        <ul class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($promises as [$title, $text, $color, $tilt])
                <li class="pop-frame rounded-2xl px-5 py-4 {{ $color }} {{ $tilt }} transition hover:rotate-0">
                    <span class="block font-display text-xl">{{ $title }}</span>
                    <span class="mt-0.5 block text-xs font-bold opacity-80">{{ $text }}</span>
                </li>
            @endforeach
        </ul>

        <section class="pop-frame grid items-center gap-6 rounded-2xl bg-rose p-6 text-white sm:p-10 lg:grid-cols-[1fr_28rem]" aria-labelledby="newsletter-title">
            <div>
                <h2 id="newsletter-title" class="font-display text-4xl leading-tight sm:text-5xl">تخفیف‌ها رو از دست نده!</h2>
                <p class="mt-3 font-bold text-white/85">عضو باشگاه خبری {{ $store['name'] }} شو؛ اول از همه خبردار می‌شی.</p>
            </div>
            @if (Route::has('newsletter.subscribe') && class_exists(\Livewire\Livewire::class))
                <div class="rounded-2xl border-2 border-[#141414] bg-white p-2 text-navy"><livewire:content.newsletter /></div>
            @endif
        </section>

        <div class="pop-frame overflow-hidden rounded-2xl bg-[#141414] text-[#fff4df]">
            <div class="grid gap-10 px-6 py-10 sm:px-10 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
                <div>
                    <x-site.logo light />
                    <p class="mt-5 text-sm leading-7 text-white/70">{{ $store['about'] }}</p>
                    <div class="mt-4 flex text-white"><x-site.socials class="hover:!bg-caramel hover:!text-[#141414]" /></div>
                </div>
                <div>
                    <h2 class="font-display text-xl text-caramel">دسته‌ها</h2>
                    <ul class="mt-4 space-y-2 text-sm font-bold text-white/75">
                        @foreach ($navCategories as $category)
                            <li><a href="{{ $category->url() }}" class="hover:text-caramel">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h2 class="font-display text-xl text-caramel">لینک‌ها</h2>
                    <ul class="mt-4 space-y-2 text-sm font-bold text-white/75">
                        <li><a href="{{ route('catalog.shop') }}" class="hover:text-caramel">فروشگاه</a></li>
                        <li><a href="{{ route('content.blog') }}" class="hover:text-caramel">مجله</a></li>
                        <li><a href="{{ route('pages.about') }}" class="hover:text-caramel">درباره ما</a></li>
                        <li><a href="{{ route('pages.contact') }}" class="hover:text-caramel">تماس با ما</a></li>
                        <li><a href="{{ route('checkout.cart') }}" class="hover:text-caramel">سبد خرید</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="font-display text-xl text-caramel">سلام کنید!</h2>
                    <ul class="mt-4 space-y-2.5 text-sm text-white/75">
                        <li><a href="tel:{{ $store['phone'] }}" class="font-black text-white" dir="ltr">{{ PersianText::toPersianDigits($store['phone']) }}</a></li>
                        <li><a href="tel:{{ $store['mobile'] }}" class="font-black text-white" dir="ltr">{{ PersianText::toPersianDigits($store['mobile']) }}</a></li>
                        <li><a href="mailto:{{ $store['email'] }}" class="font-latin">{{ $store['email'] }}</a></li>
                        <li class="leading-7">{{ $store['address'] }}</li>
                    </ul>
                    @if ($store['enamad_url'])
                        <a href="{{ $store['enamad_url'] }}" target="_blank" rel="noopener" referrerpolicy="origin"
                           class="mt-5 inline-flex items-center gap-2 rounded-xl border-2 border-white bg-white px-4 py-2.5 text-xs font-black text-[#141414] shadow-[3px_3px_0_var(--color-caramel)]">
                            نماد اعتماد الکترونیکی
                        </a>
                    @endif
                </div>
            </div>
            <div class="border-t-2 border-white/15 px-6 py-4 text-center text-xs font-bold text-white/50">
                © {{ PersianText::toPersianDigits(Jalali::year()) }} {{ $store['name'] }} — ساخته‌شده با کلی ذوق ✺
            </div>
        </div>
    </div>
</footer>
