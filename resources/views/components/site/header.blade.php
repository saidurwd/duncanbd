@props(['site'])
<header class="site-header" data-site-header>
    <div class="container">
        <a href="{{ url('/') }}" class="site-header__brand" aria-label="{{ $site['name'] }} — home">
            <img src="{{ asset('favicon.svg') }}" alt="" width="40" height="40" class="site-header__mark">
            <span class="site-header__name">{{ $site['name'] }}</span>
        </a>

        <nav class="primary-nav" aria-label="Primary">
            <ul>
                @foreach (array_slice($site['menu_explore'], 0, 7) as $item)
                    <li class="@if ($item['children']) has-children @endif">
                        <a href="{{ $item['url'] ?? '#' }}" @if ($item['target'] === '_blank') target="_blank" rel="noopener" @endif @if (request()->is(ltrim((string) $item['url'], '/').'*') && $item['url'] && $item['url'] !== '/') aria-current="true" @endif>{{ $item['label'] }}@if ($item['children'])<span class="caret" aria-hidden="true"></span>@endif</a>
                        @if ($item['children'])
                            <ul class="primary-nav__sub">
                                @foreach ($item['children'] as $child)
                                    <li><a href="{{ $child['url'] ?? '#' }}">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <button type="button" class="explore-toggle" data-explore-toggle aria-expanded="false" aria-controls="explore-menu">
            <span class="explore-toggle__bars" aria-hidden="true"></span><span class="explore-toggle__label">Menu</span>
        </button>
    </div>
</header>
