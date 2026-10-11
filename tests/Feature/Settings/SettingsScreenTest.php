<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(Role $role): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('S'),
        'two_factor_recovery_codes' => encrypt('[]'),
        'two_factor_confirmed_at' => now(),
    ])->assignRole($role->value);
}

it('lets a site admin view and save settings', function () {
    $admin = userWithRole(Role::SiteAdmin);

    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('Site identity');

    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'identity' => ['site_name' => 'Duncan Brothers'],
        'mail' => ['password' => 's3cret-smtp'],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $repo = app(SettingsRepository::class);
    expect($repo->get('identity', 'site_name'))->toBe('Duncan Brothers')
        ->and($repo->get('mail', 'password'))->toBe('s3cret-smtp')
        ->and(json_encode(Setting::where('key', 'password')->first()->value))->not->toContain('s3cret-smtp');
});

it('keeps the stored secret when the password field is left blank', function () {
    $repo = app(SettingsRepository::class);
    $repo->set('mail', 'password', 'keep-me', encrypted: true);

    $this->actingAs(userWithRole(Role::SiteAdmin))->put(route('admin.settings.update'), [
        'identity' => ['site_name' => 'X'],
        'mail' => ['password' => ''],
    ]);

    expect($repo->get('mail', 'password'))->toBe('keep-me');
});

it('never writes secret values to the audit log', function () {
    $this->actingAs(userWithRole(Role::SiteAdmin))->put(route('admin.settings.update'), [
        'identity' => ['site_name' => 'X'],
        'mail' => ['password' => 'audit-leak-check'],
    ]);

    $log = Activity::where('log_name', 'settings')->first();

    expect($log)->not->toBeNull()
        ->and(json_encode($log->properties))->not->toContain('audit-leak-check');
});

it('forbids roles without settings.manage', function () {
    $this->actingAs(userWithRole(Role::Editor))->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs(userWithRole(Role::Editor))->put(route('admin.settings.update'), [])->assertForbidden();
});

it('validates fields', function () {
    $this->actingAs(userWithRole(Role::SiteAdmin))->put(route('admin.settings.update'), [
        'identity' => ['site_name' => ''],
        'social' => ['linkedin' => 'http://insecure.example'],
    ])->assertSessionHasErrors(['identity.site_name', 'social.linkedin']);
});
