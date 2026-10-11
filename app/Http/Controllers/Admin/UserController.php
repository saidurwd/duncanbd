<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\SaveUser;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.users.index', [
            'users' => User::with('roles')
                ->when(Role::tryFrom((string) $request->query('role')), fn ($q, $r) => $q->role($r->value))
                ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%')))
                ->orderBy('name')->paginate(30)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true]), 'roles' => Role::cases()]);
    }

    public function store(Request $request, SaveUser $save): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'string', PasswordRule::default()],
        ]);

        ['user' => $user, 'invited' => $invited] = $save(null, $data, $request->user());

        return redirect()->route('admin.users.edit', $user)->with('status', $invited
            ? "User created. A link to set their password was emailed to {$user->email}."
            : 'User created. They must change the password you set at first sign-in'.(blank($data['password'] ?? null) ? ' (the reset email could not be sent; use "Set a temporary password" below).' : '.'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user, SaveUser $save): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $save($user, $data + ['is_active' => $request->boolean('is_active')], $request->user());

        return back()->with('status', 'User updated.');
    }

    /** Admin sets a temporary password; the person must replace it at next sign-in. Other sessions are ended. */
    public function setPassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, 'Change your own password from "My security".');

        $data = $request->validate(['password' => ['required', 'string', PasswordRule::default()]]);

        $user->forceFill(['password' => $data['password'], 'must_change_password' => true])->save();
        $this->endSessions($user);
        activity('identity')->causedBy($request->user())->performedOn($user)->log('password set by administrator');

        return back()->with('status', 'Temporary password set. They must change it at next sign-in.');
    }

    public function sendResetLink(Request $request, User $user): RedirectResponse
    {
        $sent = Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
        activity('identity')->causedBy($request->user())->performedOn($user)->log('password reset link sent');

        return back()->with('status', $sent ? "Password reset link emailed to {$user->email}." : 'The reset link could not be sent (check the mail settings).');
    }

    public function resetTwoFactor(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->endSessions($user);
        activity('identity')->causedBy($request->user())->performedOn($user)->log('two-factor reset');

        return back()->with('status', 'Two-factor authentication reset. They will set it up again at next sign-in.');
    }

    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        $user->forceFill(['remember_token' => null])->save();
    }
}
