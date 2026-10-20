{{-- Announcement — «طاق»: a quiet olive line | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($text = config('khaneh.promo'))
@if (filled($text))
    <div class="bg-deep px-4 py-2 text-center text-[13px] text-white/90" role="region" aria-label="اطلاعیه">{{ $text }}</div>
@endif
