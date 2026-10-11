<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $site['name'])</title>
    <meta name="description" content="@hasSection('description')@yield('description')@else{{ $site['default_description'] }}@endif">
    <meta name="theme-color" content="#2c3f5c">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $site['name'] }}">
    <meta property="og:title" content="@yield('title', $site['name'])">
    <meta property="og:description" content="@hasSection('description')@yield('description')@else{{ $site['default_description'] }}@endif">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')<meta property="og:image" content="@yield('og_image')"><meta name="twitter:card" content="summary_large_image">@endif
    @stack('head')
    <script type="application/ld+json">{!! json_encode($site['organization_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @if (! empty($breadcrumbs) && count($breadcrumbs) > 1)
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)->values()->map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['label'], 'item' => url($c['url'])])->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
    @stack('jsonld')
    @vite(['resources/scss/site/app.scss', 'resources/js/site/app.js'])
</head>
<body id="top" @if ($site['analytics_id']) data-analytics-id="{{ $site['analytics_id'] }}" @endif>
<a href="#main" class="skip-link">Skip to content</a>
<x-site.utility-bar :site="$site" />
<x-site.header :site="$site" />
<x-site.explore-menu :items="$site['menu_explore']" />
<main id="main">
    @isset($breadcrumbs)@unless (View::hasSection('crumbs_in_hero'))<x-ui.breadcrumb :items="$breadcrumbs" />@endunless @endisset
    @yield('content')
</main>
@unless (request()->routeIs('newsletter*'))<x-site.newsletter-band />@endunless
<x-site.footer :site="$site" />
<x-site.cookie-banner :text="$site['cookie_text']" />
</body>
</html>
