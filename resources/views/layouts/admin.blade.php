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
                    @can('pages.edit')
                        <li class="nav-item">
                            <a href="{{ route('admin.pages.index') }}" class="nav-link @if (request()->routeIs('admin.pages.*')) active @endif">
                                <i class="nav-icon bi bi-file-earmark-text"></i><p>Pages</p>
                            </a>
                        </li>
                    @endcan
                    @can('menus.manage')
                        <li class="nav-item">
                            <a href="{{ route('admin.menus.index') }}" class="nav-link @if (request()->routeIs('admin.menus.*')) active @endif">
                                <i class="nav-icon bi bi-list-nested"></i><p>Menus</p>
                            </a>
                        </li>
                    @endcan
                    @can('seo.manage')
                        <li class="nav-item">
                            <a href="{{ route('admin.redirects.index') }}" class="nav-link @if (request()->routeIs('admin.redirects.*')) active @endif">
                                <i class="nav-icon bi bi-signpost-split"></i><p>Redirects &amp; 404s</p>
                            </a>
                        </li>
                    @endcan
                    @canany(['documents.investor.manage', 'documents.general.manage'])
                        <li class="nav-item">
                            <a href="{{ route('admin.documents.index') }}" class="nav-link @if (request()->routeIs('admin.documents.*')) active @endif">
                                <i class="nav-icon bi bi-file-earmark-pdf"></i><p>Documents</p>
                            </a>
                        </li>
                    @endcanany
                    @can('news.edit')
                        <li class="nav-item">
                            <a href="{{ route('admin.posts.index') }}" class="nav-link @if (request()->routeIs('admin.posts.*')) active @endif">
                                <i class="nav-icon bi bi-newspaper"></i><p>News &amp; press</p>
                            </a>
                        </li>
                    @endcan
                    @can('pages.publish')
                        @foreach (App\Domain\Content\ContentResource::all() as $res)
                            <li class="nav-item">
                                <a href="{{ route('admin.content.index', $res->key) }}" class="nav-link @if (request()->is(config('admin.path').'/content/'.$res->key.'*')) active @endif">
                                    <i class="nav-icon bi bi-collection"></i><p>{{ $res->label }}</p>
                                </a>
                            </li>
                        @endforeach
                    @endcan
                    @canany(['enquiries.manage', 'enquiries.view'])
                        <li class="nav-item"><a href="{{ route('admin.enquiries.index') }}" class="nav-link @if (request()->routeIs('admin.enquiries.*')) active @endif"><i class="nav-icon bi bi-envelope"></i><p>Enquiries</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.subscribers.index') }}" class="nav-link @if (request()->routeIs('admin.subscribers.*')) active @endif"><i class="nav-icon bi bi-people"></i><p>Subscribers</p></a></li>
                    @endcanany
                    @can('careers.manage')
                        <li class="nav-item"><a href="{{ route('admin.vacancies.index') }}" class="nav-link @if (request()->routeIs('admin.vacancies.*', 'admin.applications.*')) active @endif"><i class="nav-icon bi bi-briefcase"></i><p>Careers</p></a></li>
                    @endcan
                    @canany(['media.manage', 'media.view'])
                        <li class="nav-item">
                            <a href="{{ route('admin.media.index') }}" class="nav-link @if (request()->routeIs('admin.media.*')) active @endif">
                                <i class="nav-icon bi bi-images"></i><p>Media library</p>
                            </a>
                        </li>
                    @endcanany
                    @can('settings.manage')
                        <li class="nav-item"><a href="{{ route('admin.privacy.index') }}" class="nav-link @if (request()->routeIs('admin.privacy.*')) active @endif"><i class="nav-icon bi bi-person-lock"></i><p>Privacy tools</p></a></li>
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
