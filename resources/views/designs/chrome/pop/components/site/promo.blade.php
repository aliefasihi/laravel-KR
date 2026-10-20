{{-- Announcement — «پاپ»: yellow ticker with black text and stars | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($text = config('khaneh.promo'))
@if (filled($text))
    <div class="overflow-hidden border-b-2 border-[#141414] bg-caramel text-[#141414]" role="region" aria-label="اطلاعیه">
        <div class="flex w-max animate-marquee gap-10 py-1.5 text-[13px] font-black hover:[animation-play-state:paused] motion-reduce:w-full motion-reduce:justify-center">
            @foreach (range(1, 8) as $i)
                <span @class(['inline-flex items-center gap-3', 'motion-reduce:hidden' => $i > 1]) @if ($i > 1) aria-hidden="true" @endif>
                    <span class="text-rose" aria-hidden="true">✺</span>{{ $text }}
                </span>
            @endforeach
        </div>
    </div>
@endif
