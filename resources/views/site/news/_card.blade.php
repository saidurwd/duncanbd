<article class="card-item">
    @if ($post->featured?->getFirstMedia('file'))
        <img src="{{ $post->featured->getFirstMediaUrl('file', 'medium') ?: $post->featured->getFirstMediaUrl('file') }}" alt="{{ $post->featured->altAttribute() }}" loading="lazy">
    @endif
    <p class="meta"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->timezone('Asia/Dhaka')->format('F Y') }}</time>@if ($post->category) · {{ $post->category->name }}@endif</p>
    @php($tag = 'h'.($level ?? 3))
    <{{ $tag }}><a href="{{ $post->url() }}">{{ $post->title }}</a></{{ $tag }}>
    @if ($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
</article>
