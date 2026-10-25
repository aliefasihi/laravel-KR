{{-- Block: ways to reach us | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $cards = [];
    if ($data['show_store'] ?? true) {
        $cards = array_values(array_filter([
            $store['phone'] ? ['icon' => 'phone', 'label' => 'تلفن فروشگاه', 'value' => PersianText::toPersianDigits($store['phone']), 'url' => 'tel:'.$store['phone'], 'ltr' => true] : null,
            $store['mobile'] ? ['icon' => 'device-phone-mobile', 'label' => 'تلفن همراه', 'value' => PersianText::toPersianDigits($store['mobile']), 'url' => 'tel:'.$store['mobile'], 'ltr' => true] : null,
            ($store['whatsapp'] ?? null) ? ['icon' => 'chat-bubble-oval-left-ellipsis', 'label' => 'واتس‌اپ', 'value' => 'گفتگو در واتس‌اپ', 'url' => 'https://wa.me/'.$store['whatsapp'], 'ltr' => false] : null,
            $store['email'] ? ['icon' => 'envelope', 'label' => 'ایمیل', 'value' => $store['email'], 'url' => 'mailto:'.$store['email'], 'ltr' => true] : null,
            ($store['instagram'] ?? null) ? ['icon' => 'camera', 'label' => 'اینستاگرام', 'value' => 'صفحه‌ی اینستاگرام', 'url' => $store['instagram'], 'ltr' => false] : null,
            ($store['telegram'] ?? null) ? ['icon' => 'paper-airplane', 'label' => 'تلگرام', 'value' => 'کانال تلگرام', 'url' => $store['telegram'], 'ltr' => false] : null,
        ]));
    }
    foreach (array_values($data['items'] ?? []) as $item) {
        $cards[] = ['icon' => 'link', 'label' => $item['label'] ?? '', 'value' => $item['value'] ?? '', 'url' => $item['url'] ?? null, 'ltr' => (bool) preg_match('/^[\x20-\x7E۰-۹]+$/u', (string) ($item['value'] ?? ''))];
    }
@endphp
<section class="space-y-5">
    @if (! empty($data['title']))<h2 class="px-2 text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
    <div @class(['grid gap-4 sm:grid-cols-2', 'lg:grid-cols-3' => count($cards) % 3 === 0])>
        @foreach ($cards as $card)
            @php($tag = $card['url'] ? 'a' : 'div')
            <{{ $tag }} @if ($card['url']) href="{{ $card['url'] }}" @if (str_starts_with($card['url'], 'http')) target="_blank" rel="noopener" @endif @endif
               class="glass group flex items-center gap-4 rounded-[var(--radius-card)] p-5 transition duration-300 hover:-translate-y-1">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-rose-100 to-caramel/25 text-rose transition group-hover:scale-105">
                    {!! rescue(fn () => svg('heroicon-o-'.$card['icon'], 'size-6')->toHtml(), '', false) !!}
                </span>
                <span class="min-w-0">
                    <span class="block text-xs text-muted">{{ $card['label'] }}</span>
                    <span @class(["mt-1 block font-black [overflow-wrap:anywhere] text-navy group-hover:text-rose", "text-[15px]" => mb_strlen($card['value']) > 18, "text-base sm:text-lg" => mb_strlen($card['value']) <= 18]) @if ($card['ltr']) dir="ltr" style="text-align: right" @endif>{{ $card['value'] }}</span>
                </span>
            </{{ $tag }}>
        @endforeach
    </div>
</section>
