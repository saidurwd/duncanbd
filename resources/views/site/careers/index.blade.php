@extends('layouts.site')

@section('title', 'Current vacancies · '.$site['name'])
@section('description', 'Current job vacancies at '.$site['name'])

@section('content')
    <x-hero.page title="Current vacancies" />
    <section class="block">
        <div class="container">
            @forelse ($vacancies as $vacancy)
                <article class="vacancy">
                    <h2><a href="{{ route('careers.show', $vacancy->slug) }}">{{ $vacancy->title }}</a></h2>
                    <p class="meta">
                        {{ collect([$vacancy->department, $vacancy->location, $vacancy->employment_type->label()])->filter()->implode(' · ') }}
                        @if ($vacancy->closing_date) · Closes {{ $vacancy->closing_date->format('j F Y') }}@endif
                    </p>
                </article>
            @empty
                <p>There are no vacancies at the moment. Please check back soon.</p>
            @endforelse
        </div>
    </section>
@endsection
