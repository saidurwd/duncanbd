@extends('layouts.site')

@section('title', 'Search · '.$site['name'])

@section('robots', 'noindex, follow')

@section('content')
    <section class="block">
        <div class="container">
            <h1>Search</h1>
            <form method="GET" action="{{ route('search') }}" class="search-form" role="search">
                <label for="q" class="visually-hidden">Search the site</label>
                <input id="q" type="search" name="q" value="{{ $query }}" minlength="2" maxlength="100" required>
                <button class="button">Search</button>
            </form>
            @if ($query !== '' && mb_strlen($query) < 2)
                <p>Please enter at least 2 characters.</p>
            @elseif ($query !== '')
                <h2 class="visually-hidden">Results</h2>
                @if ($results->isEmpty())
                    <p>No results for “{{ $query }}”.</p>
                @else
                    <p class="meta">{{ $results->count() }} result{{ $results->count() === 1 ? '' : 's' }}</p>
                    <ul class="search-results">
                        @foreach ($results as $result)
                            <li>
                                <a href="{{ $result['url'] }}">{{ $result['title'] }}</a>
                                <span class="meta"> · {{ $result['type'] }}</span>
                                @if ($result['excerpt'])<p>{{ $result['excerpt'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
    </section>
@endsection
