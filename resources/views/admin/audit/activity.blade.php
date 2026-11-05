@extends('layouts.admin')

@section('title', 'Activity log')

@section('content')
    <p class="text-muted">Everything people did in the admin, sign-ins included: {{ number_format($today) }} actions today.</p>
    <div class="row g-3 mb-3">
        <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h2 class="card-title h5">Most active, last 30 days</h2></div>
            <ul class="list-group list-group-flush">
                @forelse ($summary as $s)
                    <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $s['name'] }}<span class="d-block small text-muted">last active {{ $s['last']->timezone('Asia/Dhaka')->diffForHumans() }}</span></span><span class="badge text-bg-primary rounded-pill">{{ number_format($s['actions']) }}</span></li>
                @empty
                    <li class="list-group-item text-muted">No activity yet.</li>
                @endforelse
            </ul></div></div>
        <div class="col-lg-8">
            @include('admin.audit._filters', ['action' => route('admin.audit.activity'), 'users' => $users, 'modules' => $modules])
        </div>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Activity, newest first</caption>
        <thead><tr><th scope="col">When (Dhaka)</th><th scope="col">User</th><th scope="col">Area</th><th scope="col">Activity</th><th scope="col">Record</th></tr></thead>
        <tbody>
        @forelse ($entries as $a)
            <tr>
                <td class="text-nowrap">{{ $a->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
                <td>{{ $a->causer?->name ?? ($a->getProperty('email') ?: 'System') }}</td>
                <td><span class="badge text-bg-secondary">{{ $a->log_name }}</span></td>
                <td>{{ $a->description }}</td>
                <td>{{ App\Domain\Audit\AuditLog::subjectLabel($a) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No activity matches these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $entries->links() }}</div>
@endsection
