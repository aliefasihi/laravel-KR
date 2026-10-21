{{-- Home product slider: infinite, auto-playing rail | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $id = 'home-slider-'.$slider->id;
    $rail = sprintf("loopRail(%d, %s)", max(2, $slider->interval) * 1000, $slider->autoplay ? 'true' : 'false');
    $arrows = fn (string $class) => <<<HTML
        <button type="button" @click="step(-1)" class="{$class}" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
        <button type="button" @click="step(1)" class="{$class}" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
    HTML;
@endphp

@if ($slider->style === 'band')
    @php
        $occ = config('khaneh.occasion');   // یلدا، نوروز…: the band takes the occasion's colours, sign and words
        $amazingTitle = $occ && str_contains($slider->accent.' '.$slider->title, 'شگفت');
    @endphp
    <section @class(['relative overflow-hidden rounded-[2rem] bg-gradient-to-l from-caramel via-[#e89a7f] to-rose p-4 shadow-[0_30px_60px_-30px_rgb(204_51_102/0.6)] sm:p-6', 'occ-amazing' => $occ])
             aria-labelledby="{{ $id }}" x-data="{{ $rail }}" aria-roledescription="carousel">
        @if ($occ)
            <span class="occ-hero" aria-hidden="true">{!! \Modules\Occasions\Support\Art::svg($occ['hero']) !!}</span>
        @endif
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(40rem_20rem_at_80%_0%,rgb(255_255_255/0.35),transparent)]" aria-hidden="true"></div>
        <div class="relative grid items-center gap-5 lg:grid-cols-[13rem_1fr]">
            <div class="flex flex-col items-start gap-4 text-white lg:items-center lg:text-center">
                <h2 id="{{ $id }}" class="text-3xl leading-tight font-black drop-shadow-sm">
                    @if ($occ)<span class="occ-title-emblem" aria-hidden="true">{!! \Modules\Occasions\Support\Art::svg($occ['emblem']) !!}</span> @endif
                    @if ($amazingTitle)
                        {{ $occ['amazing_title'] }}
                    @else
                        @if ($slider->accent){{ $slider->accent }}<br class="max-lg:hidden"> @endif{{ $slider->title }}
                    @endif
                </h2>
                @if ($dealsEnd)
                    <x-ui.deals-countdown :until="$dealsEnd" />
                @endif
                <a href="{{ $slider->url() }}" class="btn !bg-surface !px-5 !py-2.5 text-navy hover:!bg-cream">مشاهده همه</a>
                <div class="hidden gap-2 lg:flex">{!! $arrows('grid size-10 place-items-center rounded-full bg-white/25 text-white transition hover:bg-white/40') !!}</div>
            </div>
            <div class="relative min-w-0" @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
                <ul class="rail !mx-0 !px-0 !pb-1" x-ref="track">
                    @foreach ($products as $product)
                        <li class="w-[46%] shrink-0 sm:w-[30%] lg:w-[23%]" aria-roledescription="slide">
                            <x-product.card :product="$product" class="!bg-surface/85 h-full" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@else
    <section aria-labelledby="{{ $id }}" class="space-y-7" x-data="{{ $rail }}" aria-roledescription="carousel">
        <div class="flex items-end justify-between gap-4">
            <x-section-title :id="$id" :accent="$slider->accent" :title="$slider->title" :ghost="$slider->ghost" class="flex-1" />
            <div class="flex shrink-0 items-center gap-2">
                <div class="hidden gap-1.5 sm:flex">{!! $arrows('btn-icon glass size-10 text-navy hover:text-rose') !!}</div>
                <a href="{{ $slider->url() }}" class="glass group inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold text-navy transition hover:text-rose">
                    مشاهده همه
                    <svg class="size-4 transition group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                </a>
            </div>
        </div>
        <div @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
            <ul class="rail" x-ref="track">
                @foreach ($products as $product)
                    <li class="w-[46%] shrink-0 sm:w-[31%] md:w-[23.5%] lg:w-[18.6%]" aria-roledescription="slide">
                        <x-product.card :product="$product" class="h-full" />
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
