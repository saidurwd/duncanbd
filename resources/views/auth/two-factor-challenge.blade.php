@extends('layouts.auth')

@section('title', 'Two-factor authentication')

@section('content')
    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        <p>Enter the 6-digit code from your authenticator app, or one of your recovery codes.</p>
        <div class="mb-3">
            <label for="code" class="form-label">Authentication code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control" autofocus>
        </div>
        <div class="mb-3">
            <label for="recovery_code" class="form-label">Recovery code</label>
            <input id="recovery_code" type="text" name="recovery_code" autocomplete="off" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">Verify</button>
    </form>
@endsection
