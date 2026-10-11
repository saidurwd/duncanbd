@extends('layouts.site')

@section('title', $estate->name.' · '.config('app.name'))

@section('content')
    <article class="block">
<div class="container">
        <p><a href="{{ route('business.show', $business->slug) }}">← {{ $business->name }}</a></p>
        <h1>{{ $estate->name }}</h1>
        @if ($estate->photo?->getFirstMedia('file'))
            <img src="{{ $estate->photo->getFirstMediaUrl('file', 'large') ?: $estate->photo->getFirstMediaUrl('file') }}" alt="{{ $estate->photo->altAttribute() }}">
        @endif
        <dl class="metrics">
            @if ($estate->district)<div><dt>District</dt><dd>{{ $estate->district }}</dd></div>@endif
            @if ($estate->area_ha)<div><dt>Area</dt><dd>{{ rtrim(rtrim(number_format((float) $estate->area_ha, 2), '0'), '.') }} ha</dd></div>@endif
            @if ($estate->factory)<div><dt>Factory</dt><dd>{{ $estate->factory }}</dd></div>@endif
        </dl>
        @if ($estate->certifications)
            <h2>Certifications</h2>
            <ul>@foreach ($estate->certifications as $c)<li>{{ $c }}</li>@endforeach</ul>
        @endif
        <div class="prose">{!! $estate->body !!}</div>
        @if ($estate->lat && $estate->lng)
            <p><a href="https://www.openstreetmap.org/?mlat={{ $estate->lat }}&mlon={{ $estate->lng }}#map=13/{{ $estate->lat }}/{{ $estate->lng }}" rel="noopener">View on map</a></p>
        @endif
    </div>
</article>
@endsection
