@extends('layouts.admin')

@section('title', 'My security')

@section('content')
    <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Change password</h2></div>
        <div class="card-body">
            @if (session('status') === 'password-updated')<div class="alert alert-success" role="status">Password updated.</div>@endif
            @if ($user->must_change_password)<div class="alert alert-warning" role="alert">You must choose a new password before continuing.</div>@endif
            <form method="POST" action="{{ route('user-password.update') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-12"><label for="current_password" class="form-label">Current password</label><input id="current_password" type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" required>@error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="password" class="form-label">New password</label><input id="password" type="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" required>@error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">At least 12 characters, mixed case, a number; known-breached passwords are rejected.</div></div>
                <div class="col-md-6"><label for="password_confirmation" class="form-label">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
                <div class="col-12"><button class="btn btn-primary">Update password</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Two-factor authentication</h2></div>
        <div class="card-body">
            @if ($enabled)
                <p class="text-success">Two-factor authentication is enabled.</p>
                @if (session('status') === 'recovery-codes-generated' || session('status') === 'two-factor-authentication-confirmed')
                    <p>Store these recovery codes in a password manager. Each can be used once.</p>
                @endif
                <ul class="list-unstyled font-monospace">
                    @foreach ($user->recoveryCodes() as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-primary btn-sm">Regenerate recovery codes</button>
                </form>
            @elseif ($pending)
                <p>Scan this QR code with an authenticator app (Microsoft Authenticator, Google Authenticator, Authy), then enter the 6-digit code to confirm.</p>
                <div class="mb-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <p>Setup key: <code>{{ decrypt($user->two_factor_secret) }}</code></p>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="row g-2">
                    @csrf
                    <div class="col-auto">
                        <label for="code" class="visually-hidden">Authentication code</label>
                        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control" placeholder="123456" required>
                    </div>
                    <div class="col-auto"><button class="btn btn-primary">Confirm</button></div>
                </form>
            @else
                <p>Two-factor authentication is mandatory for all administrators.</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="btn btn-primary">Set up two-factor authentication</button>
                </form>
            @endif
        </div>
    </div>
@endsection
