@extends('layouts.site')

@section('title', 'Contact us · '.$site['name'])

@section('content')
    <x-hero.page title="Contact us" intro="Tell us how we can help and we will route your message to the right team." />
    <section class="block">
        <div class="container contact-grid">
            <div>
                <x-forms.errors />
                <form method="POST" action="{{ route('contact.store') }}" novalidate>
                    @csrf
                    <x-forms.guard />
                    <div class="field">
                        <label for="f-category">What is your enquiry about? <span aria-hidden="true">*</span></label>
                        <select id="f-category" name="category" required>
                            @foreach ($categories as $c)<option value="{{ $c->value }}" @selected(old('category', $selected) === $c->value)>{{ $c->label() }}</option>@endforeach
                        </select>
                    </div>
                    <x-forms.field name="name" label="Your name" required autocomplete="name" />
                    <x-forms.field name="email" type="email" label="Email address" required autocomplete="email" />
                    <x-forms.field name="phone" label="Phone (optional)" autocomplete="tel" />
                    <x-forms.field name="organisation" label="Organisation (optional)" autocomplete="organization" />
                    <x-forms.field name="message" type="textarea" label="Message" required />
                    <div class="field">
                        <label class="check"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span> I agree that {{ $site['name'] }} may use these details to respond to me, as described in the <a href="/privacy-notice">privacy notice</a>. <span aria-hidden="true">*</span></span></label>
                        @error('consent')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    @error('captcha')<p class="field-error">{{ $message }}</p>@enderror
                    <button class="button">Send message</button>
                </form>
            </div>
            <aside aria-label="Our offices">
                <h2>Our offices</h2>
                @foreach ($site['addresses'] as $address)<address>{!! nl2br(e($address)) !!}</address>@endforeach
                @if ($site['phone'])<p>Tel: {{ $site['phone'] }}</p>@endif
                @if ($site['email'])<p><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></p>@endif
            </aside>
        </div>
    </section>
@endsection
