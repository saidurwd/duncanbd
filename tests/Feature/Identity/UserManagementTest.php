<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->boss = User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole(Role::SuperAdmin->value);
});

function plainUser(Role $role, array $o = []): User
{
    return User::factory()->create($o)->assignRole($role->value);
}

it('lets only Super Admins manage users', function () {
    foreach ([Role::SiteAdmin, Role::Editor, Role::Hr, Role::Viewer] as $role) {
        $u = plainUser($role, ['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()]);
        $this->actingAs($u)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($u)->post(route('admin.users.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'viewer'])->assertForbidden();
    }
    $this->actingAs($this->boss)->get(route('admin.users.index'))->assertOk();
});

it('creates a user with an emailed set-password link when no password is given', function () {
    Notification::fake();

    $this->actingAs($this->boss)->post(route('admin.users.store'), ['name' => 'New Editor', 'email' => 'New.Editor@Example.com', 'role' => 'editor'])
        ->assertSessionHasNoErrors()->assertRedirect();

    $user = User::where('email', 'new.editor@example.com')->firstOrFail();
    expect($user->hasRole('editor'))->toBeTrue()->and($user->must_change_password)->toBeTrue()->and($user->is_active)->toBeTrue();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('creates a user with an admin-set password that must be changed at first sign-in', function () {
    $this->actingAs($this->boss)->post(route('admin.users.store'), ['name' => 'HR Person', 'email' => 'hr@example.com', 'role' => 'hr', 'password' => 'Temp-Password-2026!'])->assertSessionHasNoErrors();

    $user = User::where('email', 'hr@example.com')->firstOrFail();
    expect(Hash::check('Temp-Password-2026!', $user->password))->toBeTrue()->and($user->must_change_password)->toBeTrue();

    // With the flag set they cannot reach the admin until they change it.
    $user->forceFill(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($user)->get('/'.config('admin.path'))->assertRedirect(route('admin.security'));
    $this->actingAs($user)->get(route('admin.security'))->assertOk()->assertSee('must choose a new password');
});

it('rejects weak temporary passwords, duplicate emails and unknown roles', function () {
    plainUser(Role::Viewer, ['email' => 'taken@example.com']);

    $this->actingAs($this->boss)->post(route('admin.users.store'), ['name' => 'A', 'email' => 'a@example.com', 'role' => 'viewer', 'password' => 'short'])->assertSessionHasErrors('password');
    $this->actingAs($this->boss)->post(route('admin.users.store'), ['name' => 'A', 'email' => 'taken@example.com', 'role' => 'viewer'])->assertSessionHasErrors('email');
    $this->actingAs($this->boss)->post(route('admin.users.store'), ['name' => 'A', 'email' => 'b@example.com', 'role' => 'emperor'])->assertSessionHasErrors('role');
});

it('changes a role, deactivates a user and logs it', function () {
    $user = plainUser(Role::Author);

    $this->actingAs($this->boss)->put(route('admin.users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => 'editor', 'is_active' => 1])->assertSessionHasNoErrors();
    expect($user->fresh()->hasRole('editor'))->toBeTrue()->and($user->fresh()->hasRole('author'))->toBeFalse();

    $this->actingAs($this->boss)->put(route('admin.users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => 'editor', 'is_active' => 0]);
    expect($user->fresh()->is_active)->toBeFalse();

    expect(Activity::where('log_name', 'identity')->where('description', 'role changed')->exists())->toBeTrue();
});

it('stops deactivated users signing in', function () {
    $user = plainUser(Role::Editor, ['password' => 'Correct-Horse-9!', 'is_active' => false]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-9!']);

    $this->assertGuest();
});

it('prevents self lock-out and enforces the Super Admin limits', function () {
    $this->actingAs($this->boss)->put(route('admin.users.update', $this->boss), ['name' => 'x', 'email' => $this->boss->email, 'role' => 'editor', 'is_active' => 1])->assertSessionHasErrors('role');
    $this->actingAs($this->boss)->put(route('admin.users.update', $this->boss), ['name' => 'x', 'email' => $this->boss->email, 'role' => 'super-admin', 'is_active' => 0])->assertSessionHasErrors('role');

    config(['admin.max_super_admins' => 2]);
    plainUser(Role::SuperAdmin);                                    // second Super Admin: ok, limit reached
    $this->actingAs($this->boss)->post(route('admin.users.store'), ['name' => 'Third', 'email' => 'third@example.com', 'role' => 'super-admin', 'password' => 'Temp-Password-2026!'])->assertSessionHasErrors('role');
    expect(User::where('email', 'third@example.com')->exists())->toBeFalse();
});

it('sets a temporary password, ends the old one and forces a change', function () {
    $user = plainUser(Role::Editor, ['password' => 'Old-Password-2026!']);

    $this->actingAs($this->boss)->put(route('admin.users.password', $user), ['password' => 'Temporary-Pass-2026!'])->assertSessionHasNoErrors();

    $user->refresh();
    expect(Hash::check('Temporary-Pass-2026!', $user->password))->toBeTrue()->and(Hash::check('Old-Password-2026!', $user->password))->toBeFalse()->and($user->must_change_password)->toBeTrue();
    $this->actingAs($this->boss)->put(route('admin.users.password', $this->boss), ['password' => 'Temporary-Pass-2026!'])->assertForbidden();   // own password: use My security
});

it('emails a reset link and resets two-factor for someone who lost their phone', function () {
    Notification::fake();
    $user = plainUser(Role::Editor, ['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()]);

    $this->actingAs($this->boss)->post(route('admin.users.reset-link', $user))->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);

    $this->actingAs($this->boss)->post(route('admin.users.reset-2fa', $user));
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse()->and($user->fresh()->two_factor_secret)->toBeNull();
});

it('lets any admin change their own password with the current one and clears the forced flag', function () {
    $user = plainUser(Role::Editor, ['password' => 'Old-Password-2026!', 'must_change_password' => true, 'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()]);

    $this->actingAs($user)->put('/user/password', ['current_password' => 'wrong', 'password' => 'New-Password-2026!', 'password_confirmation' => 'New-Password-2026!'])->assertSessionHasErrors('current_password', null, 'updatePassword');
    expect($user->fresh()->must_change_password)->toBeTrue();

    $this->actingAs($user)->put('/user/password', ['current_password' => 'Old-Password-2026!', 'password' => 'New-Password-2026!', 'password_confirmation' => 'New-Password-2026!'])->assertSessionHasNoErrors();

    expect(Hash::check('New-Password-2026!', $user->fresh()->password))->toBeTrue()->and($user->fresh()->must_change_password)->toBeFalse();
    $this->actingAs($user->fresh())->get('/'.config('admin.path'))->assertOk();
});

it('renders the user screens', function () {
    $user = plainUser(Role::Hr);

    $this->actingAs($this->boss)->get(route('admin.users.create'))->assertOk()->assertSee('Temporary password');
    $this->actingAs($this->boss)->get(route('admin.users.edit', $user))->assertOk()->assertSee('Reset two-factor');
    $this->actingAs($this->boss)->get(route('admin.users.index', ['role' => 'hr', 'q' => $user->name]))->assertOk()->assertSee($user->email);
});
