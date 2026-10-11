<?php

use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\SettingsRepository;

it('stores and reads plain settings', function () {
    $repo = app(SettingsRepository::class);
    $repo->set('contact', 'email', 'info@example.com');

    expect($repo->get('contact', 'email'))->toBe('info@example.com')
        ->and($repo->get('contact', 'missing', 'fallback'))->toBe('fallback');
});

it('encrypts secrets at rest', function () {
    $repo = app(SettingsRepository::class);
    $repo->set('mail', 'smtp_password', 'hunter2', encrypted: true);

    expect($repo->get('mail', 'smtp_password'))->toBe('hunter2')
        ->and(json_encode(Setting::first()->value))->not->toContain('hunter2');
});
