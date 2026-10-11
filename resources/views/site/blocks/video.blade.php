<section class="block block-video">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    {{-- Click-to-load: no third-party request until the visitor opts in (privacy-enhanced domain). --}}
    <a href="https://www.youtube-nocookie.com/embed/{{ $data['youtube_id'] }}" class="video-facade" rel="noopener" target="_blank">Watch the video</a>
</section>
