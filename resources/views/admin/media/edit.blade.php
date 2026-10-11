@extends('layouts.admin')

@section('title', 'Edit media')

@section('content')
    <div class="row g-4">
        <div class="col-md-5">
            @if ($item->getFirstMedia('file'))
                <img src="{{ $item->getFirstMediaUrl('file', 'medium') ?: $item->getFirstMediaUrl('file') }}" alt="{{ $item->altAttribute() }}" class="img-fluid rounded">
            @endif
        </div>
        <div class="col-md-7">
            <form method="POST" action="{{ route('admin.media.update', $item) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input id="title" name="title" value="{{ old('title', $item->title) }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="alt_text" class="form-label">Alt text</label>
                    <input id="alt_text" name="alt_text" value="{{ old('alt_text', $item->alt_text) }}" class="form-control">
                    <div class="form-check mt-1">
                        <input id="is_decorative" type="checkbox" name="is_decorative" value="1" class="form-check-input" @checked(old('is_decorative', $item->is_decorative))>
                        <label for="is_decorative" class="form-check-label">Decorative (no alt text needed)</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="caption" class="form-label">Caption</label>
                    <input id="caption" name="caption" value="{{ old('caption', $item->caption) }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="credit" class="form-label">Credit</label>
                    <input id="credit" name="credit" value="{{ old('credit', $item->credit) }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="folder_id" class="form-label">Folder</label>
                    <select id="folder_id" name="folder_id" class="form-select">
                        <option value="">No folder</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected((int) old('folder_id', $item->folder_id) === $folder->id)>{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.media.index') }}" class="btn btn-link">Cancel</a>
            </form>
            <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">Move to trash</button>
            </form>
        </div>
    </div>
@endsection
