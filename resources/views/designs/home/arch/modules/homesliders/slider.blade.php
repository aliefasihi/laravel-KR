{{-- Home product slider — «طاق»: light heading with a sage eyebrow, soft round arrows; the "band" style sits on
     a cream panel with the countdown in circles | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $id = 'home-slider-'.$slider->id;
    $rail = sprintf("loopRail(%d, %s)", max(2, $slider->interval) * 1000, $slider->autoplay ? 'true' : 'false');
    $band = $slider->style === 'band';
    $button = 'grid size-11 place-items-center rounded-full bg-cream text-navy transition hover:bg-deep hover:text-white';
@endphp

<section aria-labelledby="{{ $id }}" x-data="{{ $rail }}" aria-roledescription="carousel"
         @class(['space-y-10', 'rounded-[2.5rem] bg-cream px-5 py-10 sm:px-10' => $band])>
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-xs font-medium text-sage">{{ $band ? 'تا پایان تخفیف' : ($slider->ghost ?: 'پیشنهاد ما') }}</p>
            <h2 id="{{ $id }}" class="mt-2 text-3xl leading-tight font-light text-navy sm:text-[2.6rem]">{{ trim($slider->accent.' '.$slider->title) }}</h2>
            @if ($band && $dealsEnd)
                <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="mt-4 flex gap-2" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                    @foreach (['h' => 'ساعت', 'm' => 'دقیقه', 's' => 'ثانیه'] as $key => $label)
                        <span class="grid size-14 place-content-center rounded-full bg-surface text-center">
                            <span class="text-lg leading-none font-bold text-navy" x-text="parts.{{ $key }}">۰۰</span>
                            <span class="text-[9px] text-muted">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $slider->url() }}" class="me-2 border-b border-navy/30 pb-0.5 text-sm text-navy hover:border-rose hover:text-rose">مشاهده همه</a>
            <button type="button" @click="step(-1)" class="{{ $button }} {{ $band ? '!bg-surface' : '' }}" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
            <button type="button" @click="step(1)" class="{{ $button }} {{ $band ? '!bg-surface' : '' }}" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
        </div>
    </div>
    <div @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
        <ul class="rail !gap-6" x-ref="track">
            @foreach ($products as $product)
                <li class="w-[58%] shrink-0 sm:w-[32%] md:w-[24%] lg:w-[23%]" aria-roledescription="slide">
                    <x-product.card :product="$product" />
                </li>
            @endforeach
        </ul>
    </div>
</section>
