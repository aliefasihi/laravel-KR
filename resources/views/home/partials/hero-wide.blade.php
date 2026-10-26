{{-- Full-width hero banner (like Digikala): edge to edge, no frame | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Desktop height follows the screen: banner, category circles and half of the «شگفت‌انگیز» band share the
     first view (100svh minus header, circles and half the band ≈ 36rem), between 15rem and 25rem.
     Banners are cropped to the middle (object-cover): keep text in the central strip; desktop art 1920×400. --}}
<section aria-label="پیشنهادهای ویژه" class="pt-3 sm:pt-4">
    <div x-data="slider({{ $heroBanners->count() }})" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()"
         @touchstart.passive="tx = $event.changedTouches[0].clientX"
         @touchend="(d => Math.abs(d) > 40 && (d > 0 ? next() : prev()))($event.changedTouches[0].clientX - tx)"
         class="group relative overflow-hidden bg-surface" aria-roledescription="carousel">
        @foreach ($heroBanners as $banner)
            <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
               x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0"
               class="block" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first" sizes="100vw"
                                class="block aspect-square w-full object-cover md:aspect-[12/5] lg:aspect-auto lg:h-[clamp(15rem,calc(100svh_-_36rem),25rem)]" />
            </a>
        @endforeach

        @if ($heroBanners->count() > 1)
            <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5 sm:bottom-4">
                @foreach ($heroBanners as $banner)
                    <button type="button" @click="go({{ $loop->index }})" class="h-1.5 rounded-full shadow-sm transition-all"
                            :class="active === {{ $loop->index }} ? 'w-5 bg-white' : 'w-1.5 bg-white/55 hover:bg-white/80'"
                            aria-label="اسلاید {{ $loop->iteration }}"></button>
                @endforeach
            </div>
            <div class="absolute bottom-3 start-4 hidden gap-2 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100 sm:bottom-4 sm:start-6 lg:flex">
                <button type="button" @click="prev()" class="grid size-9 place-items-center rounded-full bg-white/90 text-navy shadow-md transition hover:bg-white" aria-label="قبلی">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg>
                </button>
                <button type="button" @click="next()" class="grid size-9 place-items-center rounded-full bg-white/90 text-navy shadow-md transition hover:bg-white" aria-label="بعدی">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                </button>
            </div>
        @endif
    </div>
</section>
