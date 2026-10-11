@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Email reset link</button>
    </form>
    <p class="mt-3 mb-0"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
