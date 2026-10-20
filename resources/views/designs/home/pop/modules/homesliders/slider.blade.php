{{-- Home product slider — «پاپ»: highlighted Lalezar heading and chunky framed arrows; the "band" style is a
     tomato-red block with the countdown in yellow boxes | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $id = 'home-slider-'.$slider->id;
    $rail = sprintf("loopRail(%d, %s)", max(2, $slider->interval) * 1000, $slider->autoplay ? 'true' : 'false');
    $band = $slider->style === 'band';
    $button = 'grid size-11 place-items-center rounded-xl border-2 border-[var(--pop-ink)] bg-surface text-navy shadow-[3px_3px_0_var(--pop-ink)] transition hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-caramel hover:text-[#141414]';
@endphp

<section aria-labelledby="{{ $id }}" x-data="{{ $rail }}" aria-roledescription="carousel"
         @class(['space-y-6', 'pop-frame rounded-2xl bg-rose p-5 sm:p-8' => $band])>
    <div class="flex flex-wrap items-center justify-between gap-4">
        @if ($band)
            <div class="flex flex-wrap items-center gap-4">
                <h2 id="{{ $id }}" class="font-display text-4xl text-white">{{ trim($slider->accent.' '.$slider->title) }}</h2>
                @if ($dealsEnd)
                    <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex gap-2 font-display text-2xl text-[#141414]" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                        @foreach (['h', 'm', 's'] as $key)
                            <span class="grid min-w-12 place-items-center rounded-xl border-2 border-[#141414] bg-caramel py-1" x-text="parts.{{ $key }}">۰۰</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <x-pop.heading :id="$id" :title="trim($slider->accent.' '.$slider->title)" :tag="$slider->ghost" />
        @endif
        <div class="flex items-center gap-2">
            <button type="button" @click="step(-1)" class="{{ $button }}" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
            <button type="button" @click="step(1)" class="{{ $button }}" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
            <a href="{{ $slider->url() }}" class="ms-1 rounded-full border-2 border-[var(--pop-ink)] bg-caramel px-4 py-2 text-sm font-black text-[#141414]">همه</a>
        </div>
    </div>
    <div @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
        <ul class="rail !gap-4 !pt-1 !pb-3" x-ref="track">
            @foreach ($products as $product)
                <li class="w-[62%] shrink-0 sm:w-[32%] md:w-[24%] lg:w-[19.2%]" aria-roledescription="slide">
                    <x-product.card :product="$product" />
                </li>
            @endforeach
        </ul>
    </div>
</section>
