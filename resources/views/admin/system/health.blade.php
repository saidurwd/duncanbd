@extends('layouts.admin')

@section('title', 'System health')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0">Checked {{ $ranAt->timezone('Asia/Dhaka')->format('d M Y H:i:s') }} (Dhaka). Checks are read-only.</p>
        <a href="{{ route('admin.system.health') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Run checks again</a>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card text-bg-success h-100"><div class="card-body"><div class="fs-2 fw-bold">{{ $summary['ok'] }}</div>Passing</div></div></div>
        <div class="col-md-4"><div class="card text-bg-warning h-100"><div class="card-body"><div class="fs-2 fw-bold">{{ $summary['warn'] }}</div>Need attention</div></div></div>
        <div class="col-md-4"><div class="card {{ $summary['fail'] ? 'text-bg-danger' : 'text-bg-secondary' }} h-100"><div class="card-body"><div class="fs-2 fw-bold">{{ $summary['fail'] }}</div>Failing</div></div></div>
    </div>
    @foreach ($groups as $group => $checks)
        <div class="card mb-3">
            <div class="card-header"><h2 class="card-title h5">{{ $group }}</h2></div>
            <ul class="list-group list-group-flush">
                @foreach ($checks as $c)
                    <li class="list-group-item">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div><strong>{{ $c['name'] }}</strong><div class="text-muted">{{ $c['detail'] }}</div></div>
                            <div><span class="badge text-bg-{{ ['ok' => 'success', 'warn' => 'warning', 'fail' => 'danger'][$c['status']] }}">{{ ['ok' => 'OK', 'warn' => 'Attention', 'fail' => 'Failing'][$c['status']] }}</span></div>
                        </div>
                        @if ($c['hint'])<div class="small mt-1"><i class="bi bi-lightbulb" aria-hidden="true"></i> {{ $c['hint'] }}</div>@endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
@endsection
