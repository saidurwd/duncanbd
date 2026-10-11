<section class="block block-stats">
    @foreach ($data['items'] ?? [] as $item)
        <div class="stat"><strong>{{ $item['value'] }}</strong><span>{{ $item['label'] }}</span></div>
    @endforeach
</section>
