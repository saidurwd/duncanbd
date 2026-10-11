@extends('layouts.admin')

@section('title', 'Enquiries')

@section('content')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <label for="q" class="visually-hidden">Search</label>
        <input id="q" name="q" value="{{ $search }}" placeholder="Name or email" class="form-control w-auto">
        <label for="category" class="visually-hidden">Category</label>
        <select id="category" name="category" class="form-select w-auto"><option value="">All categories</option>
            @foreach (App\Domain\Engagement\Enums\EnquiryCategory::cases() as $c)<option value="{{ $c->value }}" @selected(request('category') === $c->value)>{{ $c->label() }}</option>@endforeach</select>
        <label for="status" class="visually-hidden">Status</label>
        <select id="status" name="status" class="form-select w-auto"><option value="">Any status</option>
            @foreach (App\Domain\Engagement\Enums\EnquiryStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">Filter</button>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>From</th><th>Category</th><th>Status</th><th>Assigned</th><th>Received (Dhaka)</th></tr></thead>
        <tbody>
        @forelse ($enquiries as $e)
            <tr>
                <td><a href="{{ route('admin.enquiries.show', $e) }}">{{ $e->name }}</a><div class="small text-muted">{{ $e->email }}</div></td>
                <td>{{ $e->category->label() }}</td>
                <td><span class="badge text-bg-secondary">{{ $e->status->label() }}</span></td>
                <td>{{ $e->assignee?->name }}</td>
                <td>{{ $e->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No enquiries.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $enquiries->links() }}</div>
@endsection
