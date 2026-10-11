@props(['site'])
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div>
                <p><strong>{{ $site['name'] }}</strong></p>
                @foreach ($site['addresses'] as $address)<address>{!! nl2br(e($address)) !!}</address>@endforeach
                @if ($site['phone'])<p>Tel: <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}">{{ $site['phone'] }}</a></p>@endif
                @if ($site['email'])<p><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></p>@endif
                @if ($site['footer_text'])<p>{{ $site['footer_text'] }}</p>@endif
            </div>
            <nav aria-label="Footer">
                <h2>Explore</h2>
                <ul>
                    @foreach ($site['menu_footer'] ?: $site['menu_explore'] as $item)<li><a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a></li>@endforeach
                </ul>
            </nav>
            <div>
                <h2>Follow us</h2>
                <ul>
                    @foreach ($site['socials'] as $label => $url)<li><a href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}<span class="visually-hidden"> (opens in a new tab)</span></a></li>@endforeach
                </ul>
            </div>
        </div>
        <div class="site-footer__legal">
            <nav aria-label="Legal">
                <ul class="site-footer__legal-links">
                    @foreach ($site['menu_legal'] as $item)<li><a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="{{ route('sitemap.html') }}">Sitemap</a></li>
                    <li><a href="#" data-cookie-preferences>Cookie settings</a></li>
                </ul>
            </nav>
            <p>© {{ now()->year }} {{ $site['name'] }}</p>
        </div>
    </div>
</footer>
