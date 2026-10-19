{{-- Block: hero | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($image = ! empty($data['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($data['image']) : null)
<section @class(['glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-12 sm:px-14', 'grid items-center gap-8 lg:grid-cols-[1fr_22rem]' => $image])>
    <div class="blob-duo -top-24 -end-16 -z-10 size-80" aria-hidden="true"></div>
    <div>
        @if (! empty($data['eyebrow']))
            <p class="text-xs font-bold tracking-[0.3em] text-caramel-600 uppercase" @if (preg_match('/^[\x20-\x7E]+$/', $data['eyebrow'])) dir="ltr" style="text-align: right" @endif>{{ $data['eyebrow'] }}</p>
        @endif
        @if ($index === 0)
            <h1 class="mt-3 text-3xl font-black text-navy sm:text-4xl">{{ $data['title'] ?? $page->title }}</h1>
        @else
            <h2 class="mt-3 text-2xl font-black text-navy sm:text-3xl">{{ $data['title'] ?? '' }}</h2>
        @endif
        @if (! empty($data['text']))
            <p class="mt-6 max-w-3xl text-lg leading-9 whitespace-pre-line text-muted">{{ $data['text'] }}</p>
        @endif
    </div>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $data['title'] ?? '' }}" class="aspect-square w-full rounded-[1.4rem] object-cover" @if ($index > 0) loading="lazy" @endif>
    @endif
</section>
