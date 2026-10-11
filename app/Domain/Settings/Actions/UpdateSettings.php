<?php

namespace App\Domain\Settings\Actions;

use App\Domain\Settings\SettingsRepository;
use App\Domain\Settings\SettingsSchema;

class UpdateSettings
{
    public function __construct(private SettingsRepository $settings) {}

    /** @param array<string, array<string, mixed>> $input validated values keyed by group then field */
    public function __invoke(array $input): void
    {
        $changed = [];

        foreach (SettingsSchema::groups() as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                if (! array_key_exists($key, $input[$group] ?? [])) {
                    continue;
                }

                $secret = $field['secret'] ?? false;
                $value = $input[$group][$key];

                // Blank secret = keep the stored one.
                if ($secret && ($value === null || $value === '')) {
                    continue;
                }

                if (! $secret && $this->settings->get($group, $key) === $value) {
                    continue;
                }

                $this->settings->set($group, $key, $value, encrypted: $secret);
                $changed[] = "$group.$key";
            }
        }

        if ($changed !== []) {
            // Values are deliberately omitted: secrets must never reach the audit log.
            activity('settings')->causedBy(auth()->user())->withProperties(['keys' => $changed])->log('settings updated');
        }
    }
}
