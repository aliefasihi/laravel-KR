{{-- Announcement marquee (an occasion — یلدا، نوروز… — brings its own text, colours and sign) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $occ = config('khaneh.occasion');
    $text = $occ['promo_text'] ?? config('khaneh.promo');
    $sign = $occ ? \Modules\Occasions\Support\Art::svg($occ['emblem']) : null;
@endphp
@if (filled($text))
    <div @class(['relative overflow-hidden text-white', 'occ-promo' => $occ, 'bg-gradient-to-l from-rose via-[#d9577f] to-caramel' => ! $occ]) role="region" aria-label="اطلاعیه">
        <div class="flex w-max animate-marquee gap-12 py-2 text-[13px] font-bold hover:[animation-play-state:paused] motion-reduce:w-full motion-reduce:justify-center">
            @foreach (range(1, 8) as $i)
                <span @class(['inline-flex items-center gap-2', 'motion-reduce:hidden' => $i > 1]) @if ($i > 1) aria-hidden="true" @endif>
                    @if ($sign)
                        <span class="inline-block size-5 [&>svg]:size-full" aria-hidden="true">{!! $sign !!}</span>
                    @else
                        <svg class="size-3.5 opacity-80" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.2 5.3 5.8.4-4.4 3.8 1.4 5.6L10 13.6l-5 3 1.4-5.6L2 7.2l5.8-.4z"/></svg>
                    @endif
                    {{ $text }}
                </span>
            @endforeach
        </div>
    </div>
@endif
