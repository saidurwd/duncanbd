@php($slides = collect($data['slides'] ?? [])->map(fn ($s) => ['media' => $media->get($s['media_id']), 'caption' => $s['caption'] ?? null])->filter(fn ($s) => $s['media'])->values())
<section class="block-hero" @if ($slides->count() > 1) data-hero-slider @endif aria-roledescription="carousel" aria-label="Featured images">
    <div class="block-hero__slides">
        @foreach ($slides as $i => $slide)
            <div class="block-hero__slide @if ($i === 0) is-active @endif" data-slide>
                <img src="{{ $slide['media']->getFirstMediaUrl('file', 'large') ?: $slide['media']->getFirstMediaUrl('file') }}"
                     alt="{{ $slide['media']->altAttribute() }}" width="2400" height="1350"
                     @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
            </div>
        @endforeach
    </div>
    <div class="container block-hero__text">
        <h1>{{ $data['heading'] }}</h1>
        @if ($data['intro'])<p>{{ $data['intro'] }}</p>@endif
    </div>
    @if ($slides->count() > 1)<button type="button" class="block-hero__pause" data-slider-pause aria-pressed="false">Pause slideshow</button>@endif
</section>
