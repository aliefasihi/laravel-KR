{{-- Block: address, map, opening hours | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $lat = $data['lat'] ?? null;
    $lng = $data['lng'] ?? null;
    $hasPoint = is_numeric($lat) && is_numeric($lng);
    $hours = array_values($data['hours'] ?? []);
@endphp
<section class="glass-navy grid gap-6 overflow-hidden rounded-[var(--radius-glass)] p-6 sm:p-8 @if (! empty($data['map_embed'])) lg:grid-cols-2 @endif">
    <div>
        <p class="text-sm font-bold text-caramel">{{ $data['title'] ?? 'آدرس فروشگاه حضوری' }}</p>
        <p class="mt-2 text-lg leading-9 whitespace-pre-line">{{ $data['address'] ?? config('khaneh.store.address') }}</p>
        @if ($hours)
            <dl class="mt-5 grid gap-2 text-sm">
                @foreach ($hours as $row)
                    <div class="flex justify-between gap-4 border-b border-white/10 pb-2"><dt class="text-white/70">{{ $row['days'] ?? '' }}</dt><dd class="font-bold">{{ $row['time'] ?? '' }}</dd></div>
                @endforeach
            </dl>
        @endif
        @if ($hasPoint)
            <div class="mt-5 flex flex-wrap gap-2 text-sm">
                <a href="https://neshan.org/maps/{{ "@".$lat }},{{ $lng }},16z" target="_blank" rel="noopener" class="rounded-full bg-white/10 px-4 py-2 font-bold hover:bg-white/20">مسیریابی با نشان</a>
                <a href="https://balad.ir/location?latitude={{ $lat }}&longitude={{ $lng }}&zoom=16" target="_blank" rel="noopener" class="rounded-full bg-white/10 px-4 py-2 font-bold hover:bg-white/20">بلد</a>
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $lat }},{{ $lng }}" target="_blank" rel="noopener" class="rounded-full bg-white/10 px-4 py-2 font-bold hover:bg-white/20">گوگل‌مپ</a>
            </div>
        @endif
    </div>
    @if (! empty($data['map_embed']))
        <iframe src="{{ $data['map_embed'] }}" title="نقشه‌ی فروشگاه" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                class="aspect-[4/3] w-full rounded-[1.2rem] border-0 bg-white/10"></iframe>
    @endif
</section>
