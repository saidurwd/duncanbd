@extends('layouts.site')

@section('title', ($page->seo['title'] ?? null) ?: $page->title.' · '.$site['name'])
@if (! empty($page->seo['description']) || ! empty($autoDescription))
    @section('description', $page->seo['description'] ?? $autoDescription)
@endif
@if (! empty($ogImage))
    @section('og_image', $ogImage)
@endif

@push('head')
    @if ($preview)<meta name="robots" content="noindex, nofollow">@endif
@endpush

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
