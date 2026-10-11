<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $heading }} · {{ config('app.name') }}</title>
    @vite(['resources/scss/site/app.scss'])
</head>
<body>
<main id="main" class="block">
    <div class="container">
        <p class="meta">Error {{ $code }}</p>
        <h1>{{ $heading }}</h1>
        <p class="lead">{{ $message }}</p>
        <p><a class="button" href="{{ url('/') }}">Go to the home page</a> <a class="button button--ghost" href="{{ url('/search') }}">Search the site</a></p>
    </div>
</main>
</body>
</html>
