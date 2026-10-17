<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $site['name'])</title>
    <meta name="description" content="@hasSection('description')@yield('description')@else{{ $site['default_description'] }}@endif">
    <meta name="theme-color" content="#2c3f5c">
    @if ($site['favicon'])<link rel="icon" href="{{ $site['favicon']['path'] }}" type="image/png">@else<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">@endif
    <link rel="canonical" href="{{ url()->current() }}">
    @php
        $desc = View::hasSection('description') ? trim(View::getSection('description')) : $site['default_description'];
        $pageTitle = trim(View::hasSection('title') ? View::getSection('title') : $site['name']);
        // Share image: the page's own, else the site-wide default, else the logo.
        $shareImage = View::hasSection('og_image') ? trim(View::getSection('og_image')) : ($site['share_image'] ? url($site['share_image']['path']) : ($site['logo'] ? url($site['logo']['path']) : null));
        $shareIsDefault = ! View::hasSection('og_image') && $site['share_image'];
    @endphp
    <meta name="robots" content="@hasSection('robots')@yield('robots')@else{{ 'index, follow, max-image-preview:large' }}@endif">
    <meta property="og:site_name" content="{{ $site['name'] }}">
    <meta property="og:locale" content="en_GB">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($shareImage)
        <meta property="og:image" content="{{ $shareImage }}">
        <meta property="og:image:alt" content="{{ $site['name'] }}">
        @if ($shareIsDefault && ($site['share_image']['width'] ?? 0))<meta property="og:image:width" content="{{ $site['share_image']['width'] }}"><meta property="og:image:height" content="{{ $site['share_image']['height'] }}">@endif
    @endif
    @yield('og_extra')
    <meta name="twitter:card" content="{{ $shareImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $desc }}">
    @if ($shareImage)<meta name="twitter:image" content="{{ $shareImage }}">@endif
    @if ($site['google_verify'])<meta name="google-site-verification" content="{{ $site['google_verify'] }}">@endif
    @if ($site['bing_verify'])<meta name="msvalidate.01" content="{{ $site['bing_verify'] }}">@endif
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
