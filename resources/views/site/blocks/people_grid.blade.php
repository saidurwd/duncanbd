@inject('content', 'App\Domain\Content\ContentQuery')
<section class="block block-people">
    <div class="container">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    <div class="cards">
        @foreach ($content->people($data['group']) as $person)
            <article class="card-item">
                @if ($person->photo?->getFirstMedia('file'))
                    <img src="{{ $person->photo->getFirstMediaUrl('file', 'thumb') }}" alt="{{ $person->photo->altAttribute() }}" loading="lazy">
                @endif
                <h3>{{ $person->name }}</h3>
                @if ($person->position)<p class="meta">{{ $person->position }}</p>@endif
                @if ($person->bio)<details><summary>Biography</summary><div class="prose">{!! $person->bio !!}</div></details>@endif
            </article>
        @endforeach
    </div>
    </div>
</section>
