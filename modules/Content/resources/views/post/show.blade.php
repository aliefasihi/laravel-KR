{{-- Content: blog post | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description ?: $post->excerpt)
@section('canonical', $post->url())

@section('content')
    <article>
        <h1>{{ $post->title }}</h1>
        {!! $post->body_html !!}
    </article>
@endsection
