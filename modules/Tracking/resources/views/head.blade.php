{{-- Site ownership tags (Search Console, Bing…): plain meta tags, nothing downloaded | نویسنده: علی فصیحی (Ali Fasihi) --}}
@foreach (\Modules\Tracking\Support\Tracking::verificationTags() as $name => $content)
    <meta name="{{ $name }}" content="{{ $content }}">
@endforeach
