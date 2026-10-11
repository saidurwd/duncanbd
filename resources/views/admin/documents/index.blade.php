@extends('layouts.admin')

@section('title', 'Documents')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <label for="q" class="visually-hidden">Search</label>
            <input id="q" name="q" value="{{ $search }}" placeholder="Search titles" class="form-control w-auto">
            <label for="category" class="visually-hidden">Category</label>
            <select id="category" name="category" class="form-select w-auto">
                <option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) request('category') === $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <label for="status" class="visually-hidden">Status</label>
            <select id="status" name="status" class="form-select w-auto">
                <option value="">Any status</option>
                @foreach (App\Domain\Documents\Enums\DocumentStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ ucfirst($s->value) }}</option>@endforeach
            </select>
            <button class="btn btn-outline-secondary">Filter</button>
        </form>
        <div class="d-flex gap-2">
            @can('documents.investor.manage')<a href="{{ route('admin.documents.categories.index') }}" class="btn btn-outline-secondary">Categories</a>@endcan
            <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">Upload document</a>
        </div>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Title</th><th>Category</th><th>Year</th><th>Status</th><th>Access</th><th>Downloads</th></tr></thead>
        <tbody>
        @forelse ($documents as $doc)
            <tr>
                <td><a href="{{ route('admin.documents.edit', $doc) }}">{{ $doc->title }}</a>
                    <div class="small text-muted">{{ $doc->currentVersion ? strtoupper(pathinfo($doc->currentVersion->original_name, PATHINFO_EXTENSION)).' · v'.$doc->currentVersion->version : 'no file' }}</div></td>
                <td>{{ $doc->category->name }}</td>
                <td>{{ $doc->financial_year }}</td>
                <td><span class="badge text-bg-secondary">{{ ucfirst($doc->status->value) }}</span></td>
                <td>{{ ucfirst($doc->visibility->value) }}</td>
                <td>{{ $doc->download_count }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">No documents.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $documents->links() }}</div>
@endsection
