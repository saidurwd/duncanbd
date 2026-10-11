@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-body">
            <p class="mb-0">Welcome, {{ auth()->user()->name }}. Modules are added phase by phase; see the development plan.</p>
        </div>
    </div>
@endsection
