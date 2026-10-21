{{-- Occasion colours and cursors (html[data-occasion]) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if ($occ = config('khaneh.occasion'))
    <style>
        html[data-occasion] {
            --color-rose: {{ $occ['accent'][0] }};
            --color-rose-600: {{ $occ['accent'][1] }};
            --color-rose-100: {{ $occ['accent'][2] }};
            --occ-promo: {{ $occ['promo_bg'] }};
            --occ-amazing: {{ $occ['amazing_bg'] }};
        }
        @media (pointer: fine) {
            html[data-occasion]:not(.occ-off) body { cursor: {!! \Modules\Occasions\Support\Art::cursor($occ['emblem']) !!}; }
            html[data-occasion]:not(.occ-off) :is(a, button, [role="button"], summary, select, label[for]) { cursor: {!! \Modules\Occasions\Support\Art::pointer($occ['emblem']) !!}; }
        }
    </style>
    <script>try { if (localStorage.getItem('kr-occ-off') === @json($occ['key'])) document.documentElement.classList.add('occ-off'); } catch (e) {}</script>
@endif
