{{-- Announcement — «شب طلایی»: one quiet centred line in gold | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($text = config('khaneh.promo'))
@if (filled($text))
    <div class="border-b border-line bg-black px-4 py-2 text-center text-[12px] tracking-[0.08em] text-caramel" role="region" aria-label="اطلاعیه">
        <span class="mx-2 inline-block size-1 rotate-45 bg-caramel align-middle" aria-hidden="true"></span>{{ $text }}<span class="mx-2 inline-block size-1 rotate-45 bg-caramel align-middle" aria-hidden="true"></span>
    </div>
@endif
