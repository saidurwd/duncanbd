@extends('layouts.admin')

@section('title', 'Applications')

@section('content')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="vacancy" value="{{ request('vacancy') }}">
        <label for="status" class="visually-hidden">Status</label>
        <select id="status" name="status" class="form-select w-auto"><option value="">Any status</option>@foreach (App\Domain\Careers\Enums\ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">Filter</button>
        <a href="{{ route('admin.applications.export', array_filter(['vacancy' => request('vacancy')])) }}" class="btn btn-outline-primary ms-auto">Export CSV</a>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Candidate</th><th>Vacancy</th><th>Status</th><th>Applied (Dhaka)</th></tr></thead>
        <tbody>@forelse ($applications as $a)
            <tr><td><a href="{{ route('admin.applications.show', $a) }}">{{ $a->name }}</a><div class="small text-muted">{{ $a->email }}</div></td><td>{{ $a->vacancy->title }}</td><td><span class="badge text-bg-secondary">{{ $a->status->label() }}</span></td><td>{{ $a->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td></tr>
        @empty<tr><td colspan="4" class="text-muted">No applications.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $applications->links() }}</div>
@endsection
