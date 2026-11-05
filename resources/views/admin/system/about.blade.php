@extends('layouts.admin')

@section('title', 'About')

@section('content')
    <div class="row g-3">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h2 class="card-title h5">This website</h2></div><div class="card-body"><dl class="row mb-0">
            @foreach ($application as $label => $value)<dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8 text-break">{{ $value }}</dd>@endforeach
        </dl></div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h2 class="card-title h5">Server</h2></div><div class="card-body"><dl class="row mb-0">
            @foreach ($server as $label => $value)<dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8 text-break">{{ $value }}</dd>@endforeach
        </dl></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-header"><h2 class="card-title h5">Key components</h2></div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th scope="col">Component</th><th scope="col">Used for</th><th scope="col">Version</th></tr></thead>
        <tbody>@foreach ($packages as $p)<tr><td><code>{{ $p['name'] }}</code></td><td>{{ $p['purpose'] }}</td><td>{{ $p['version'] }}</td></tr>@endforeach</tbody>
    </table></div></div>
    <p class="text-muted small mt-3">Quote the release and versions above when reporting a problem. Passwords, keys and other secrets are never shown here.</p>
@endsection
