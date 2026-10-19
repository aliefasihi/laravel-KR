{{-- Block: ways to reach us | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $cards = [];
    if ($data['show_store'] ?? true) {
        $cards = array_filter([
            $store['phone'] ? ['label' => 'تلفن فروشگاه', 'value' => PersianText::toPersianDigits($store['phone']), 'url' => 'tel:'.$store['phone'], 'ltr' => true] : null,
            $store['mobile'] ? ['label' => 'تلفن همراه', 'value' => PersianText::toPersianDigits($store['mobile']), 'url' => 'tel:'.$store['mobile'], 'ltr' => true] : null,
            $store['whatsapp'] ? ['label' => 'واتس‌اپ', 'value' => 'گفتگو در واتس‌اپ', 'url' => 'https://wa.me/'.$store['whatsapp'], 'ltr' => false] : null,
            $store['email'] ? ['label' => 'ایمیل', 'value' => $store['email'], 'url' => 'mailto:'.$store['email'], 'ltr' => true] : null,
        ]);
    }
    foreach (array_values($data['items'] ?? []) as $item) {
        $cards[] = ['label' => $item['label'] ?? '', 'value' => $item['value'] ?? '', 'url' => $item['url'] ?? null, 'ltr' => (bool) preg_match('/^[\x20-\x7E۰-۹]+$/u', (string) ($item['value'] ?? ''))];
    }
@endphp
<section class="space-y-4">
    @if (! empty($data['title']))<h2 class="px-2 text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($cards as $card)
            @php($tag = $card['url'] ? 'a' : 'div')
            <{{ $tag }} @if ($card['url']) href="{{ $card['url'] }}" @if (str_starts_with($card['url'], 'http')) target="_blank" rel="noopener" @endif @endif
               class="glass group flex items-center justify-between gap-4 rounded-[var(--radius-card)] p-6 transition hover:-translate-y-0.5 hover:bg-surface/85">
                <span class="text-sm text-muted">{{ $card['label'] }}</span>
                <span class="text-lg font-black text-navy group-hover:text-rose" @if ($card['ltr']) dir="ltr" @endif>{{ $card['value'] }}</span>
            </{{ $tag }}>
        @endforeach
    </div>
</section>
