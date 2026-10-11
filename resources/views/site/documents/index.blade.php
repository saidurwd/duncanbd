@extends('layouts.site')

@section('title', ($category?->name ?? 'Documents').' · '.config('app.name'))

@section('content')
    <section class="block">
        <h1>{{ $category?->name ?? 'Reports & documents' }}</h1>
        <nav aria-label="Document categories">
            <a href="{{ route('documents.index') }}">All</a>
            @foreach ($categories as $cat)
                | <a href="{{ route('documents.category', $cat->slug) }}" @if ($category?->id === $cat->id) aria-current="page" @endif>{{ $cat->name }}</a>
            @endforeach
        </nav>
        @if ($years->isNotEmpty())
            <form method="GET" class="doc-filter">
                <label for="year">Financial year</label>
                <select id="year" name="year">
                    <option value="">All</option>
                    @foreach ($years as $y)<option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>@endforeach
                </select>
                <button>Filter</button>
            </form>
        @endif
        @include('site.documents._list', ['documents' => $documents])
        {{ $documents->links() }}
    </section>
@endsection
