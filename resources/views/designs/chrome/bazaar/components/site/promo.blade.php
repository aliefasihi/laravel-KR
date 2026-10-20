{{-- Announcement — «بازار»: a slim blue bar with a link to the offers | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($text = config('khaneh.promo'))
@if (filled($text))
    <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="block bg-deep px-4 py-2 text-center text-[13px] font-bold text-white hover:bg-deep-700" role="region" aria-label="اطلاعیه">
        {{ $text }} <span class="ms-2 rounded-md bg-white/20 px-2 py-0.5 text-xs">مشاهده</span>
    </a>
@endif
