{{-- Site footer — «شب طلایی»: a large statement, newsletter on a gold rule, quiet link columns | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $navCategories = ($navCategories ?? collect())->take(6);
@endphp

<footer class="mt-32 border-t border-line bg-black text-navy">
    <div class="mx-auto max-w-[90rem] px-5 sm:px-8">
        {{-- Statement + newsletter --}}
        <section class="grid items-end gap-10 border-b border-line py-16 lg:grid-cols-[1.2fr_1fr]" aria-labelledby="newsletter-title">
            <div>
                <p class="text-[11px] tracking-[0.3em] text-caramel">باشگاه خبری</p>
                <h2 id="newsletter-title" class="mt-4 font-display text-4xl leading-[1.35] font-semibold sm:text-5xl">زیبایی، هر روز<br>سر سفره‌ی شما</h2>
                <p class="mt-4 max-w-md text-sm leading-7 text-muted">از کالکشن‌های تازه، تخفیف‌های محدود و ایده‌های چیدمان {{ $store['name'] }} زودتر از همه باخبر شوید.</p>
            </div>
            @if (Route::has('newsletter.subscribe') && class_exists(\Livewire\Livewire::class))
                <div class="lg:pb-2"><livewire:content.newsletter /></div>
            @endif
        </section>

        <div class="grid gap-12 py-14 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.3fr]">
            <div>
                <x-site.logo light />
                <p class="mt-6 max-w-sm text-sm leading-7 text-muted">{{ $store['about'] }}</p>
                <div class="mt-5 flex text-muted"><x-site.socials class="hover:!text-caramel" /></div>
            </div>
            <div>
                <h2 class="text-[11px] tracking-[0.3em] text-caramel">مجموعه‌ها</h2>
                <ul class="mt-5 space-y-3 text-sm text-muted">
                    @foreach ($navCategories as $category)
                        <li><a href="{{ $category->url() }}" class="transition hover:text-navy">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="text-[11px] tracking-[0.3em] text-caramel">راهنما</h2>
                <ul class="mt-5 space-y-3 text-sm text-muted">
                    <li><a href="{{ route('catalog.shop') }}" class="transition hover:text-navy">فروشگاه</a></li>
                    <li><a href="{{ route('content.blog') }}" class="transition hover:text-navy">مجله</a></li>
                    <li><a href="{{ route('pages.about') }}" class="transition hover:text-navy">درباره ما</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="transition hover:text-navy">تماس با ما</a></li>
                    <li><a href="{{ route('checkout.cart') }}" class="transition hover:text-navy">سبد خرید</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-[11px] tracking-[0.3em] text-caramel">نشانی</h2>
                <ul class="mt-5 space-y-3 text-sm text-muted">
                    <li class="leading-7">{{ $store['address'] }}</li>
                    <li><a href="tel:{{ $store['phone'] }}" class="text-navy" dir="ltr">{{ PersianText::toPersianDigits($store['phone']) }}</a></li>
                    <li><a href="mailto:{{ $store['email'] }}" class="font-latin">{{ $store['email'] }}</a></li>
                </ul>
                @if ($store['enamad_url'])
                    <a href="{{ $store['enamad_url'] }}" target="_blank" rel="noopener" referrerpolicy="origin"
                       class="mt-6 inline-flex items-center gap-2 border border-caramel/40 px-4 py-2.5 text-xs text-caramel transition hover:border-caramel">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        نماد اعتماد الکترونیکی
                    </a>
                @endif
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-line py-6 text-xs text-subtle sm:flex-row">
            <span>© {{ PersianText::toPersianDigits(Jalali::year()) }} {{ $store['name'] }} — همه‌ی حقوق محفوظ است.</span>
            <span class="font-latin tracking-[0.3em] uppercase" dir="ltr">{{ $store['name_en'] }}</span>
        </div>
    </div>
</footer>
