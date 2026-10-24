{{-- BlogPosting structured data (both post designs) | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- built in a php block: Blade reads the schema key inside an echo as its own "context" directive --}}
@php
$schema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post->title,
    'datePublished' => $post->published_at?->toIso8601String(),
    'dateModified' => $post->updated_at?->toIso8601String(),
    'image' => $post->media->first()?->absoluteUrl(),
    'mainEntityOfPage' => $post->url(),
    'publisher' => ['@type' => 'Organization', 'name' => config('khaneh.store.name')],
]);
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
