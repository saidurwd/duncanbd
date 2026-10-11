@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="new-password">
            <div class="form-text">At least 12 characters, mixed case, with a number. Known-breached passwords are rejected.</div>
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Reset password</button>
    </form>
@endsection
