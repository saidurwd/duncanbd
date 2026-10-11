@php($poster = $data['poster_media_id'] ? $media->get($data['poster_media_id']) : null)
<section class="block block-video">
    <div class="container">
        @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
        <button type="button" class="video-facade" data-video="{{ $data['youtube_id'] }}" data-title="{{ $data['heading'] ?: 'Video' }}"
                @if ($poster) data-poster="{{ $poster->getFirstMediaUrl('file', 'large') }}" @endif>
            <span>Play video{{ $data['heading'] ? ': '.$data['heading'] : '' }}</span>
        </button>
        <noscript><p><a href="https://www.youtube-nocookie.com/embed/{{ $data['youtube_id'] }}" rel="noopener">Watch the video</a></p></noscript>
    </div>
</section>
