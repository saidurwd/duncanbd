<?php

namespace App\Domain\Settings;

/**
 * Declares every editable site setting: group, field, label, input type, validation, secrecy.
 * The admin form, validation and persistence are all generated from this one definition.
 */
class SettingsSchema
{
    /**
     * @return array<string, array{label: string, fields: array<string, array{label: string, type: string, rules: list<string>, secret?: bool, help?: string}>}>
     */
    public static function groups(): array
    {
        return [
            'identity' => [
                'label' => 'Site identity',
                'fields' => [
                    'site_name' => ['label' => 'Site name', 'type' => 'text', 'rules' => ['required', 'string', 'max:120']],
                    'tagline' => ['label' => 'Tagline', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:160']],
                    'group_name' => ['label' => 'Parent group name', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
                    'group_url' => ['label' => 'Parent group URL', 'type' => 'url', 'rules' => ['nullable', 'url:https', 'max:255']],
                ],
            ],
            'contact' => [
                'label' => 'Contact details',
                'fields' => [
                    'email' => ['label' => 'General email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:255']],
                    'phone' => ['label' => 'Phone', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:40']],
                    'address_dhaka' => ['label' => 'Dhaka address', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
                    'address_chattogram' => ['label' => 'Chattogram address', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
                ],
            ],
            'social' => [
                'label' => 'Social links',
                'fields' => [
                    'linkedin' => ['label' => 'LinkedIn URL', 'type' => 'url', 'rules' => ['nullable', 'url:https', 'max:255']],
                    'facebook' => ['label' => 'Facebook URL', 'type' => 'url', 'rules' => ['nullable', 'url:https', 'max:255']],
                    'youtube' => ['label' => 'YouTube URL', 'type' => 'url', 'rules' => ['nullable', 'url:https', 'max:255']],
                ],
            ],
            'footer' => [
                'label' => 'Footer & consent',
                'fields' => [
                    'footer_text' => ['label' => 'Footer text', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
                    'cookie_banner_text' => ['label' => 'Cookie banner text', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
                    'analytics_id' => ['label' => 'Analytics measurement ID', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9-]+$/'], 'help' => 'Loads only after the visitor consents.'],
                ],
            ],
            'mail' => [
                'label' => 'Outgoing mail (SMTP)',
                'fields' => [
                    'host' => ['label' => 'SMTP host', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    'port' => ['label' => 'Port', 'type' => 'number', 'rules' => ['nullable', 'integer', 'between:1,65535']],
                    'username' => ['label' => 'Username', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    'password' => ['label' => 'Password', 'type' => 'password', 'rules' => ['nullable', 'string', 'max:255'], 'secret' => true, 'help' => 'Stored encrypted. Leave blank to keep the current password.'],
                    'from_address' => ['label' => 'From address', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:255']],
                    'from_name' => ['label' => 'From name', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
                ],
            ],
        ];
    }

    /** @return array<string, list<string>> validation rules keyed "group.field" */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::groups() as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $rules["$group.$key"] = $field['rules'];
            }
        }

        return $rules;
    }
}
