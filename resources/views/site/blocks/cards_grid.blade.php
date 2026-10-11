<section class="block block-cards">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    <div class="cards">
        @foreach ($data['items'] ?? [] as $card)
            @php($image = $card['media_id'] ? $media->get($card['media_id']) : null)
            <article class="card-item">
                @if ($image)<img src="{{ $image->getFirstMediaUrl('file', 'medium') ?: $image->getFirstMediaUrl('file') }}" alt="{{ $image->altAttribute() }}" loading="lazy">@endif
                <h3>@if ($card['url'])<a href="{{ $card['url'] }}">{{ $card['title'] }}</a>@else{{ $card['title'] }}@endif</h3>
                @if ($card['text'])<p>{{ $card['text'] }}</p>@endif
            </article>
        @endforeach
    </div>
</section>
