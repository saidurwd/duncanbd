@extends('layouts.admin')

@section('title', 'Vacancies')

@section('content')
    <div class="mb-3 d-flex gap-2"><a href="{{ route('admin.vacancies.create') }}" class="btn btn-primary">New vacancy</a><a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary">All applications</a></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Title</th><th>Status</th><th>Closing</th><th>Applications</th></tr></thead>
        <tbody>@forelse ($vacancies as $v)
            <tr><td><a href="{{ route('admin.vacancies.edit', $v) }}">{{ $v->title }}</a></td><td><span class="badge text-bg-secondary">{{ ucfirst($v->status->value) }}</span></td><td>{{ $v->closing_date?->format('d M Y') }}</td><td><a href="{{ route('admin.applications.index', ['vacancy' => $v->id]) }}">{{ $v->applications_count }}</a></td></tr>
        @empty<tr><td colspan="4" class="text-muted">No vacancies.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $vacancies->links() }}</div>
@endsection
