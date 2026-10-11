@extends('layouts.site')

@section('title', $vacancy->title.' · '.$site['name'])

@push('jsonld')
    @if ($open)
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $vacancy->title,
            'description' => trim(strip_tags($vacancy->description.' '.$vacancy->requirements)),
            'datePosted' => ($vacancy->published_at ?? $vacancy->created_at)->toDateString(),
            'validThrough' => $vacancy->closing_date?->toDateString(),
            'employmentType' => $vacancy->employment_type->schemaValue(),
            'hiringOrganization' => ['@type' => 'Organization', 'name' => $site['name'], 'sameAs' => url('/')],
            'jobLocation' => $vacancy->location ? ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $vacancy->location, 'addressCountry' => 'BD']] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
@endpush

@section('content')
    <section class="block">
        <div class="container">
            <p><a href="{{ route('careers.index') }}">← All vacancies</a></p>
            <h1>{{ $vacancy->title }}</h1>
            <p class="meta">{{ collect([$vacancy->department, $vacancy->location, $vacancy->employment_type->label()])->filter()->implode(' · ') }}@if ($vacancy->closing_date) · Closes {{ $vacancy->closing_date->format('j F Y') }}@endif</p>
            {{-- Sanitised with the HTMLPurifier allow-list on save. --}}
            <div class="prose">{!! $vacancy->description !!}</div>
            @if ($vacancy->requirements)<h2>Requirements</h2><div class="prose">{!! $vacancy->requirements !!}</div>@endif
            @if ($open)
                <p><a class="button" href="{{ route('careers.apply', $vacancy->slug) }}">Apply for this job</a></p>
            @else
                <p class="form-summary" role="status">This vacancy is now closed to applications.</p>
            @endif
        </div>
    </section>
@endsection
