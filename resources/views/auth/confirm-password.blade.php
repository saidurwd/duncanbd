@extends('layouts.auth')

@section('title', 'Confirm password')

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <p>Please confirm your password before continuing.</p>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control" required autofocus autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Confirm</button>
    </form>
@endsection
