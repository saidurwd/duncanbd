<?php

namespace App\Support;

use App\Domain\Settings\SettingsRepository;
use Throwable;

/** Applies the SMTP details edited in Admin > Settings over the .env mail defaults. */
class MailConfigurator
{
    public function __construct(private SettingsRepository $settings) {}

    public function apply(): void
    {
        try {
            // Served from the settings cache on every request after the first; no schema introspection here
            // (information_schema queries are slow on MySQL and this runs on every request).
            $mail = $this->settings->all()['mail'] ?? [];
        } catch (Throwable) {
            return; // table/DB not available (e.g. during install): keep .env defaults
        }

        if (filled($mail['host'] ?? null)) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $mail['host'],
                'mail.mailers.smtp.port' => (int) ($mail['port'] ?? 587),
                'mail.mailers.smtp.username' => $mail['username'] ?? null,
                'mail.mailers.smtp.password' => $mail['password'] ?? null,
            ]);
        }

        if (filled($mail['from_address'] ?? null)) {
            config(['mail.from.address' => $mail['from_address'], 'mail.from.name' => $mail['from_name'] ?? config('mail.from.name')]);
        }
    }
}
