@extends('layouts.site')

@section('title', ($action === 'confirm' ? 'Confirm subscription' : 'Unsubscribe').' · '.$site['name'])

@push('head')<meta name="robots" content="noindex, nofollow">@endpush

@section('content')
    <section class="block">
        <div class="container">
            <h1>{{ $action === 'confirm' ? 'Confirm your subscription' : 'Unsubscribe' }}</h1>
            <p>{{ $action === 'confirm' ? 'Press the button to confirm that you want to receive news and updates.' : 'Press the button to stop receiving emails from us.' }}</p>
            {{-- Posts to the same signed URL; mail scanners that merely open the link change nothing. --}}
            <form method="POST" action="{{ request()->fullUrl() }}">
                @csrf
                <button class="button">{{ $action === 'confirm' ? 'Confirm subscription' : 'Unsubscribe' }}</button>
            </form>
        </div>
    </section>
@endsection
