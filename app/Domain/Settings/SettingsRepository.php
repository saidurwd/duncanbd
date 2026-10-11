<?php

namespace App\Domain\Settings;

use App\Domain\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Site settings stored in the database (identity, contacts, socials, SMTP...).
 * Secrets are encrypted at rest; reads are cached until a value changes.
 */
class SettingsRepository
{
    private const CACHE_KEY = 'settings.all';

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return $this->all()[$group][$key] ?? $default;
    }

    public function set(string $group, string $key, mixed $value, bool $encrypted = false): void
    {
        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => [$encrypted ? Crypt::encryptString(json_encode($value)) : $value], 'is_encrypted' => $encrypted],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $out = [];

            foreach (Setting::all() as $setting) {
                $raw = $setting->value[0] ?? null;
                $out[$setting->group][$setting->key] = $setting->is_encrypted
                    ? json_decode(Crypt::decryptString($raw), true)
                    : $raw;
            }

            return $out;
        });
    }
}
