@extends('layouts.site')

@section('title', $business->name.' · '.config('app.name'))
@section('description', $business->summary)

@section('content')
    <article class="block">
<div class="container">
        <h1>{{ $business->name }}</h1>
        @if ($business->summary)<p class="lead">{{ $business->summary }}</p>@endif
        @if ($business->metrics)
            <dl class="metrics">
                @foreach ($business->metrics as $m)<div><dt>{{ $m['label'] }}</dt><dd>{{ $m['value'] }}</dd></div>@endforeach
            </dl>
        @endif
        {{-- Sanitised with the HTMLPurifier allow-list on save. --}}
        <div class="prose">{!! $business->body !!}</div>
        @if ($business->estates->isNotEmpty())
            <h2>Our estates</h2>
            <ul>
                @foreach ($business->estates as $estate)
                    <li><a href="{{ route('business.estate', [$business->slug, $estate->slug]) }}">{{ $estate->name }}</a>@if ($estate->district), {{ $estate->district }}@endif</li>
                @endforeach
            </ul>
        @endif
        @if ($business->website_url)<p><a href="{{ $business->website_url }}" rel="noopener">Visit website</a></p>@endif
    </div>
</article>
@endsection
