<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }} Admin</title>
    @vite(['resources/scss/admin/app.scss', 'resources/js/admin/app.js'])
</head>
@php($me = auth()->user())
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <a href="#main" class="visually-hidden-focusable">Skip to content</a>

    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle navigation"><i class="bi bi-list"></i></a></li>
                <li class="nav-item d-none d-md-block"><a href="{{ route('admin.dashboard') }}" class="nav-link">Home</a></li>
                <li class="nav-item d-none d-md-block"><a href="{{ url('/') }}" class="nav-link" target="_blank" rel="noopener">View website <i class="bi bi-box-arrow-up-right small"></i><span class="visually-hidden"> (opens in a new tab)</span></a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#" data-lte-toggle="fullscreen" role="button" aria-label="Toggle full screen"><i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i><i data-lte-icon="minimize" class="bi bi-fullscreen-exit" hidden></i></a></li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-initials" aria-hidden="true">{{ Str::of($me->name)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}</span>
                        <span class="d-none d-md-inline">{{ $me->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <span class="avatar-initials avatar-lg" aria-hidden="true">{{ Str::of($me->name)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}</span>
                            <p>{{ $me->name }}<small>{{ $me->roles->first() ? App\Domain\Identity\Enums\Role::from($me->roles->first()->name)->label() : '' }}</small><small>{{ $me->email }}</small></p>
                        </li>
                        <li class="user-footer">
                            <a href="{{ route('admin.security') }}" class="btn btn-outline-secondary">My security</a>
                            <form method="POST" action="{{ route('logout') }}" class="float-end">@csrf<button type="submit" class="btn btn-outline-secondary">Sign out</button></form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="brand-link">
                <span class="brand-mark" aria-hidden="true">DB</span>
                <span class="brand-text fw-light">Duncan Brothers <small class="d-block">Website admin</small></span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2" aria-label="Main">@include('admin._sidebar')</nav>
        </div>
    </aside>

    <main id="main" class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h1 class="mb-0 fs-3">@yield('title', 'Dashboard')</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                            @hasSection('breadcrumb')@yield('breadcrumb')@endif
                            <li class="breadcrumb-item active" aria-current="page">@yield('title', 'Dashboard')</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                @if (session('status') && ! in_array(session('status'), ['two-factor-authentication-enabled', 'two-factor-authentication-confirmed', 'two-factor-authentication-disabled', 'recovery-codes-generated', 'password-updated']))
                    <div class="alert alert-success alert-dismissible fade show" role="status">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Website administration · {{ config('app.version') }}</div>
        <strong>© {{ now()->year }} <a href="{{ url('/') }}" class="text-decoration-none" target="_blank" rel="noopener">Duncan Brothers (Bangladesh) Limited</a>.</strong> All rights reserved.
    </footer>
</div>
</body>
</html>
