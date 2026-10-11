<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    @vite(['resources/scss/admin/app.scss', 'resources/js/admin/app.js'])
</head>
<body class="login-page bg-body-secondary">
<main class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <span class="h1 fw-bold">{{ config('app.name') }}</span>
        </div>
        <div class="card-body login-card-body">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</main>
</body>
</html>
