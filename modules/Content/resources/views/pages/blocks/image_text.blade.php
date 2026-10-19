{{-- Block: image + text | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($image = ! empty($data['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($data['image']) : null)
<section class="glass grid items-center gap-8 overflow-hidden rounded-[var(--radius-glass)] p-4 sm:p-6 lg:grid-cols-2">
    @if ($image)
        <img src="{{ $image }}" alt="{{ $data['title'] ?? '' }}" loading="lazy"
             @class(['aspect-[4/3] w-full rounded-[1.4rem] object-cover', 'lg:order-last' => ($data['image_side'] ?? 'start') === 'end'])>
    @endif
    <div class="px-2 py-4 sm:px-6">
        @if (! empty($data['title']))<h2 class="text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
        @if (! empty($data['content']))<div class="prose-fa mt-4">{!! $data['content'] !!}</div>@endif
    </div>
</section>
