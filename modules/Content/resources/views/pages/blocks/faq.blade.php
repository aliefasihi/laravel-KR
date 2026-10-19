{{-- Block: FAQ (also FAQPage schema) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="glass rounded-[var(--radius-glass)] px-6 py-8 sm:px-12">
    @if (! empty($data['title']))<h2 class="mb-4 text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
    <div class="divide-y divide-line">
        @foreach (array_values($data['items'] ?? []) as $item)
            <details class="group py-4" @if ($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-navy">
                    {{ $item['question'] ?? '' }}
                    <span class="text-xl text-rose transition group-open:rotate-45" aria-hidden="true">+</span>
                </summary>
                <p class="mt-3 leading-8 whitespace-pre-line text-muted">{{ $item['answer'] ?? '' }}</p>
            </details>
        @endforeach
    </div>
</section>
