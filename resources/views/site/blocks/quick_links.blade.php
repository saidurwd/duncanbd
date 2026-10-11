<nav class="block block-tiles" aria-label="Quick links">
    <div class="container">
        @foreach ($data['items'] ?? [] as $item)<a href="{{ $item['url'] }}" class="tile">{{ $item['label'] }}</a>@endforeach
    </div>
</nav>
