@extends('layouts.site')

@section('title', 'Application received · '.$site['name'])

@push('head')<meta name="robots" content="noindex, nofollow">@endpush

@section('content')
    <section class="block">
        <div class="container">
            <h1>Application received</h1>
            <x-forms.errors />
            <p>Thank you for applying for “{{ $vacancy->title }}”.</p>
            <p><a href="{{ route('careers.index') }}">See other vacancies</a></p>
        </div>
    </section>
@endsection
