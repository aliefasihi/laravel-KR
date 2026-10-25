{{-- Block: call to action | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="relative isolate overflow-hidden rounded-[var(--radius-glass)] bg-gradient-to-l from-cream via-rose-100 to-caramel-100 px-6 py-10 shadow-[var(--shadow-glass)] sm:px-12 sm:py-12">
    <x-site.lotus :stroke="0.6" class="pointer-events-none absolute -bottom-10 -start-8 -z-10 size-48 text-rose/15" />
    <div class="relative flex flex-wrap items-center justify-between gap-6">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-black text-[#23284f]">{{ $data['title'] ?? '' }}</h2>
            @if (! empty($data['text']))<p class="mt-2 leading-8 text-[#4a4f6a]">{{ $data['text'] }}</p>@endif
        </div>
        @if (! empty($data['button_url']))
            <a href="{{ $data['button_url'] }}" class="btn-rose">{{ $data['button_label'] ?: 'ادامه' }}</a>
        @endif
    </div>
</section>
