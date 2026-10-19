{{-- Block: gallery | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($images = array_values(array_filter((array) ($data['images'] ?? []))))
<section class="space-y-4" x-data="{ open: null }">
    @if (! empty($data['title']))
        <h2 class="px-2 text-2xl font-black text-navy">{{ $data['title'] }}</h2>
    @endif
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($images as $i => $path)
            @php($url = \Illuminate\Support\Facades\Storage::disk('public')->url($path))
            <button type="button" @click="open = @js($url)" class="glass overflow-hidden rounded-[var(--radius-card)] p-1.5">
                <img src="{{ $url }}" alt="{{ $data['title'] ?? 'تصویر' }} {{ $i + 1 }}" loading="lazy" class="aspect-square w-full rounded-[1rem] object-cover transition hover:scale-[1.02]">
            </button>
        @endforeach
    </div>
    <div x-show="open" x-cloak x-transition.opacity @click="open = null" @keydown.escape.window="open = null"
         class="fixed inset-0 z-[80] grid place-items-center bg-navy/80 p-4">
        <img :src="open" alt="" class="max-h-[90dvh] max-w-full rounded-2xl">
    </div>
</section>
