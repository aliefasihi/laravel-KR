{{-- Block: contact form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section id="contact-form" class="glass grid gap-8 rounded-[var(--radius-glass)] px-6 py-8 sm:px-12 sm:py-11 lg:grid-cols-[17rem_1fr]">
    <div>
        <span class="grid size-12 place-items-center rounded-2xl bg-gradient-to-br from-rose-100 to-caramel/25 text-rose">
            {!! rescue(fn () => svg('heroicon-o-chat-bubble-left-right', 'size-6')->toHtml(), '', false) !!}
        </span>
        @if (! empty($data['title']))<h2 class="mt-4 text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
        @if (! empty($data['text']))<p class="mt-2 leading-8 text-muted">{{ $data['text'] }}</p>@endif
    </div>
    <div>
        <livewire:content.contact-form :subjects="array_values((array) ($data['subjects'] ?? []))" :key="'contact-form-'.$index" />
    </div>
</section>
