<section class="block block-map">
    <div class="container">
        @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
        @if ($data['address'])<address>{!! nl2br(e($data['address'])) !!}</address>@endif
        <a href="https://www.openstreetmap.org/?mlat={{ $data['lat'] }}&mlon={{ $data['lng'] }}#map=14/{{ $data['lat'] }}/{{ $data['lng'] }}" rel="noopener" target="_blank">View on map<span class="visually-hidden"> (opens in a new tab)</span></a>
    </div>
</section>
