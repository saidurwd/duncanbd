@extends('layouts.admin')

@section('title', $document->exists ? 'Edit document' : 'Upload document')

@section('content')
    @if ($document->exists)
        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="badge text-bg-secondary fs-6">{{ ucfirst($document->status->value) }}</span>
            @if ($document->status->value !== 'published')
                <form method="POST" action="{{ route('admin.documents.publish', $document) }}">@csrf<button class="btn btn-sm btn-outline-primary">Publish</button></form>
            @else
                <form method="POST" action="{{ route('admin.documents.unpublish', $document) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Archive</button></form>
            @endif
            @if ($document->visibility->value === 'public' && $document->isLive())
                <a href="{{ route('documents.show', [$document->category->slug, $document->slug]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-link">View on site</a>
            @endif
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ $document->exists ? route('admin.documents.update', $document) : route('admin.documents.store') }}" class="row g-3">
        @csrf
        @if ($document->exists) @method('PUT') @endif
        <div class="col-md-8">
            <label for="title" class="form-label">Title</label>
            <input id="title" name="title" value="{{ old('title', $document->title) }}" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label for="category_id" class="form-label">Category</label>
            <select id="category_id" name="category_id" class="form-select" required>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) old('category_id', $document->category_id) === $c->id)>{{ $c->name }}{{ $c->is_investor ? ' (investor)' : '' }}</option>@endforeach
            </select>
        </div>
        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $document->description) }}</textarea>
        </div>
        <div class="col-md-3">
            <label for="financial_year" class="form-label">Financial year</label>
            <input id="financial_year" name="financial_year" value="{{ old('financial_year', $document->financial_year) }}" class="form-control" placeholder="2025 or 2025-2026">
        </div>
        <div class="col-md-3">
            <label for="document_date" class="form-label">Document date</label>
            <input id="document_date" type="date" name="document_date" value="{{ old('document_date', $document->document_date?->format('Y-m-d')) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="visibility" class="form-label">Visibility</label>
            <select id="visibility" name="visibility" class="form-select">
                @foreach (['public', 'private'] as $v)<option value="{{ $v }}" @selected(old('visibility', $document->visibility->value) === $v)>{{ ucfirst($v) }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="cover_media_id" class="form-label">Cover image (media ID)</label>
            <input id="cover_media_id" type="number" name="cover_media_id" value="{{ old('cover_media_id', $document->cover_media_id) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="published_at" class="form-label">Publish from</label>
            <input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', $document->published_at?->timezone('Asia/Dhaka')->format('Y-m-d\TH:i')) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="expires_at" class="form-label">Expires at</label>
            <input id="expires_at" type="datetime-local" name="expires_at" value="{{ old('expires_at', $document->expires_at?->timezone('Asia/Dhaka')->format('Y-m-d\TH:i')) }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label for="file" class="form-label">{{ $document->exists ? 'Upload a new version' : 'File' }} (PDF, DOCX, XLSX, PPTX · max 50 MB)</label>
            <input id="file" type="file" name="file" class="form-control" accept=".pdf,.docx,.xlsx,.pptx" @required(! $document->exists)>
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.documents.index') }}" class="btn btn-link">Back</a>
        </div>
    </form>

    @if ($document->exists)
        <h2 class="h5 mt-4">Versions</h2>
        <div class="card"><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Version</th><th>File</th><th>Size</th><th>SHA-256</th><th>Uploaded</th><th></th></tr></thead>
            <tbody>
            @foreach ($document->versions as $v)
                <tr>
                    <td>v{{ $v->version }} @if ($v->is_current)<span class="badge text-bg-success">current</span>@endif</td>
                    <td>{{ $v->original_name }}</td>
                    <td>{{ Illuminate\Support\Number::fileSize($v->size) }}</td>
                    <td><code class="small">{{ substr($v->checksum, 0, 16) }}…</code></td>
                    <td>{{ $v->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('admin.documents.download', [$document, $v->version]) }}" class="btn btn-sm btn-outline-secondary">Download</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div></div>
        <form method="POST" action="{{ route('admin.documents.destroy', $document) }}" class="mt-3">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Move to trash</button></form>
    @endif
@endsection
