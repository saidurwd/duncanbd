@inject('documentQuery', 'App\Domain\Documents\DocumentQuery')
@php($documents = $documentQuery->latest($data['category'] ?? null, (int) ($data['limit'] ?: 5)))
<section class="block block-documents">
    <div class="container">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    @include('site.documents._list', ['documents' => $documents])
    </div>
</section>
