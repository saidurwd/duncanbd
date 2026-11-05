@extends('layouts.admin')

@section('title', 'Audit log')

@section('content')
    <p class="text-muted">A permanent record of who created, changed or deleted content and settings. Entries cannot be edited or removed from here.</p>
    @include('admin.audit._filters', ['action' => route('admin.audit.audit'), 'users' => $users, 'modules' => $modules, 'events' => true, 'export' => route('admin.audit.export', request()->query())])
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Audit log entries, newest first</caption>
        <thead><tr><th scope="col">When (Dhaka)</th><th scope="col">User</th><th scope="col">Area</th><th scope="col">Action</th><th scope="col">Record</th><th scope="col"><span class="visually-hidden">Details</span></th></tr></thead>
        <tbody>
        @forelse ($entries as $a)
            <tr>
                <td class="text-nowrap">{{ $a->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
                <td>{{ $a->causer?->name ?? 'System' }}</td>
                <td><span class="badge text-bg-secondary">{{ $a->log_name }}</span></td>
                <td>@php($color = match ($a->event) { 'created' => 'success', 'deleted' => 'danger', 'updated' => 'info', default => 'secondary' })<span class="badge text-bg-{{ $color }}">{{ $a->description }}</span></td>
                <td>{{ App\Domain\Audit\AuditLog::subjectLabel($a) }}</td>
                <td class="text-end"><a href="{{ route('admin.audit.show', $a) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No entries match these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $entries->links() }}</div>
@endsection
