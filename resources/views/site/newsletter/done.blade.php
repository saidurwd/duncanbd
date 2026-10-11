@extends('layouts.site')

@section('title', 'Thank you · '.$site['name'])

@push('head')<meta name="robots" content="noindex, nofollow">@endpush

@section('content')
    <section class="block"><div class="container"><h1>Thank you</h1><p class="lead">{{ $message }}</p><p><a href="{{ url('/') }}">Back to the home page</a></p></div></section>
@endsection
