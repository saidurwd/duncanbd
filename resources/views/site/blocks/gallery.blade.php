<section class="block block-gallery" data-gallery>
    <div class="container">
        @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
        <div class="gallery">
            @foreach ($data['items'] ?? [] as $row)
                @php($image = $media->get($row['media_id']))
                @if ($image)
                    <button type="button" data-full="{{ $image->getFirstMediaUrl('file', 'large') ?: $image->getFirstMediaUrl('file') }}" data-alt="{{ $image->altAttribute() }}">
                        <img src="{{ $image->getFirstMediaUrl('file', 'medium') ?: $image->getFirstMediaUrl('file') }}" alt="{{ $image->altAttribute() ?: 'Enlarge image' }}" width="1200" height="900" loading="lazy">
                    </button>
                @endif
            @endforeach
        </div>
        <dialog class="lightbox" aria-label="Image viewer"><img src="" alt=""><p data-caption></p><button type="button" class="button" data-close>Close</button></dialog>
    </div>
</section>
