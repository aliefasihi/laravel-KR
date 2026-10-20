{{-- Home product slider — «بازار»: a white panel with a compact rail; the "band" style is the blue deals strip
     with a countdown box beside the cards | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $id = 'home-slider-'.$slider->id;
    $rail = sprintf("loopRail(%d, %s)", max(2, $slider->interval) * 1000, $slider->autoplay ? 'true' : 'false');
    $arrow = 'absolute top-1/2 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-full border border-line bg-surface text-navy shadow-md transition hover:text-deep';
@endphp

@if ($slider->style === 'band')
    <section aria-labelledby="{{ $id }}" x-data="{{ $rail }}" aria-roledescription="carousel" class="rounded-2xl bg-deep p-3 sm:p-4">
        <div class="flex gap-3">
            <div class="hidden w-44 shrink-0 flex-col items-center justify-center gap-4 text-center text-white md:flex">
                <h2 id="{{ $id }}" class="text-2xl leading-tight font-black">{{ $slider->accent }}<br>{{ $slider->title }}</h2>
                @if ($dealsEnd)
                    <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex gap-1 text-sm font-black text-deep" dir="ltr" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                        <span class="rounded-md bg-white px-1.5 py-1" x-text="parts.h">۰۰</span><span class="text-white">:</span><span class="rounded-md bg-white px-1.5 py-1" x-text="parts.m">۰۰</span><span class="text-white">:</span><span class="rounded-md bg-white px-1.5 py-1" x-text="parts.s">۰۰</span>
                    </div>
                @endif
                <a href="{{ $slider->url() }}" class="text-sm font-bold text-white/85 hover:text-white">مشاهده همه ←</a>
            </div>
            <div class="relative min-w-0 flex-1" @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
                <h2 class="mb-3 text-lg font-black text-white md:hidden">{{ trim($slider->accent.' '.$slider->title) }}</h2>
                <ul class="rail !mx-0 !gap-1 !px-0 !pb-0" x-ref="track">
                    @foreach ($products as $product)
                        <li class="w-[44%] shrink-0 sm:w-[28%] lg:w-[19.6%]" aria-roledescription="slide">
                            <x-product.card :product="$product" class="h-full !rounded-lg" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@else
    <section aria-labelledby="{{ $id }}" x-data="{{ $rail }}" aria-roledescription="carousel" class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
        <x-bazaar.heading :id="$id" :title="trim($slider->accent.' '.$slider->title)" :link="$slider->url()" />
        <div class="relative" @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
            <button type="button" @click="step(-1)" class="{{ $arrow }} -start-2 hidden lg:grid" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
            <ul class="rail !mx-0 !gap-0 !px-0 !pb-0" x-ref="track">
                @foreach ($products as $product)
                    <li class="w-[44%] shrink-0 border-e border-line last:border-e-0 sm:w-[28%] lg:w-[16.66%]" aria-roledescription="slide">
                        <x-product.card :product="$product" class="h-full !border-0 !shadow-none" />
                    </li>
                @endforeach
            </ul>
            <button type="button" @click="step(1)" class="{{ $arrow }} -end-2 hidden lg:grid" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
        </div>
    </section>
@endif
