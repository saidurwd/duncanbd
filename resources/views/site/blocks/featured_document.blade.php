@inject('documentQuery', 'App\Domain\Documents\DocumentQuery')
@php($doc = $documentQuery->find((int) $data['document_id']))
@if ($doc)
    <section class="block block-featured-document">
        <div class="container">
            @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
            <article class="featured-doc">
                @if ($doc->cover?->getFirstMedia('file'))
                    <img class="featured-doc__cover" src="{{ $doc->cover->getFirstMediaUrl('file', 'medium') ?: $doc->cover->getFirstMediaUrl('file') }}" alt="{{ $doc->cover->altAttribute() }}" loading="lazy">
                @endif
                <div class="featured-doc__body">
                    <p class="meta">@if ($doc->financial_year)Financial year {{ $doc->financial_year }} · @endif{{ $doc->fileTypeLabel() }}, {{ $doc->sizeLabel() }}</p>
                    <h3>{{ $doc->title }}</h3>
                    @if ($doc->description)<p>{{ $doc->description }}</p>@endif
                    <p><a href="{{ route('documents.download', $doc->uuid) }}" class="button" download>Download the report <span class="visually-hidden">({{ $doc->fileTypeLabel() }}, {{ $doc->sizeLabel() }})</span></a>
                    <a href="{{ route('documents.category', $doc->category->slug) }}" class="button button--ghost">All {{ Str::lower($doc->category->name) }}</a></p>
                </div>
            </article>
        </div>
    </section>
@endif
