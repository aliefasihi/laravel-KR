{{-- Home product slider — «شب طلایی»: centred Naskh heading with a gold rule, thin square arrows;
     the "band" style gets a black panel with a gold countdown | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $id = 'home-slider-'.$slider->id;
    $rail = sprintf("loopRail(%d, %s)", max(2, $slider->interval) * 1000, $slider->autoplay ? 'true' : 'false');
    $button = 'grid size-11 place-items-center border border-line text-navy transition hover:border-caramel hover:text-caramel';
    $band = $slider->style === 'band';
@endphp

<section aria-labelledby="{{ $id }}" x-data="{{ $rail }}" aria-roledescription="carousel"
         @class(['space-y-12', 'border border-caramel/25 bg-black px-5 py-12 sm:px-10' => $band])>
    <div class="flex flex-col items-center gap-4 text-center">
        <p class="text-[11px] tracking-[0.3em] text-caramel">{{ $band ? 'تا پایان تخفیف' : ($slider->ghost ?: $slider->accent) }}</p>
        <h2 id="{{ $id }}" class="font-display text-[2.1rem] leading-tight font-semibold text-navy sm:text-[2.6rem]">{{ trim($slider->accent.' '.$slider->title) }}</h2>
        @if ($band && $dealsEnd)
            <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex items-center gap-3 font-display text-3xl text-caramel" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                <span x-text="parts.h">۰۰</span><span class="text-caramel/40">:</span><span x-text="parts.m">۰۰</span><span class="text-caramel/40">:</span><span x-text="parts.s">۰۰</span>
            </div>
        @else
            <div class="noir-rule w-48" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
        @endif
    </div>
    <div @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
        <ul class="rail !gap-6" x-ref="track">
            @foreach ($products as $product)
                <li class="w-[70%] shrink-0 sm:w-[40%] md:w-[30%] lg:w-[23%]" aria-roledescription="slide">
                    <x-product.card :product="$product" />
                </li>
            @endforeach
        </ul>
    </div>
    <div class="flex items-center justify-center gap-3">
        <button type="button" @click="step(-1)" class="{{ $button }}" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
        <a href="{{ $slider->url() }}" class="px-4 text-[13px] tracking-wide text-caramel hover:underline">مشاهده همه</a>
        <button type="button" @click="step(1)" class="{{ $button }}" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
    </div>
</section>
