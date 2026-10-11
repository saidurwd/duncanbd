<section class="block block-map">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    @if ($data['address'])<address>{{ $data['address'] }}</address>@endif
    <a href="https://www.openstreetmap.org/?mlat={{ $data['lat'] }}&mlon={{ $data['lng'] }}#map=14/{{ $data['lat'] }}/{{ $data['lng'] }}" rel="noopener">View on map</a>
</section>
