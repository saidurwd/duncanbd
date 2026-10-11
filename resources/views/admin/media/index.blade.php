@extends('layouts.admin')

@section('title', 'Media library')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <form method="GET" class="d-flex gap-2">
                <label for="q" class="visually-hidden">Search</label>
                <input id="q" name="q" value="{{ $search }}" placeholder="Search by title" class="form-control">
                <label for="folder" class="visually-hidden">Folder</label>
                <select id="folder" name="folder" class="form-select w-auto">
                    <option value="">All folders</option>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder->id }}" @selected($folderId === $folder->id)>{{ $folder->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary">Filter</button>
            </form>
        </div>
        @can('media.manage')
            <div class="col-lg-4">
                <form method="POST" action="{{ route('admin.media.folders.store') }}" class="d-flex gap-2">
                    @csrf
                    <label for="folder-name" class="visually-hidden">New folder name</label>
                    <input id="folder-name" name="name" placeholder="New folder" class="form-control" required>
                    <button class="btn btn-outline-primary">Add</button>
                </form>
            </div>
        @endcan
    </div>

    @can('media.manage')
        <div class="card mb-4">
            <div class="card-header"><h2 class="card-title">Upload images</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="files" class="form-label">Files (JPEG, PNG, WebP, AVIF · max 10 MB)</label>
                        <input id="files" type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,image/avif" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="alt_text" class="form-label">Alt text</label>
                        <input id="alt_text" name="alt_text" value="{{ old('alt_text') }}" class="form-control">
                        <div class="form-check mt-1">
                            <input id="is_decorative" type="checkbox" name="is_decorative" value="1" class="form-check-input" @checked(old('is_decorative'))>
                            <label for="is_decorative" class="form-check-label">Decorative (no alt text needed)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="upload-folder" class="form-label">Folder</label>
                        <select id="upload-folder" name="folder_id" class="form-select">
                            <option value="">No folder</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="credit" class="form-label">Credit / photographer</label>
                        <input id="credit" name="credit" class="form-control">
                    </div>
                    <div class="col-12"><button class="btn btn-primary">Upload</button></div>
                </form>
            </div>
        </div>
    @endcan

    <div class="row g-3">
        @forelse ($items as $item)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100">
                    @if ($item->getFirstMedia('file'))
                        <img src="{{ $item->getFirstMediaUrl('file', 'thumb') ?: $item->getFirstMediaUrl('file') }}" alt="{{ $item->altAttribute() }}" class="card-img-top" loading="lazy">
                    @endif
                    <div class="card-body p-2">
                        <div class="small fw-semibold text-truncate">{{ $item->title }}</div>
                        @if (! $item->is_decorative && ! $item->alt_text)<span class="badge text-bg-warning">No alt text</span>@endif
                        @can('media.manage')<a href="{{ route('admin.media.edit', $item) }}" class="stretched-link small">Edit</a>@endcan
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No media yet.</p>
        @endforelse
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
@endsection
