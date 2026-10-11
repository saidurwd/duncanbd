@extends('layouts.admin')

@section('title', 'Users & roles')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <label for="q" class="visually-hidden">Search</label>
            <input id="q" name="q" value="{{ $search }}" placeholder="Name or email" class="form-control w-auto">
            <label for="role" class="visually-hidden">Role</label>
            <select id="role" name="role" class="form-select w-auto"><option value="">All roles</option>
                @foreach (App\Domain\Identity\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>@endforeach</select>
            <select name="status" class="form-select w-auto" aria-label="Status"><option value="">Any status</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
            <button class="btn btn-outline-secondary">Filter</button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add user</a>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Name</th><th>Role</th><th>Two-factor</th><th>Status</th><th>Last sign-in</th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
                <td><a href="{{ route('admin.users.edit', $u) }}">{{ $u->name }}</a><div class="small text-muted">{{ $u->email }}</div></td>
                <td>{{ $u->roles->first() ? App\Domain\Identity\Enums\Role::from($u->roles->first()->name)->label() : '—' }}</td>
                <td>@if ($u->hasTwoFactorEnabled())<span class="badge text-bg-success">On</span>@else<span class="badge text-bg-warning">Not set up</span>@endif</td>
                <td>@if ($u->is_active)<span class="badge text-bg-secondary">Active</span>@else<span class="badge text-bg-danger">Inactive</span>@endif @if ($u->must_change_password)<span class="badge text-bg-info">Must change password</span>@endif</td>
                <td>{{ $u->last_login_at?->timezone('Asia/Dhaka')->format('d M Y H:i') ?? 'Never' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $users->links() }}</div>
@endsection
