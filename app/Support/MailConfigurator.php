<?php

namespace App\Support;

use App\Domain\Settings\SettingsRepository;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Applies the SMTP details edited in Admin > Settings over the .env mail defaults. */
class MailConfigurator
{
    public function __construct(private SettingsRepository $settings) {}

    public function apply(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $mail = $this->settings->all()['mail'] ?? [];
        } catch (Throwable) {
            return; // DB not available (e.g. during install): keep .env defaults
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
