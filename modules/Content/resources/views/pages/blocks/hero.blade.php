{{-- Block: hero | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($image = ! empty($data['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($data['image']) : null)
<section @class(['glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-12 sm:px-14 sm:py-16', 'grid items-center gap-10 lg:grid-cols-[1fr_22rem]' => $image])>
    <div class="blob-duo -top-24 -end-16 -z-10 size-80" aria-hidden="true"></div>
    @unless ($image)
        <x-site.lotus :stroke="0.6" class="pointer-events-none absolute -bottom-20 -end-14 -z-10 size-80 text-caramel/25 sm:size-96" />
    @endunless
    <div class="max-w-3xl">
        @if (! empty($data['eyebrow']))
            <p class="inline-flex items-center gap-2 rounded-full bg-caramel/15 px-3.5 py-1 text-[11px] font-bold tracking-[0.25em] text-caramel-600 uppercase" @if (preg_match('/^[\x20-\x7E]+$/', $data['eyebrow'])) dir="ltr" @endif>
                <span class="size-1.5 rounded-full bg-caramel" aria-hidden="true"></span>{{ $data['eyebrow'] }}
            </p>
        @endif
        @if ($index === 0)
            <h1 class="mt-4 text-3xl leading-tight font-black text-navy sm:text-[2.6rem]">{{ $data['title'] ?? $page->title }}</h1>
        @else
            <h2 class="mt-4 text-2xl font-black text-navy sm:text-3xl">{{ $data['title'] ?? '' }}</h2>
        @endif
        <span class="mt-5 block h-1 w-16 rounded-full bg-gradient-to-l from-caramel to-rose" aria-hidden="true"></span>
        @if (! empty($data['text']))
            <p class="mt-6 text-base leading-9 whitespace-pre-line text-muted sm:text-lg">{{ $data['text'] }}</p>
        @endif
    </div>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $data['title'] ?? '' }}" class="aspect-square w-full rounded-[1.4rem] object-cover shadow-[var(--shadow-glass)]" @if ($index > 0) loading="lazy" @endif>
    @endif
</section>
