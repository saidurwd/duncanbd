@extends('layouts.admin')

@section('title', $user->exists ? 'Edit user' : 'Add user')

@section('content')
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="row g-3 mb-4">
        @csrf @if ($user->exists) @method('PUT') @endif
        <div class="col-md-6"><label for="name" class="form-label">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
        <div class="col-md-6"><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
        <div class="col-md-6">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                @foreach ($roles as $r)<option value="{{ $r->value }}" @selected(old('role', $user->roles->first()?->name) === $r->value)>{{ $r->label() }}</option>@endforeach
            </select>
            @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text">Editor publishes pages and news; Author drafts only; HR manages careers; IR / Company Secretary manages investor documents; Viewer is read-only.</div>
        </div>
        @if ($user->exists)
            <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input type="hidden" name="is_active" value="0"><input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $user->is_active))><label for="is_active" class="form-check-label">Active (can sign in)</label></div></div>
        @else
            <div class="col-md-6">
                <label for="password" class="form-label">Temporary password <span class="text-muted">(optional)</span></label>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Leave blank to email them a link to choose their own password (needs working mail). If set, they must change it at first sign-in.</div>
            </div>
        @endif
        <div class="col-12"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.users.index') }}" class="btn btn-link">Back</a></div>
    </form>

    @if ($user->exists)
        <div class="card"><div class="card-header"><h2 class="card-title">Account actions</h2></div><div class="card-body d-flex flex-column gap-4">
            @unless ($user->is(auth()->user()))
                <form method="POST" action="{{ route('admin.users.password', $user) }}" class="row g-2 align-items-end">
                    @csrf @method('PUT')
                    <div class="col-md-5"><label for="new_password" class="form-label mb-0">Set a temporary password</label><input id="new_password" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
                    <div class="col-auto"><button class="btn btn-outline-primary">Set password</button></div>
                    <div class="col-12 form-text">They are signed out everywhere and must choose a new password at next sign-in.</div>
                </form>
            @endunless
            <form method="POST" action="{{ route('admin.users.reset-link', $user) }}">@csrf<button class="btn btn-outline-secondary">Email a password reset link</button></form>
            <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}">@csrf<button class="btn btn-outline-danger">Reset two-factor authentication</button><div class="form-text">For a lost phone. They set it up again at next sign-in.</div></form>
            <div class="small text-muted">Last sign-in: {{ $user->last_login_at?->timezone('Asia/Dhaka')->format('d M Y H:i') ?? 'never' }} @if ($user->last_login_ip) from {{ $user->last_login_ip }} @endif</div>
        </div></div>
    @endif
@endsection
