<nav class="block block-tiles" aria-label="Quick links">
    @foreach ($data['items'] ?? [] as $item)
        <a href="{{ $item['url'] }}" class="tile">{{ $item['label'] }}</a>
    @endforeach
</nav>
