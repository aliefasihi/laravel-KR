{{-- Site footer | نویسنده: علی فصیحی (Ali Fasihi) --}}
<footer class="mt-20">
    <div class="kashi-band" aria-hidden="true"></div>
    <div class="bg-ink text-porcelain/80">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2 text-porcelain">
                    <x-khatam class="size-7 text-mist" />
                    <span class="font-display text-2xl">خانه رمانتیک</span>
                </div>
                <p class="mt-4 max-w-md text-sm leading-7">لوازم و اکسسوری خانه و آشپزخانه از برندهای گلدکیش و ریتون، با ارسال به سراسر ایران.</p>
            </div>
            <div>
                <h2 class="mb-3 font-bold text-porcelain">خرید</h2>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('checkout.cart') }}" class="hover:text-white">سبد خرید</a></li>
                    <li><a href="{{ route('catalog.search') }}" class="hover:text-white">جستجوی محصولات</a></li>
                </ul>
            </div>
            <div>
                <h2 class="mb-3 font-bold text-porcelain">خواندنی</h2>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('content.blog') }}" class="hover:text-white">مجله خانه رمانتیک</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 py-5 text-center text-xs text-porcelain/50">
            © {{ \App\Core\Support\PersianText::toPersianDigits(\App\Core\Support\Jalali::year()) }} خانه رمانتیک
        </div>
    </div>
</footer>
