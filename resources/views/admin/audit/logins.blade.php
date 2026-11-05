@extends('layouts.admin')

@section('title', 'Login history')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([['Successful sign-ins, 24 h', $stats['ok24'], 'success'], ['Failed attempts, 24 h', $stats['failed24'], $stats['failed24'] > 0 ? 'warning' : 'secondary'], ['Distinct IP addresses, 7 days', $stats['ips7'], 'info']] as [$label, $value, $color])
            <div class="col-md-4"><div class="card text-bg-{{ $color }} h-100"><div class="card-body"><div class="fs-2 fw-bold">{{ number_format($value) }}</div>{{ $label }}</div></div></div>
        @endforeach
    </div>
    @include('admin.audit._filters', ['action' => route('admin.audit.logins'), 'users' => $users, 'outcomes' => true])
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Sign-in history, newest first</caption>
        <thead><tr><th scope="col">When (Dhaka)</th><th scope="col">User</th><th scope="col">Outcome</th><th scope="col">IP address</th><th scope="col">Browser</th></tr></thead>
        <tbody>
        @forelse ($entries as $a)
            <tr>
                <td class="text-nowrap">{{ $a->created_at->timezone('Asia/Dhaka')->format('d M Y H:i:s') }}</td>
                <td>{{ $a->causer?->name ?? ($a->getProperty('email') ?: 'Unknown') }}@if ($a->causer)<div class="small text-muted">{{ $a->causer->email }}</div>@endif</td>
                <td>@switch($a->description)
                    @case('login')<span class="badge text-bg-success">Signed in</span>@break
                    @case('logout')<span class="badge text-bg-secondary">Signed out</span>@break
                    @default<span class="badge text-bg-danger">Failed</span>
                @endswitch</td>
                <td>{{ $a->getProperty('ip') ?? '—' }}</td>
                <td>{{ $ua($a->getProperty('user_agent')) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No sign-ins match these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $entries->links() }}</div>
@endsection
