<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }} Admin</title>
    @vite(['resources/scss/admin/app.scss', 'resources/js/admin/app.js'])
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <a href="#main" class="visually-hidden-focusable">Skip to content</a>
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle navigation"><i class="bi bi-list"></i></a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">{{ auth()->user()->name }}</li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Sign out</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="brand-link"><span class="brand-text fw-bold">{{ config('app.name') }}</span></a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2" aria-label="Main">
                <ul class="nav sidebar-menu flex-column" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link @if (request()->routeIs('admin.dashboard')) active @endif">
                            <i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p>
                        </a>
                    </li>
                    @can('settings.manage')
                        <li class="nav-item">
                            <a href="{{ route('admin.settings.edit') }}" class="nav-link @if (request()->routeIs('admin.settings.*')) active @endif">
                                <i class="nav-icon bi bi-gear"></i><p>Settings</p>
                            </a>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <a href="{{ route('admin.security') }}" class="nav-link @if (request()->routeIs('admin.security')) active @endif">
                            <i class="nav-icon bi bi-shield-lock"></i><p>My security</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <main id="main" class="app-main">
        <div class="app-content-header">
            <div class="container-fluid"><h1 class="h3 mb-0">@yield('title', 'Dashboard')</h1></div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                @if (session('status'))
                    <div class="alert alert-info" role="status">{{ session('status') }}</div>
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
</div>
</body>
</html>
