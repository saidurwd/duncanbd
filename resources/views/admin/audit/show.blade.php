@extends('layouts.admin')

@section('title', 'Audit entry #'.$entry->id)

@section('content')
    @php
        $changes = $entry->attribute_changes ?? collect();
        $new = (array) ($changes['attributes'] ?? []);
        $old = (array) ($changes['old'] ?? []);
        $extra = collect($entry->properties ?? [])->except(['attributes', 'old'])->all();
        $show = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'true' : 'false') : (string) ($v ?? '—'));
    @endphp
    <a href="{{ route('admin.audit.audit') }}" class="btn btn-link px-0 mb-2">&larr; Back to the audit log</a>
    <div class="card mb-3"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">When</dt><dd class="col-sm-9">{{ $entry->created_at->timezone('Asia/Dhaka')->format('d M Y H:i:s') }} (Dhaka)</dd>
        <dt class="col-sm-3">User</dt><dd class="col-sm-9">{{ $entry->causer?->name ?? 'System' }} @if ($entry->causer?->email)<span class="text-muted">({{ $entry->causer->email }})</span>@endif</dd>
        <dt class="col-sm-3">Area</dt><dd class="col-sm-9">{{ $entry->log_name }}</dd>
        <dt class="col-sm-3">Action</dt><dd class="col-sm-9">{{ $entry->description }}</dd>
        <dt class="col-sm-3">Record</dt><dd class="col-sm-9">{{ App\Domain\Audit\AuditLog::subjectLabel($entry) }}</dd>
        @if ($entry->getProperty('ip'))<dt class="col-sm-3">IP address</dt><dd class="col-sm-9">{{ $entry->getProperty('ip') }}</dd>@endif
    </dl></div></div>

    @if ($new || $old)
        <div class="card mb-3"><div class="card-header"><h2 class="card-title h5">What changed</h2></div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th scope="col">Field</th><th scope="col">Before</th><th scope="col">After</th></tr></thead>
            <tbody>
            @foreach (array_unique([...array_keys($new), ...array_keys($old)]) as $field)
                <tr><th scope="row">{{ $field }}</th><td class="text-break">@if (array_key_exists($field, $old)){{ \Illuminate\Support\Str::limit($show($old[$field]), 300) }}@else<span class="text-muted">—</span>@endif</td><td class="text-break">{{ \Illuminate\Support\Str::limit($show($new[$field] ?? null), 300) }}</td></tr>
            @endforeach
            </tbody>
        </table></div></div>
    @endif

    @if ($extra)
        <div class="card"><div class="card-header"><h2 class="card-title h5">Details</h2></div><div class="card-body"><dl class="row mb-0">
            @foreach ($extra as $key => $value)<dt class="col-sm-3">{{ $key }}</dt><dd class="col-sm-9 text-break">{{ \Illuminate\Support\Str::limit($show($value), 500) }}</dd>@endforeach
        </dl></div></div>
    @elseif (! $new && ! $old)
        <p class="text-muted">This entry carries no further detail.</p>
    @endif
@endsection
