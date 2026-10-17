@extends('layouts.site')

@section('title', ($page->seo['title'] ?? null) ?: $page->title.' · '.$site['name'])
@if (! empty($page->seo['description']) || ! empty($autoDescription))
    @section('description', $page->seo['description'] ?? $autoDescription)
@endif
@if (! empty($ogImage))
    @section('og_image', $ogImage)
@endif

@if ($preview)
    @section('robots', 'noindex, nofollow')
@endif

@if ($page->path === 'home')
    @push('jsonld')
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $site['name'],
            'url' => url('/'),
            'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush
@endif

@unless (collect($blocks)->contains(fn ($b) => $b['type'] === 'hero'))
    @section('crumbs_in_hero', '1')
@endunless

@section('content')
    @if ($preview)
        <div class="preview-banner" role="status">Preview — this page is not published.</div>
    @endif
    @unless (collect($blocks)->contains(fn ($b) => $b['type'] === 'hero'))
        <x-hero.page :title="$page->title" :crumbs="$breadcrumbs" />
    @endunless
    @foreach ($blocks as $block)
        @includeIf('site.blocks.'.$block['type'], ['data' => $block['data'], 'media' => $media])
    @endforeach
@endsection
