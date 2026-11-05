<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\System\HealthCheck;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function auditUser(Role $role): User
{
    return User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole($role->value);
}

const AUDIT_ROUTES = ['admin.audit.audit', 'admin.audit.activity', 'admin.audit.logins', 'admin.audit.events'];

it('lets a super admin open every security and system screen', function () {
    $admin = auditUser(Role::SuperAdmin);

    foreach ([...AUDIT_ROUTES, 'admin.system.health', 'admin.system.about'] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

it('shows the Security & audit group in the sidebar', function () {
    $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.dashboard'))
        ->assertSee('SECURITY &amp; AUDIT', false)->assertSee('Audit log')->assertSee('Login history')->assertSee('System health');
});

it('gives a site admin the logs and the system screens', function () {
    $admin = auditUser(Role::SiteAdmin);

    foreach ([...AUDIT_ROUTES, 'admin.system.health', 'admin.system.about'] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

it('schedules the audit log clean-up to keep 60 days', function () {
    $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->filter(fn ($e) => str_contains($e->command, 'activitylog:clean'));

    expect($events)->toHaveCount(1)->and($events->first()->command)->toContain('--days=60')->and($events->first()->expression)->toBe('30 3 * * *');
});

it('keeps editors and viewers out of every screen', function (Role $role) {
    $user = auditUser($role);

    foreach ([...AUDIT_ROUTES, 'admin.system.health', 'admin.system.about'] as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
})->with([Role::Editor, Role::Author, Role::Viewer]);

it('redirects guests to sign in', function () {
    $this->get(route('admin.audit.audit'))->assertRedirect();
    $this->get(route('admin.system.health'))->assertRedirect();
});

it('records lockouts and two-factor changes as security events', function () {
    $user = auditUser(Role::Editor);

    Event::dispatch(new Lockout(Request::create('/login', 'POST', ['email' => 'target@example.com', 'password' => 'secret-never-stored'])));
    Event::dispatch(new TwoFactorAuthenticationDisabled($user));

    $lockout = Activity::where('log_name', 'security')->where('description', 'like', 'sign-in locked out%')->first();
    expect($lockout)->not->toBeNull()->and($lockout->getProperty('email'))->toBe('target@example.com')
        ->and(json_encode($lockout->properties))->not->toContain('secret-never-stored');
    expect(Activity::where('log_name', 'security')->where('description', 'two-factor disabled')->where('causer_id', $user->id)->exists())->toBeTrue();

    $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.audit.events'))
        ->assertOk()->assertSee('Sign-in locked out')->assertSee('Two-factor disabled');
});

it('flags a burst of failed sign-ins as possible password guessing', function () {
    foreach (range(1, 5) as $_) {
        activity('auth')->withProperties(['ip' => '203.0.113.9', 'email' => 'victim@example.com'])->log('login failed');
    }

    $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.audit.events'))
        ->assertOk()->assertSee('Possible password guessing')->assertSee('victim@example.com');
});

it('lists sign-ins with a readable browser and filters by outcome', function () {
    $user = auditUser(Role::Editor);
    activity('auth')->causedBy($user)->withProperties(['ip' => '198.51.100.7', 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0 Safari/537.36'])->log('login');
    activity('auth')->withProperties(['ip' => '198.51.100.8', 'email' => 'nobody@example.com'])->log('login failed');

    $admin = auditUser(Role::SuperAdmin);
    $this->actingAs($admin)->get(route('admin.audit.logins'))->assertOk()->assertSee('Chrome on macOS')->assertSee('198.51.100.7');
    $this->actingAs($admin)->get(route('admin.audit.logins', ['outcome' => 'login failed']))->assertOk()->assertSee('nobody@example.com')->assertDontSee('198.51.100.7');
});

it('shows before and after values for a recorded change', function () {
    $entry = activity('pages')->event('updated')->withProperties(['attributes' => ['title' => 'New title'], 'old' => ['title' => 'Old title']])->log('updated');
    $entry->update(['attribute_changes' => ['attributes' => ['title' => 'New title'], 'old' => ['title' => 'Old title']]]);

    $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.audit.show', $entry))->assertOk()->assertSee('Old title')->assertSee('New title');
});

it('ignores malformed filters instead of failing', function () {
    $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.audit.audit', ['user' => 'abc', 'from' => 'not-a-date', 'to' => '9999-99-99', 'event' => 'drop table', 'q' => "%_'\""]))->assertOk();
});

it('exports the audit log as CSV without formula injection', function () {
    activity('pages')->event('created')->log('=HYPERLINK("http://evil")');

    $response = $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.audit.export'));
    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    expect($csv)->toContain('when (Dhaka)')->and($csv)->toContain("'=HYPERLINK");
});

it('runs every health check and reports a valid status for each', function () {
    Cache::put(HealthCheck::SCHEDULER_KEY, now()->timestamp, 60);
    $checks = app(HealthCheck::class)->run(Request::create('/'));

    expect($checks)->not->toBeEmpty();
    foreach ($checks as $check) {
        expect($check['status'])->toBeIn(['ok', 'warn', 'fail'])->and($check['name'])->not->toBeEmpty();
    }
    $byName = collect($checks)->keyBy('name');
    expect($byName['Connection']['status'])->toBe('ok')->and($byName['Cron heartbeat']['status'])->toBe('ok')->and($byName['Read / write']['status'])->toBe('ok');
    expect(HealthCheck::summary($checks))->toHaveKeys(['ok', 'warn', 'fail']);
});

it('warns when the scheduler has never reported', function () {
    Cache::forget(HealthCheck::SCHEDULER_KEY);

    $row = collect(app(HealthCheck::class)->run(Request::create('/')))->firstWhere('name', 'Cron heartbeat');
    expect($row['status'])->toBe('warn');
});

it('about page names the framework but never leaks secrets', function () {
    $html = $this->actingAs(auditUser(Role::SuperAdmin))->get(route('admin.system.about'))->assertOk()->assertSee('laravel/framework')->getContent();
    expect($html)->not->toContain((string) config('app.key'))->and($html)->not->toContain((string) config('database.connections.mysql.password') ?: 'no-password-configured-xyz');
});
