@extends('layouts.admin')

@section('title', 'Revisions — '.$page->title)

@section('content')
    <div class="card"><div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>When (Dhaka)</th><th>Who</th><th>Note</th><th></th></tr></thead>
            <tbody>
            @foreach ($revisions as $revision)
                <tr>
                    <td>{{ $revision->id }}</td>
                    <td>{{ $revision->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
                    <td>{{ $revision->user?->name }}</td>
                    <td>{{ $revision->note }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.pages.restore', [$page, $revision]) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">Restore</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $revisions->links() }}</div>
    <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-link mt-2">Back to page</a>
@endsection
