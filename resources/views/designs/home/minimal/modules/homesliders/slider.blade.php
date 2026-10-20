{{-- Home product slider — «مینیمال»: plain heading with a rule, round outline arrows, frameless cards | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $id = 'home-slider-'.$slider->id;
    $rail = sprintf("loopRail(%d, %s)", max(2, $slider->interval) * 1000, $slider->autoplay ? 'true' : 'false');
    $button = 'grid size-10 place-items-center rounded-full border border-line text-navy transition hover:border-navy';
@endphp

<section aria-labelledby="{{ $id }}" class="space-y-8" x-data="{{ $rail }}" aria-roledescription="carousel">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-4">
        <div>
            @if ($slider->style === 'band' && $dealsEnd)
                <p class="mb-1 text-xs font-bold tracking-wide text-rose">تا پایان تخفیف</p>
                <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex items-baseline gap-1 font-black text-navy" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                    <h2 id="{{ $id }}" class="me-3 text-2xl">{{ trim($slider->accent.' '.$slider->title) }}</h2>
                    <span class="tabular-nums" x-text="parts.h">۰۰</span>:<span x-text="parts.m">۰۰</span>:<span x-text="parts.s">۰۰</span>
                </div>
            @else
                @if ($slider->ghost)
                    <p class="mb-1 font-latin text-xs font-bold tracking-[0.2em] text-caramel-600 uppercase" dir="ltr">{{ $slider->ghost }}</p>
                @endif
                <h2 id="{{ $id }}" class="text-2xl font-black text-navy">{{ trim($slider->accent.' '.$slider->title) }}</h2>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="step(-1)" class="{{ $button }}" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
            <button type="button" @click="step(1)" class="{{ $button }}" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
            <a href="{{ $slider->url() }}" class="ms-2 text-sm font-bold text-navy hover:text-rose">مشاهده همه</a>
        </div>
    </div>
    <div @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
        <ul class="rail !gap-4" x-ref="track">
            @foreach ($products as $product)
                <li class="w-[46%] shrink-0 sm:w-[31%] md:w-[23.5%] lg:w-[18.6%]" aria-roledescription="slide">
                    <x-product.card :product="$product" />
                </li>
            @endforeach
        </ul>
    </div>
</section>
