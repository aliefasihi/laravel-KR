{{-- Product page <head>: title, description, canonical, og:image and the Product JSON-LD — shared by every
     product page design (variables from Modules\Catalog\Support\ProductPage::data) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@section('title', $product->meta_title ?: $product->name)
@section('meta_description', (string) ($product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 155)))
@section('canonical', $product->url())
@if ($images->isNotEmpty())
    @section('og_image', $images->first()->absoluteUrl())
@endif

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
