<section class="block block-cta">
    <div class="container">
        <h2>{{ $data['heading'] }}</h2>
        @if ($data['text'])<p>{{ $data['text'] }}</p>@endif
        <a href="{{ $data['button_url'] }}" class="button">{{ $data['button_label'] }}</a>
    </div>
</section>
