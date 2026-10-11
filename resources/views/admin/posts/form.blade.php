@extends('layouts.admin')

@section('title', $post->exists ? 'Edit post' : 'New post')

@section('content')
    @if ($post->exists)
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <span class="badge text-bg-secondary fs-6">{{ ucfirst($post->status->value) }}</span>
            @can('pages.publish')
                @if ($post->status->value !== 'published')
                    <form method="POST" action="{{ route('admin.posts.publish', $post) }}" class="d-flex gap-2 align-items-center">
                        @csrf
                        <label for="pub-at" class="visually-hidden">Publish at (Asia/Dhaka)</label>
                        <input id="pub-at" type="datetime-local" name="published_at" class="form-control form-control-sm w-auto">
                        <button class="btn btn-sm btn-outline-primary">Publish / schedule</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.posts.unpublish', $post) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Archive</button></form>
                @endif
            @endcan
        </div>
    @endif
    <form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" class="row g-3">
        @csrf
        @if ($post->exists) @method('PUT') @endif
        <div class="col-md-8"><label for="title" class="form-label">Title</label><input id="title" name="title" value="{{ old('title', $post->title) }}" class="form-control" required></div>
        <div class="col-md-2">
            <label for="type" class="form-label">Type</label>
            <select id="type" name="type" class="form-select">
                @foreach (App\Domain\News\Enums\PostType::cases() as $t)<option value="{{ $t->value }}" @selected(old('type', $post->type->value) === $t->value)>{{ $t->label() }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="category_id" class="form-label">Category</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">None</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) old('category_id', $post->category_id) === $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-12"><label for="excerpt" class="form-label">Excerpt</label><textarea id="excerpt" name="excerpt" rows="2" class="form-control" maxlength="400">{{ old('excerpt', $post->excerpt) }}</textarea></div>
        <div class="col-12"><label for="body" class="form-label">Body (HTML: h2–h4, p, lists, links, tables)</label><textarea id="body" name="body" rows="12" class="form-control">{{ old('body', $post->body) }}</textarea></div>
        <div class="col-md-4"><label for="featured_media_id" class="form-label">Featured image (media ID)</label><input id="featured_media_id" type="number" name="featured_media_id" value="{{ old('featured_media_id', $post->featured_media_id) }}" class="form-control"></div>
        <div class="col-md-4"><label for="tags" class="form-label">Tags (comma separated)</label><input id="tags" name="tags" value="{{ old('tags', implode(', ', $post->tags ?? [])) }}" class="form-control"></div>
        <div class="col-md-4"><label for="slug" class="form-label">Slug</label><input id="slug" name="slug" value="{{ old('slug', $post->slug) }}" class="form-control" placeholder="auto from title"></div>
        <div class="col-md-6"><label for="seo_title" class="form-label">Meta title</label><input id="seo_title" name="seo[title]" value="{{ old('seo.title', $post->seo['title'] ?? '') }}" class="form-control" maxlength="70"></div>
        <div class="col-md-6"><label for="seo_desc" class="form-label">Meta description</label><input id="seo_desc" name="seo[description]" value="{{ old('seo.description', $post->seo['description'] ?? '') }}" class="form-control" maxlength="170"></div>
        <div class="col-12"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.posts.index') }}" class="btn btn-link">Back</a></div>
    </form>
    <form method="POST" action="{{ route('admin.posts.categories.store') }}" class="row g-2 mt-4">
        @csrf
        <div class="col-auto"><label for="cat-name" class="form-label mb-0 small">New category</label><input id="cat-name" name="name" class="form-control form-control-sm" required></div>
        <div class="col-auto d-flex align-items-end"><button class="btn btn-sm btn-outline-secondary">Add</button></div>
    </form>
@endsection
