{{-- Site footer: trust strip, newsletter, links, contact, enamad | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $navCategories = ($navCategories ?? collect())->take(6);
    $features = [
        ['title' => 'ضمانت اصالت کالا', 'text' => 'همه‌ی محصولات اورجینال و از نمایندگی رسمی', 'icon' => 'M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Zm-3 9 2 2 4-4'],
        ['title' => 'ارسال به سراسر ایران', 'text' => 'بسته‌بندی ایمن، مخصوص ظروف شکستنی', 'icon' => 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z'],
        ['title' => 'پرداخت امن', 'text' => 'درگاه بانکی معتبر و نماد اعتماد', 'icon' => 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm0 3h18M7 15h4'],
        ['title' => 'مشاوره قبل از خرید', 'text' => 'در واتس‌اپ و تلفن همراه شما هستیم', 'icon' => 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12ZM9 11h.01M12 11h.01M15 11h.01'],
    ];
@endphp

<footer class="mt-24 px-3 sm:px-4">
    <div class="mx-auto max-w-7xl space-y-4">
        {{-- Trust strip --}}
        <ul class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($features as $feature)
                <li class="glass flex items-center gap-3 rounded-[var(--radius-card)] p-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-rose-100 to-cream text-rose">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $feature['icon'] }}"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-black text-navy">{{ $feature['title'] }}</span>
                        <span class="mt-0.5 hidden text-xs text-muted sm:block">{{ $feature['text'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>

        {{-- Newsletter --}}
        <section class="relative overflow-hidden rounded-[var(--radius-glass)] bg-gradient-to-l from-cream via-[#fde8ee] to-caramel-100 p-6 shadow-[var(--shadow-glass)] sm:p-10" aria-labelledby="newsletter-title">
            <div class="blob -top-16 -start-10 size-56 text-rose/15" aria-hidden="true"></div>
            <div class="blob -bottom-20 end-10 size-64 text-caramel/25" aria-hidden="true"></div>
            <div class="relative grid items-center gap-6 lg:grid-cols-[1fr_28rem]">
                <div>
                    <h2 id="newsletter-title" class="text-2xl font-black text-navy">عضویت در باشگاه خبری خانه رمانتیک</h2>
                    <p class="mt-2 leading-7 text-muted">اولین نفری باشید که از تخفیف‌ها، کالکشن‌های تازه و ایده‌های چیدمان خبردار می‌شود.</p>
                </div>
                <form method="post" action="{{ route('newsletter.subscribe') }}" class="glass-strong flex items-center gap-2 rounded-full p-1.5 ps-5">
                    @csrf
                    <label for="newsletter-contact" class="sr-only">ایمیل یا شماره موبایل</label>
                    <input id="newsletter-contact" name="contact" type="text" inputmode="email" required value="{{ old('contact') }}"
                           placeholder="ایمیل یا شماره موبایل" class="min-w-0 flex-1 bg-transparent text-sm text-ink placeholder:text-subtle focus:outline-none">
                    <button class="btn-rose !px-5 !py-2.5">عضویت</button>
                </form>
                {{-- $errors is absent on error pages rendered outside the web middleware --}}
                @if (isset($errors) && $errors->newsletter->has('contact'))
                    <p class="text-sm text-rose lg:col-start-2" role="alert">{{ $errors->newsletter->first('contact') }}</p>
                @endif
            </div>
        </section>

        {{-- Main footer --}}
        <div class="glass-navy relative overflow-hidden rounded-[var(--radius-glass)]">
            <div class="blob -top-24 end-1/4 size-72 text-rose/20" aria-hidden="true"></div>
            <div class="relative grid gap-10 px-6 py-10 sm:px-10 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
                <div>
                    <x-site.logo light />
                    <p class="mt-5 text-sm leading-7 text-white/70">{{ $store['about'] }}</p>
                    <div class="mt-5 flex text-white/80"><x-site.socials class="hover:!bg-white/10 hover:!text-caramel" /></div>
                </div>

                <div>
                    <h2 class="mb-4 text-sm font-black text-caramel">دسترسی سریع</h2>
                    <ul class="space-y-2.5 text-sm text-white/75">
                        <li><a href="{{ route('catalog.shop') }}" class="hover:text-white">فروشگاه</a></li>
                        <li><a href="{{ route('content.blog') }}" class="hover:text-white">مجله خانه رمانتیک</a></li>
                        <li><a href="{{ route('pages.about') }}" class="hover:text-white">درباره ما</a></li>
                        <li><a href="{{ route('pages.contact') }}" class="hover:text-white">تماس با ما</a></li>
                        <li><a href="{{ route('checkout.cart') }}" class="hover:text-white">سبد خرید</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="mb-4 text-sm font-black text-caramel">دسته‌بندی‌ها</h2>
                    <ul class="space-y-2.5 text-sm text-white/75">
                        @foreach ($navCategories as $category)
                            <li><a href="{{ $category->url() }}" class="hover:text-white">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="mb-4 text-sm font-black text-caramel">تماس با ما</h2>
                    <ul class="space-y-3 text-sm text-white/75">
                        <li class="flex gap-2"><span class="text-white/45">تلفن:</span><a href="tel:{{ $store['phone'] }}" class="font-bold text-white" dir="ltr">{{ PersianText::toPersianDigits($store['phone']) }}</a></li>
                        <li class="flex gap-2"><span class="text-white/45">همراه:</span><a href="tel:{{ $store['mobile'] }}" class="font-bold text-white" dir="ltr">{{ PersianText::toPersianDigits($store['mobile']) }}</a></li>
                        <li class="flex gap-2"><span class="text-white/45">ایمیل:</span><a href="mailto:{{ $store['email'] }}" class="font-latin text-white">{{ $store['email'] }}</a></li>
                        <li class="leading-7"><span class="text-white/45">فروشگاه:</span> {{ $store['address'] }}</li>
                    </ul>
                    @if ($store['enamad_url'])
                        <a href="{{ $store['enamad_url'] }}" target="_blank" rel="noopener" referrerpolicy="origin"
                           class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-3 text-xs font-bold text-navy transition hover:scale-[1.02]">
                            <svg class="size-6 text-[#1a9e4a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            نماد اعتماد الکترونیکی
                        </a>
                    @endif
                </div>
            </div>
            <div class="relative border-t border-white/10 px-6 py-5 text-center text-xs text-white/50 sm:px-10">
                © {{ PersianText::toPersianDigits(Jalali::year()) }} — تمامی حقوق این وب‌سایت متعلق به فروشگاه {{ $store['name'] }} است.
            </div>
        </div>
    </div>
</footer>
