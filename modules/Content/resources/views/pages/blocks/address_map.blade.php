{{-- Block: address, map, opening hours | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $lat = $data['lat'] ?? null;
    $lng = $data['lng'] ?? null;
    $hasPoint = is_numeric($lat) && is_numeric($lng);
    $hours = array_values($data['hours'] ?? []);
    $address = (string) ($data['address'] ?? config('khaneh.store.address'));
    $routes = $hasPoint
        ? ['نشان' => "https://neshan.org/maps/@{$lat},{$lng},16z", 'بلد' => "https://balad.ir/location?latitude={$lat}&longitude={$lng}&zoom=16", 'گوگل‌مپ' => "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}"]
        : ['مسیریابی در گوگل‌مپ' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address)];
@endphp
<section class="glass-navy relative isolate grid gap-8 overflow-hidden rounded-[var(--radius-glass)] p-6 sm:p-10 @if (! empty($data['map_embed'])) lg:grid-cols-2 @endif">
    <x-site.lotus :stroke="0.6" class="pointer-events-none absolute -bottom-16 -end-12 -z-10 size-72 text-caramel/15" />
    <div class="flex gap-4">
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/10 text-caramel">
            {!! rescue(fn () => svg('heroicon-o-map-pin', 'size-6')->toHtml(), '', false) !!}
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-caramel">{{ $data['title'] ?? 'فروشگاه حضوری' }}</p>
            <p class="mt-2 text-lg leading-9 whitespace-pre-line">{{ $address }}</p>
            @if ($hours)
                <dl class="mt-5 grid gap-2 text-sm">
                    @foreach ($hours as $row)
                        <div class="flex justify-between gap-4 border-b border-white/10 pb-2"><dt class="text-white/70">{{ $row['days'] ?? '' }}</dt><dd class="font-bold">{{ $row['time'] ?? '' }}</dd></div>
                    @endforeach
                </dl>
            @endif
            <div class="mt-6 flex flex-wrap gap-2 text-sm">
                @foreach ($routes as $label => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-2 font-bold transition hover:bg-white/20">
                        {!! rescue(fn () => svg('heroicon-o-arrow-top-right-on-square', 'size-4')->toHtml(), '', false) !!}{{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @if (! empty($data['map_embed']))
        <iframe src="{{ $data['map_embed'] }}" title="نقشه‌ی فروشگاه" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                class="aspect-[4/3] w-full rounded-[1.2rem] border-0 bg-white/10"></iframe>
    @endif
</section>
