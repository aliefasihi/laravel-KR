{{-- 8-point star (khatam) — brand mark and tile motif | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['ring' => true])
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'aria-hidden' => 'true']) }}>
    <polygon points="12,1 15.22,4.22 19.78,4.22 19.78,8.78 23,12 19.78,15.22 19.78,19.78 15.22,19.78 12,23 8.78,19.78 4.22,19.78 4.22,15.22 1,12 4.22,8.78 4.22,4.22 8.78,4.22"
             stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
    @if ($ring)
        <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.4"/>
    @endif
</svg>
