@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')

@section('content')
    @if ($page->exists)
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <span class="badge text-bg-secondary fs-6">{{ $page->status->label() }}</span>
            <a href="{{ route('admin.pages.preview', $page) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Preview</a>
            <a href="{{ route('admin.pages.revisions', $page) }}" class="btn btn-sm btn-outline-secondary">Revisions</a>
            @foreach ($nextStatuses as $next)
                @if ($next !== App\Domain\Cms\Enums\PageStatus::Scheduled)
                    <form method="POST" action="{{ route('admin.pages.transition', $page) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $next->value }}">
                        <button class="btn btn-sm btn-outline-primary">{{ $next->label() }}</button>
                    </form>
                @endif
            @endforeach
        </div>
        @if ($nextStatuses->contains(App\Domain\Cms\Enums\PageStatus::Scheduled))
            <form method="POST" action="{{ route('admin.pages.transition', $page) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <input type="hidden" name="status" value="scheduled">
                <div class="col-auto">
                    <label for="published_at" class="form-label small mb-0">Publish at (Asia/Dhaka)</label>
                    <input id="published_at" type="datetime-local" name="published_at" class="form-control form-control-sm" required>
                </div>
                <div class="col-auto">
                    <label for="unpublish_at" class="form-label small mb-0">Unpublish at (optional)</label>
                    <input id="unpublish_at" type="datetime-local" name="unpublish_at" class="form-control form-control-sm">
                </div>
                <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Schedule</button></div>
            </form>
        @endif
    @endif

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($page->exists) @method('PUT') @endif
        <div class="card mb-3"><div class="card-body row g-3">
            <div class="col-md-6">
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" value="{{ old('title', $page->title) }}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="slug" class="form-label">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" class="form-control" placeholder="auto from title">
            </div>
            <div class="col-md-3">
                <label for="template" class="form-label">Template</label>
                <select id="template" name="template" class="form-select">
                    @foreach (['default', 'wide'] as $t)<option value="{{ $t }}" @selected(old('template', $page->template) === $t)>{{ ucfirst($t) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="parent_id" class="form-label">Parent page</label>
                <select id="parent_id" name="parent_id" class="form-select">
                    <option value="">None (top level)</option>
                    @foreach ($parents as $parent)<option value="{{ $parent->id }}" @selected((int) old('parent_id', $page->parent_id) === $parent->id)>/{{ $parent->path }}</option>@endforeach
                </select>
            </div>
        </div></div>

        <h2 class="h5">Content blocks</h2>
        <div id="block-editor" data-block-editor data-schema="{{ json_encode($blockSchema) }}" data-initial="{{ json_encode($initialBlocks) }}">
            <input type="hidden" name="blocks_json" value="{{ old('blocks_json') }}">
            <div data-block-list></div>
            <div class="d-flex gap-2 mb-4">
                <label for="block-type" class="visually-hidden">Block type</label>
                <select id="block-type" data-block-type class="form-select w-auto">
                    @foreach ($blockSchema as $type => $def)<option value="{{ $type }}">{{ $def['label'] }}</option>@endforeach
                </select>
                <button type="button" data-add-block class="btn btn-outline-primary">Add block</button>
            </div>
        </div>

        <div class="card mb-3"><div class="card-header"><h2 class="card-title">SEO</h2></div><div class="card-body row g-3">
            <div class="col-md-6">
                <label for="seo_title" class="form-label">Meta title <span class="text-muted small">(≤ 70)</span></label>
                <input id="seo_title" name="seo[title]" value="{{ old('seo.title', $page->seo['title'] ?? '') }}" class="form-control" maxlength="70">
            </div>
            <div class="col-md-6">
                <label for="seo_desc" class="form-label">Meta description <span class="text-muted small">(≤ 170)</span></label>
                <textarea id="seo_desc" name="seo[description]" rows="2" class="form-control" maxlength="170">{{ old('seo.description', $page->seo['description'] ?? '') }}</textarea>
            </div>
        </div></div>

        <div class="mb-3">
            <label for="note" class="form-label">Change note (optional)</label>
            <input id="note" name="note" class="form-control" maxlength="255">
        </div>
        <button class="btn btn-primary">Save</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-link">Back</a>
    </form>
@endsection
