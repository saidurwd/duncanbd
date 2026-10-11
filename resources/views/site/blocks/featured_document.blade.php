@inject('documentQuery', 'App\Domain\Documents\DocumentQuery')
@php($doc = $documentQuery->find((int) $data['document_id']))
@if ($doc)
    <section class="block block-featured-document">
        @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
        @if ($doc->cover?->getFirstMedia('file'))
            <img src="{{ $doc->cover->getFirstMediaUrl('file', 'thumb') }}" alt="{{ $doc->cover->altAttribute() }}" loading="lazy">
        @endif
        <h3>{{ $doc->title }}</h3>
        <a href="{{ route('documents.download', $doc->uuid) }}" class="button" download>Open report <span class="visually-hidden">({{ $doc->fileTypeLabel() }}, {{ $doc->sizeLabel() }})</span></a>
    </section>
@endif
