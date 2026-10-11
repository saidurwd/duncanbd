@extends('layouts.site')

@section('title', 'Subscribe · '.$site['name'])

@section('content')
    <section class="block">
        <div class="container">
            <h1>Receive news &amp; updates</h1>
            <x-forms.errors />
            <form method="POST" action="{{ route('newsletter.subscribe') }}" novalidate>
                @csrf
                <x-forms.guard />
                <x-forms.field name="email" type="email" label="Email address" required autocomplete="email" />
                <div class="field"><label class="check"><input type="checkbox" name="consent" value="1" required><span> I agree to receive news and updates and have read the <a href="/privacy-notice">privacy notice</a>.</span></label>@error('consent')<p class="field-error">{{ $message }}</p>@enderror</div>
                <button class="button">Subscribe</button>
            </form>
        </div>
    </section>
@endsection
