@extends('layouts.site')

@section('title', 'Sitemap · '.$site['name'])

@section('content')
    <section class="block">
        <div class="container">
            <h1>Sitemap</h1>
            <ul>
                @foreach ($pages as $page)
                    <li class="depth-{{ min(substr_count($page->path, "/"), 4) }}"><a href="{{ App\Domain\Cms\RedirectManager::pageUrl($page) }}">{{ $page->title }}</a></li>
                @endforeach
                <li><a href="{{ route('news.index') }}">News</a></li>
                <li><a href="{{ route('documents.index') }}">Documents</a></li>
            </ul>
        </div>
    </section>
@endsection
