<section class="block block-hero">
    @php($slide = $data['slides'][0] ?? null)
    @php($image = $slide ? $media->get($slide['media_id']) : null)
    @if ($image)
        <img src="{{ $image->getFirstMediaUrl('file', 'large') ?: $image->getFirstMediaUrl('file') }}" alt="{{ $image->altAttribute() }}" class="block-hero__image" fetchpriority="high">
    @endif
    <div class="block-hero__text">
        <h1>{{ $data['heading'] }}</h1>
        @if ($data['intro'])<p>{{ $data['intro'] }}</p>@endif
    </div>
</section>
