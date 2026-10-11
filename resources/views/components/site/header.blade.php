@props(['site'])
<header class="site-header">
    <div class="container">
        <a href="{{ url('/') }}" class="site-header__brand">{{ $site['name'] }}</a>
        <button type="button" class="explore-toggle" data-explore-toggle aria-expanded="false" aria-controls="explore-menu">Explore</button>
    </div>
</header>
