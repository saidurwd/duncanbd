@extends('layouts.site')

@section('title', ($post->seo['title'] ?? null) ?: $post->title.' · '.config('app.name'))
@section('description', ($post->seo['description'] ?? null) ?: $post->excerpt)

@section('og_type', 'article')
@if ($post->featured?->getFirstMedia('file'))
    @section('og_image', $post->featured->getFirstMediaUrl('file', 'large') ?: $post->featured->getFirstMediaUrl('file'))
@endif
@section('og_extra')
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
@endsection

@push('jsonld')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $post->title,
        'description' => ($post->seo['description'] ?? null) ?: $post->excerpt,
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'mainEntityOfPage' => url($post->url()),
        'image' => $post->featured?->getFirstMedia('file') ? [$post->featured->getFirstMediaUrl('file', 'large') ?: $post->featured->getFirstMediaUrl('file')] : null,
        'author' => ['@type' => 'Organization', 'name' => $site['name']],
        'publisher' => array_filter(['@type' => 'Organization', 'name' => $site['name'], 'logo' => $site['logo'] ? ['@type' => 'ImageObject', 'url' => url($site['logo']['path'])] : null]),
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

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
