@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex gap-2">
            <label for="q" class="visually-hidden">Search</label>
            <input id="q" name="q" value="{{ $search }}" placeholder="Search titles" class="form-control">
            <label for="status" class="visually-hidden">Status</label>
            <select id="status" name="status" class="form-select w-auto">
                <option value="">All statuses</option>
                @foreach (App\Domain\Cms\Enums\PageStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-secondary">Filter</button>
        </form>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">New page</a>
    </div>
    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Title</th><th>Path</th><th>Status</th><th>Author</th><th>Updated</th></tr></thead>
            <tbody>
            @forelse ($pages as $page)
                <tr>
                    <td><a href="{{ route('admin.pages.edit', $page) }}">{{ $page->title }}</a></td>
                    <td><code>/{{ $page->path }}</code></td>
                    <td><span class="badge text-bg-secondary">{{ $page->status->label() }}</span></td>
                    <td>{{ $page->author?->name }}</td>
                    <td>{{ $page->updated_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No pages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $pages->links() }}</div>
@endsection
