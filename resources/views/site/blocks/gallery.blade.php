<section class="block block-gallery">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    <div class="gallery">
        @foreach ($data['items'] ?? [] as $row)
            @php($image = $media->get($row['media_id']))
            @if ($image)<img src="{{ $image->getFirstMediaUrl('file', 'medium') ?: $image->getFirstMediaUrl('file') }}" alt="{{ $image->altAttribute() }}" loading="lazy">@endif
        @endforeach
    </div>
</section>
