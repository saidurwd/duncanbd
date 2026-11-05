@extends('layouts.admin')

@section('title', 'Security events')

@section('content')
    @if ($suspects)
        <div class="alert alert-danger" role="alert">
            <strong>Possible password guessing in the last hour:</strong>
            @foreach ($suspects as $s){{ $s['who'] }} ({{ $s['count'] }} failures)@if (! $loop->last), @endif @endforeach.
            Check Login history, and consider resetting the password or two-factor of any targeted account.
        </div>
    @endif
    <div class="row g-3 mb-3">
        @foreach ([['Failed sign-ins, 24 h', $stats['failed24'], $stats['failed24'] > 0 ? 'warning' : 'secondary'], ['Lockouts, 7 days', $stats['lockouts7'], $stats['lockouts7'] > 0 ? 'danger' : 'secondary'], ['Password, role and two-factor changes, 30 days', $stats['changes30'], 'info']] as [$label, $value, $color])
            <div class="col-md-4"><div class="card text-bg-{{ $color }} h-100"><div class="card-body"><div class="fs-2 fw-bold">{{ number_format($value) }}</div>{{ $label }}</div></div></div>
        @endforeach
    </div>
    @include('admin.audit._filters', ['action' => route('admin.audit.events'), 'users' => $users])
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Security events, newest first</caption>
        <thead><tr><th scope="col">When (Dhaka)</th><th scope="col">Severity</th><th scope="col">Event</th><th scope="col">Account</th><th scope="col">Performed by</th><th scope="col">IP address</th></tr></thead>
        <tbody>
        @forelse ($entries as $a)
            @php($sev = App\Domain\Audit\AuditLog::severity($a))
            <tr>
                <td class="text-nowrap">{{ $a->created_at->timezone('Asia/Dhaka')->format('d M Y H:i:s') }}</td>
                <td><span class="badge text-bg-{{ $sev }}">{{ $sev === 'danger' ? 'High' : ($sev === 'warning' ? 'Review' : 'Info') }}</span></td>
                <td>{{ ucfirst($a->description) }}</td>
                <td>@if ($a->log_name === 'identity' && $a->subject_type){{ App\Domain\Audit\AuditLog::subjectLabel($a) }}@else{{ $a->getProperty('email') ?: ($a->causer?->email ?? '—') }}@endif</td>
                <td>{{ $a->causer?->name ?? '—' }}</td>
                <td>{{ $a->getProperty('ip') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No security events match these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $entries->links() }}</div>
@endsection
