@extends('layouts.site')

@section('title', $document->title.' · '.config('app.name'))

@section('content')
    <article class="block">
        <p><a href="{{ route('documents.category', $document->category->slug) }}">← {{ $document->category->name }}</a></p>
        <h1>{{ $document->title }}</h1>
        @if ($document->financial_year)<p>Financial year {{ $document->financial_year }}</p>@endif
        @if ($document->description)<p>{{ $document->description }}</p>@endif
        <a href="{{ route('documents.download', $document->uuid) }}" class="button" download>Download {{ $document->fileTypeLabel() }} ({{ $document->sizeLabel() }})</a>
    </article>
@endsection
