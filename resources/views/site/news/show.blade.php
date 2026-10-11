@extends('layouts.site')

@section('title', ($post->seo['title'] ?? null) ?: $post->title.' · '.config('app.name'))
@section('description', ($post->seo['description'] ?? null) ?: $post->excerpt)

@section('content')
    <article class="block">
<div class="container">
        <p><a href="{{ route('news.index') }}">← All news</a></p>
        <h1>{{ $post->title }}</h1>
        <p class="meta"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->timezone('Asia/Dhaka')->format('j F Y') }}</time> · {{ $post->type->label() }}</p>
        @if ($post->featured?->getFirstMedia('file'))
            <img src="{{ $post->featured->getFirstMediaUrl('file', 'large') ?: $post->featured->getFirstMediaUrl('file') }}" alt="{{ $post->featured->altAttribute() }}" fetchpriority="high">
            @if ($post->featured->credit)<p class="meta">Photo: {{ $post->featured->credit }}</p>@endif
        @endif
        {{-- Sanitised with the HTMLPurifier allow-list on save. --}}
        <div class="prose">{!! $post->body !!}</div>
    </div>
</article>
@endsection
