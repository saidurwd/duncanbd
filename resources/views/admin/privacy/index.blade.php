@extends('layouts.admin')

@section('title', 'Privacy tools')

@section('content')
    <form method="GET" class="d-flex gap-2 mb-4">
        <label for="email" class="visually-hidden">Email address</label>
        <input id="email" type="email" name="email" value="{{ $email }}" placeholder="person@example.com" class="form-control w-auto" required>
        <button class="btn btn-primary">Look up</button>
    </form>
    @if ($counts)
        <div class="card"><div class="card-body">
            <p>Held for <strong>{{ $email }}</strong>: {{ $counts['enquiries'] }} enquiries, {{ $counts['applications'] }} applications, {{ $counts['subscriber'] ? 'a newsletter subscription' : 'no newsletter subscription' }}.</p>
            <form method="GET" action="{{ route('admin.privacy.export') }}" class="mb-3"><input type="hidden" name="email" value="{{ $email }}"><button class="btn btn-outline-primary">Download data (JSON)</button></form>
            <form method="POST" action="{{ route('admin.privacy.erase') }}" class="row g-2 align-items-end">
                @csrf @method('DELETE')
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="col-auto"><label for="confirm" class="form-label mb-0">Type ERASE to confirm</label><input id="confirm" name="confirm" class="form-control" required autocomplete="off"></div>
                <div class="col-auto"><button class="btn btn-danger">Erase everything</button></div>
            </form>
        </div></div>
    @endif
@endsection
