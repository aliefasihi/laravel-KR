{{-- Occasion: drifting pieces (resources/js/occasion.js), the visitor's on/off button, admin preview strip | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if ($occ = config('khaneh.occasion'))
    @php
        $bits = array_map(fn ($bit) => [
            'src' => $bit[0] === 'spark' ? null : \Modules\Occasions\Support\Art::dataUri(\Modules\Occasions\Support\Art::svg($bit[0])),
            'weight' => $bit[1],
        ], $occ['bits']);
    @endphp
    <script type="application/json" id="occ-config">@json(['key' => $occ['key'], 'motion' => $occ['motion'], 'bits' => $bits])</script>
    <button type="button" id="occ-toggle" class="occ-toggle" aria-pressed="true" title="خاموش کردن افکت‌های مناسبت" aria-label="روشن/خاموش کردن افکت‌های {{ $occ['label'] }}">
        {!! \Modules\Occasions\Support\Art::svg($occ['emblem']) !!}
        <span class="occ-toggle-x" aria-hidden="true">✕</span>
    </button>
    @if ($occ['previewing'])
        <div class="occ-preview" role="status">
            پیش‌نمایش مناسبت: <b>{{ $occ['label'] }}</b> (فقط شما می‌بینید)
            <a href="{{ request()->fullUrlWithQuery(['occasion' => 'live']) }}">خروج</a>
        </div>
    @endif
@endif
