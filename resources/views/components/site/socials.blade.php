{{-- Social links from config('khaneh.store') | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $store = config('khaneh.store');
    $items = array_filter([
        'instagram' => $store['instagram'] ? ['url' => $store['instagram'], 'label' => 'اینستاگرام', 'path' => 'M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2Zm0 2A3.8 3.8 0 0 0 4 7.8v8.4A3.8 3.8 0 0 0 7.8 20h8.4a3.8 3.8 0 0 0 3.8-3.8V7.8A3.8 3.8 0 0 0 16.2 4H7.8Zm4.2 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Zm5-3.3a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2Z'] : null,
        'telegram' => $store['telegram'] ? ['url' => $store['telegram'], 'label' => 'تلگرام', 'path' => 'M21.4 3.6 2.9 10.8c-1.3.5-1.2 1.2-.2 1.5l4.7 1.5 1.8 5.6c.2.6.4.8.8.8.4 0 .6-.2.9-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.8c.3-1.3-.5-1.9-1.4-1.5ZM9.6 14.4l-.3 3.5-1.5-4.7 10.4-6.6-8.6 7.8Z'] : null,
        'whatsapp' => $store['whatsapp'] ? ['url' => 'https://wa.me/'.$store['whatsapp'], 'label' => 'واتس‌اپ', 'path' => 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2c0 1.3.9 2.5 1.1 2.7.1.2 1.8 2.8 4.5 4 1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z'] : null,
    ]);
@endphp

@foreach ($items as $item)
    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" {{ $attributes->class(['btn-icon size-9 hover:text-rose']) }} aria-label="{{ $item['label'] }}">
        <svg class="size-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $item['path'] }}"/></svg>
    </a>
@endforeach
