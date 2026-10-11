@extends('layouts.admin')

@section('title', 'My security')

@section('content')
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
