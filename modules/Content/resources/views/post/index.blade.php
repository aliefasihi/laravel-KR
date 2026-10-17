{{-- Content: blog index | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'مجله خانه رمانتیک')
@section('canonical', route('content.blog'))

@section('content')
    <h1>مجله خانه رمانتیک</h1>

    @foreach ($posts as $post)
        <article>
            <h2><a href="{{ $post->url() }}">{{ $post->title }}</a></h2>
            <p>{{ $post->excerpt }}</p>
        </article>
    @endforeach

    {{ $posts->links() }}
@endsection
