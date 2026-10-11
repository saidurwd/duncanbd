<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\TrustProxies;

it('backs up user data (documents, CVs, media) but never code, dependencies or secrets', function () {
    $include = config('backup.backup.source.files.include');

    expect($include)->toContain(storage_path('app/private'), storage_path('app/public'))
        ->and($include)->not->toContain(base_path())
        ->and(collect($include)->contains(fn ($p) => str_contains($p, '.env')))->toBeFalse();
});

it('encrypts backup archives with AES-256', function () {
    expect(config('backup.backup.encryption'))->toBe('aes256');
});

it('writes archives with repo-relative paths so a restore does not depend on the old server layout', function () {
    expect(config('backup.backup.source.files.relative_path'))->toBe(base_path());
});

it('keeps backup archives on a private disk that is not web-accessible', function () {
    $disks = config('backup.backup.destination.disks');

    expect($disks)->toContain('backups')
        ->and(config('filesystems.disks.backups.driver'))->toBe('local')
        ->and(config('filesystems.disks.backups.root'))->toBe(storage_path('app/backups'))
        ->and(config('filesystems.disks.backups.url'))->toBeNull();
});

it('schedules database and full backups, cleanup and health monitoring', function () {
    $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode(' | ');

    expect($commands)->toContain('backup:run --only-db')->toContain('backup:clean')->toContain('backup:monitor')->toContain('privacy:prune')->toContain('pages:publish-scheduled');
});

it('serves no private storage over HTTP', function () {
    expect(config('filesystems.disks.local.serve'))->toBeFalse();
    $this->get('/storage/private/anything')->assertNotFound();
});

it('trusts forwarded headers only from configured proxies', function () {
    TrustProxies::flushState();

    $seen = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])->get('/up');

    // Untrusted proxy: the spoofed header is ignored.
    $seen();
    expect(request()->ip())->toBe('10.0.0.5');

    TrustProxies::at(['10.0.0.0/8']);
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->withHeaders(['X-Forwarded-For' => '203.0.113.9'])->get('/contact');
    expect(request()->ip())->toBe('203.0.113.9');
    TrustProxies::flushState();
});
