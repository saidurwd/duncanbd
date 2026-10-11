@extends('layouts.admin')

@section('title', 'Subscribers')

@section('content')
    <div class="d-flex justify-content-between mb-3">
        <div>@foreach (App\Domain\Engagement\Enums\SubscriberStatus::cases() as $s)<a href="{{ route('admin.subscribers.index', ['status' => $s->value]) }}" class="badge text-bg-secondary text-decoration-none me-1">{{ ucfirst($s->value) }}: {{ $counts[$s->value] ?? 0 }}</a>@endforeach</div>
        @can('enquiries.manage')<a href="{{ route('admin.subscribers.export') }}" class="btn btn-outline-primary">Export confirmed (CSV)</a>@endcan
    </div>
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Email</th><th>Status</th><th>Consent</th><th>Confirmed</th></tr></thead>
        <tbody>@forelse ($subscribers as $s)<tr><td>{{ $s->email }}</td><td>{{ ucfirst($s->status->value) }}</td><td>{{ $s->consent_at?->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ $s->confirmed_at?->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No subscribers.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $subscribers->links() }}</div>
@endsection
