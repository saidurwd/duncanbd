<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @hasSection('description')<meta name="description" content="@yield('description')">@endif
    @stack('head')
    @vite(['resources/scss/site/app.scss', 'resources/js/site/app.js'])
</head>
<body>
<a href="#main" class="skip-link">Skip to content</a>
<main id="main">@yield('content')</main>
</body>
</html>
