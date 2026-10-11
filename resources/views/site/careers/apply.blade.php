@extends('layouts.site')

@section('title', 'Apply: '.$vacancy->title.' · '.$site['name'])

@push('head')<meta name="robots" content="noindex, nofollow">@endpush

@section('content')
    <section class="block">
        <div class="container">
            <p><a href="{{ route('careers.show', $vacancy->slug) }}">← {{ $vacancy->title }}</a></p>
            <h1>Apply: {{ $vacancy->title }}</h1>
            <x-forms.errors />
            <form method="POST" action="{{ route('careers.apply.store', $vacancy->slug) }}" enctype="multipart/form-data" novalidate>
                @csrf
                <x-forms.guard />
                <x-forms.field name="name" label="Full name" required autocomplete="name" />
                <x-forms.field name="email" type="email" label="Email address" required autocomplete="email" />
                <x-forms.field name="phone" label="Phone (optional)" autocomplete="tel" />
                <x-forms.field name="cover_letter" type="textarea" label="Cover letter (optional)" />
                <x-forms.field name="cv" type="file" label="CV" required accept=".pdf,.doc,.docx" hint="PDF, DOC or DOCX, up to 5 MB." />
                <div class="field">
                    <label class="check"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span> I consent to {{ $site['name'] }} processing my application data for recruitment and keeping it for up to 12 months after this vacancy closes, as described in the <a href="/privacy-notice">privacy notice</a>. <span aria-hidden="true">*</span></span></label>
                    @error('consent')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                @error('captcha')<p class="field-error">{{ $message }}</p>@enderror
                <button class="button">Submit application</button>
            </form>
        </div>
    </section>
@endsection
