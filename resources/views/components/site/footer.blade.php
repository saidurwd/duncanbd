@props(['site'])
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__about">
                <a href="{{ url('/') }}" class="site-footer__brand">
                    @if ($site['logo'])
                        <img src="{{ $site['logo']['path'] }}" alt="{{ $site['name'] }}" width="{{ $site['logo']['width'] ?: 160 }}" height="{{ $site['logo']['height'] ?: 48 }}" class="site-footer__logo" loading="lazy">
                    @else
                        <img src="{{ asset('favicon.svg') }}" alt="" width="44" height="44"><span>{{ $site['name'] }}</span>
                    @endif
                </a>
                @if ($site['tagline'])<p>{{ $site['tagline'] }}</p>@endif
                @if ($site['group_name'])<p class="site-footer__group">A member of the <a href="{{ $site['group_url'] ?? '#' }}" rel="noopener">{{ $site['group_name'] }}</a> group.</p>@endif
                @if ($site['socials'])
                    <ul class="site-footer__social" aria-label="Social media">
                        @foreach ($site['socials'] as $label => $url)<li><a href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}<span class="visually-hidden"> (opens in a new tab)</span></a></li>@endforeach
                    </ul>
                @endif
            </div>
            <nav aria-label="Footer: sections">
                <h2>Explore</h2>
                <ul>
                    @foreach ($site['menu_explore'] as $item)<li><a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a></li>@endforeach
                </ul>
            </nav>
            <nav aria-label="Footer: quick links">
                <h2>Quick links</h2>
                <ul>
                    @foreach ($site['menu_footer'] as $item)<li><a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="{{ route('search') }}">Search</a></li>
                    <li><a href="{{ route('sitemap.html') }}">Sitemap</a></li>
                </ul>
            </nav>
            <div>
                <h2>Contact</h2>
                @foreach ($site['addresses'] as $address)<address>{!! nl2br(e($address)) !!}</address>@endforeach
                @if ($site['phone'])<p>Tel: <a href="tel:{{ preg_replace('/[^\d+]/', '', explode(',', $site['phone'])[0]) }}">{{ $site['phone'] }}</a></p>@endif
                @if ($site['fax'])<p>Fax: {{ $site['fax'] }}</p>@endif
                @if ($site['email'])<p><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></p>@endif
            </div>
        </div>
        <div class="site-footer__legal">
            <p>© {{ now()->year }} {{ $site['name'] }}. @if ($site['footer_text'])<span class="site-footer__note">{{ $site['footer_text'] }}</span>@endif</p>
            <nav aria-label="Legal">
                <ul class="site-footer__legal-links">
                    @foreach ($site['menu_legal'] as $item)<li><a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="#" data-cookie-preferences>Cookie settings</a></li>
                </ul>
            </nav>
        </div>
    </div>
    <a href="#top" class="back-to-top" data-back-to-top hidden aria-label="Back to top"><span aria-hidden="true">↑</span></a>
</footer>
