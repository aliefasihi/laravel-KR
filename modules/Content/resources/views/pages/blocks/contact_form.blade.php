{{-- Block: contact form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section id="contact-form" class="glass rounded-[var(--radius-glass)] px-6 py-8 sm:px-12 sm:py-10">
    @if (! empty($data['title']))<h2 class="text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
    @if (! empty($data['text']))<p class="mt-2 leading-8 text-muted">{{ $data['text'] }}</p>@endif
    <div class="mt-6">
        <livewire:content.contact-form :subjects="array_values((array) ($data['subjects'] ?? []))" :key="'contact-form-'.$index" />
    </div>
</section>
