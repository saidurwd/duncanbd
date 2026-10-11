<section class="block block-stats" aria-label="Key figures">
    <div class="container">
        @foreach ($data['items'] ?? [] as $item)
            <div class="stat"><strong data-count-up>{{ $item['value'] }}</strong><span>{{ $item['label'] }}</span></div>
        @endforeach
    </div>
</section>
