<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = '/'.config('admin.path');
});

function adminWith2fa(bool $confirmed = true): User
{
    return User::factory()->create([
        'two_factor_secret' => $confirmed ? encrypt('SECRET') : null,
        'two_factor_recovery_codes' => $confirmed ? encrypt(json_encode(['code-1'])) : null,
        'two_factor_confirmed_at' => $confirmed ? now() : null,
    ]);
}

it('redirects guests to the login page', function () {
    $this->get($this->admin)->assertRedirect('/login');
});

it('forces users without 2FA to the security page', function () {
    $user = adminWith2fa(false);

    $this->actingAs($user)->get($this->admin)->assertRedirect(route('admin.security'));
    $this->actingAs($user)->get(route('admin.security'))->assertOk();
});

it('lets a user with 2FA reach the dashboard', function () {
    $this->actingAs(adminWith2fa())->get($this->admin)->assertOk()->assertSee('Welcome');
});

it('logs out deactivated users', function () {
    $user = adminWith2fa();
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)->get($this->admin)->assertRedirect(route('login'));
    $this->assertGuest();
});

it('hides the admin area from IPs outside the allowlist', function () {
    config(['admin.ip_allowlist' => ['10.0.0.0/8']]);

    $this->actingAs(adminWith2fa())->get($this->admin)->assertNotFound();
});

it('sends security headers and noindex on admin pages', function () {
    $this->actingAs(adminWith2fa())->get($this->admin)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Content-Security-Policy');
});

it('does not expose public registration', function () {
    $this->get('/register')->assertNotFound();
});

it('rejects weak passwords on reset', function () {
    expect(Validator::make(
        ['password' => 'short1A'],
        ['password' => Password::defaults()],
    )->fails())->toBeTrue();
});

it('seeds every role with the documented permissions', function () {
    $editor = Spatie\Permission\Models\Role::findByName(Role::Editor->value);

    expect($editor->hasPermissionTo('pages.publish'))->toBeTrue()
        ->and($editor->hasPermissionTo('users.manage'))->toBeFalse();

    $hr = User::factory()->create()->assignRole(Role::Hr->value);
    expect($hr->can('careers.manage'))->toBeTrue()
        ->and($hr->can('documents.investor.manage'))->toBeFalse();
});

it('records failed and successful logins in the audit log', function () {
    $user = User::factory()->create(['password' => 'Correct-Horse-9!']);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-9!']);

    expect(Activity::where('log_name', 'auth')->pluck('description')->all())
        ->toContain('login failed', 'login');
});

it('rejects a one-time code that has already been used (replay protection)', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);
    $provider = app(TwoFactorAuthenticationProvider::class);

    expect($provider->verify($secret, $code))->toBeTrue()
        ->and($provider->verify($secret, $code))->toBeFalse();
});

it('only skips the forced 2FA enrolment when explicitly switched off', function () {
    $user = adminWith2fa(false);

    expect(config('admin.require_2fa'))->toBeTrue();   // secure by default
    $this->actingAs($user)->get($this->admin)->assertRedirect(route('admin.security'));

    config(['admin.require_2fa' => false]);
    $this->actingAs($user)->get($this->admin)->assertOk();
});
