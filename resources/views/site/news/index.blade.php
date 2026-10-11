@extends('layouts.site')

@section('title', 'News · '.config('app.name'))

@section('content')
    <section class="block">
<div class="container">
        <h1>News &amp; media</h1>
        <nav aria-label="Filter news">
            <a href="{{ route('news.index') }}">All</a> |
            <a href="{{ route('news.index', ['type' => 'news']) }}">News</a> |
            <a href="{{ route('news.index', ['type' => 'press']) }}">Press releases</a>
            @foreach ($categories as $c) | <a href="{{ route('news.index', ['category' => $c->slug]) }}">{{ $c->name }}</a>@endforeach
        </nav>
        <div class="cards">
            @forelse ($posts as $post)@include('site.news._card', ['post' => $post])@empty<p>No stories yet.</p>@endforelse
        </div>
        {{ $posts->links() }}
    </div>
</section>
@endsection
