@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        @foreach ($boxes as $box)
            <div class="col-6 col-lg-3">
                <div class="small-box text-bg-{{ $box['color'] }}">
                    <div class="inner"><h3>{{ number_format($box['value']) }}</h3><p>{{ $box['label'] }}</p></div>
                    <i class="bi {{ $box['icon'] }} small-box-icon" aria-hidden="true"></i>
                    <a href="{{ $box['route'] }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">View <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7">
            @if ($drafts->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header"><h2 class="card-title">Drafts and pages in review</h2></div>
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        <tbody>
                        @foreach ($drafts as $page)
                            <tr><td><a href="{{ route('admin.pages.edit', $page) }}">{{ $page->title }}</a></td><td><span class="badge text-bg-secondary">{{ $page->status->label() }}</span></td><td class="text-muted small">{{ $page->updated_at->timezone('Asia/Dhaka')->diffForHumans() }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </div>
            @endif
            @if ($activity->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header"><h2 class="card-title">Recent activity</h2></div>
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        <tbody>
                        @foreach ($activity as $a)
                            <tr><td class="text-nowrap"><span class="badge text-bg-light border">{{ $a->log_name }}</span></td><td>{{ $a->description }}@if ($a->subject_type) <span class="text-muted">· {{ Str::headline(class_basename($a->subject_type)) }}</span>@endif</td><td class="text-muted small">{{ $a->causer?->name ?? 'System' }}</td><td class="text-muted small text-nowrap">{{ $a->created_at->timezone('Asia/Dhaka')->diffForHumans() }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </div>
            @endif
        </div>
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h2 class="card-title">Quick actions</h2></div>
                <div class="list-group list-group-flush">
                    @forelse ($actions as [$label, $url, $icon])
                        <a href="{{ $url }}" class="list-group-item list-group-item-action"><i class="bi {{ $icon }} me-2"></i>{{ $label }}</a>
                    @empty
                        <div class="list-group-item text-muted">Nothing to do yet. Ask a Super Admin for access.</div>
                    @endforelse
                    <a href="{{ url('/') }}" class="list-group-item list-group-item-action" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2"></i>View the website<span class="visually-hidden"> (opens in a new tab)</span></a>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Welcome, {{ auth()->user()->name }}</h2></div>
                <div class="card-body small text-muted">Your role decides what you see here. Pages and news move through Draft → In review → Approved → Published; nothing is public until it is published.</div>
            </div>
        </div>
    </div>
@endsection
