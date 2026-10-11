@extends('layouts.admin')

@section('title', 'News & press')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <label for="q" class="visually-hidden">Search</label>
            <input id="q" name="q" value="{{ $search }}" placeholder="Search titles" class="form-control w-auto">
            <label for="type" class="visually-hidden">Type</label>
            <select id="type" name="type" class="form-select w-auto">
                <option value="">All types</option>
                @foreach (App\Domain\News\Enums\PostType::cases() as $t)<option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>@endforeach
            </select>
            <button class="btn btn-outline-secondary">Filter</button>
        </form>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">New post</a>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Title</th><th>Type</th><th>Category</th><th>Status</th><th>Published</th><th>Author</th></tr></thead>
        <tbody>
        @forelse ($posts as $post)
            <tr>
                <td><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a></td>
                <td>{{ $post->type->label() }}</td>
                <td>{{ $post->category?->name }}</td>
                <td><span class="badge text-bg-secondary">{{ ucfirst($post->status->value) }}</span></td>
                <td>{{ $post->published_at?->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
                <td>{{ $post->author?->name }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">No posts yet.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $posts->links() }}</div>
@endsection
