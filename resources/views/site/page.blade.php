@extends('layouts.site')

@section('title', ($page->seo['title'] ?? null) ?: $page->title.' · '.config('app.name'))
@if (! empty($page->seo['description']))
    @section('description', $page->seo['description'])
@endif

@push('head')
    @if ($preview)<meta name="robots" content="noindex, nofollow">@endif
@endpush

@section('content')
    @if ($preview)
        <div class="preview-banner" role="status">Preview — this page is not published.</div>
    @endif
    @foreach ($blocks as $block)
        @includeIf('site.blocks.'.$block['type'], ['data' => $block['data'], 'media' => $media])
    @endforeach
@endsection
