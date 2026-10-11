<ul class="doc-list">
    @forelse ($documents as $doc)
        <li>
            <a href="{{ route('documents.show', [$doc->category->slug, $doc->slug]) }}">{{ $doc->title }}</a>
            <span class="doc-meta">
                @if ($doc->document_date){{ $doc->document_date->format('j F Y') }} · @endif
                {{ $doc->fileTypeLabel() }}, {{ $doc->sizeLabel() }}
            </span>
            <a href="{{ route('documents.download', $doc->uuid) }}" class="button" download>Download <span class="visually-hidden">{{ $doc->title }} ({{ $doc->fileTypeLabel() }}, {{ $doc->sizeLabel() }})</span></a>
        </li>
    @empty
        <li>No documents found.</li>
    @endforelse
</ul>
