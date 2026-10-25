{{-- Editable page (About /aboutus, Contact /contact-us, Privacy /privacy-policy, Returns /return-policy) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    $blocks = $page->blockList();
    $hero = collect($blocks)->firstWhere('type', 'hero')['data'] ?? [];
    $description = $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($hero['text'] ?? config('khaneh.store.about'))), 158);
    $faqs = collect($blocks)->where('type', 'faq')->flatMap(fn ($b) => $b['data']['items'] ?? [])->filter(fn ($i) => ! empty($i['question']));
@endphp

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $description)
@section('canonical', $page->url())

@if ($faqs->isNotEmpty())
    @push('head')
        @php
            $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['answer'] ?? '']])->values()->all(),
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endpush
@endif

@section('content')
    <div class="mx-auto max-w-5xl space-y-8 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="[$page->title => null]" />

        @foreach ($blocks as $index => $block)
            @if (view()->exists('content::pages.blocks.'.$block['type']))
                @include('content::pages.blocks.'.$block['type'], ['data' => (array) ($block['data'] ?? []), 'index' => $index, 'page' => $page])
            @endif
        @endforeach
    </div>
@endsection
