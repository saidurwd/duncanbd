<section class="block block-certifications">
    <div class="container">
        @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
        @if ($data['intro'])<p class="lead">{{ $data['intro'] }}</p>@endif
        <ul class="cert-grid">
            @foreach ($data['items'] ?? [] as $cert)
                @php($image = $cert['media_id'] ? $media->get($cert['media_id']) : null)
                <li class="cert">
                    @if ($image)<img src="{{ $image->getFirstMediaUrl('file', 'thumb') ?: $image->getFirstMediaUrl('file') }}" alt="{{ $image->altAttribute() }}" loading="lazy">@else<span class="cert__seal" aria-hidden="true">✓</span>@endif
                    <div>
                        <h3>{{ $cert['name'] }}@if ($cert['year']) <span class="cert__year">{{ $cert['year'] }}</span>@endif</h3>
                        @if ($cert['issuer'])<p class="meta">{{ $cert['issuer'] }}</p>@endif
                        @if ($cert['scope'])<p>{{ $cert['scope'] }}</p>@endif
                        @if ($cert['url'])<p><a href="{{ $cert['url'] }}">More information</a></p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
