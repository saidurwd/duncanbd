@extends('layouts.auth')

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="username">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-check mb-3">
            <input id="remember" type="checkbox" name="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Remember me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign in</button>
    </form>
    <p class="mt-3 mb-0"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
@endsection
