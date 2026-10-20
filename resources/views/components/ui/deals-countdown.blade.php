{{-- Hours : minutes : seconds left until a sale ends (offers bands) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['until'])
<div x-data="countdown('{{ $until->toIso8601String() }}')" {{ $attributes->class(['flex gap-1.5']) }} aria-label="زمان باقی‌مانده تا پایان تخفیف">
    @foreach (['h' => 'ساعت', 'm' => 'دقیقه', 's' => 'ثانیه'] as $key => $label)
        <span class="w-14 rounded-2xl bg-white/25 py-2 text-center">
            <span class="block text-xl leading-none font-black" x-text="parts.{{ $key }}">۰۰</span>
            <span class="mt-1 block text-[10px] text-white/80">{{ $label }}</span>
        </span>
    @endforeach
</div>
