<?php

return [

    // Non-obvious admin URL prefix; override in .env (ADMIN_PATH).
    'path' => env('ADMIN_PATH', 'admin'),

    // Optional comma-separated IP/CIDR allowlist, e.g. office/VPN ranges. Empty = allow all.
    'ip_allowlist' => array_filter(array_map('trim', explode(',', (string) env('ADMIN_IP_ALLOWLIST', '')))),

    // Mandatory two-factor authentication for every admin (plan §10). Only ever set to false on a local/demo machine:
    // with it off, admins are not forced to enrol (those who have enrolled are still challenged at sign-in).
    'require_2fa' => (bool) env('ADMIN_REQUIRE_2FA', true),

    // Plan §10: Super Admin is limited to named individuals (at most this many accounts).
    'max_super_admins' => (int) env('ADMIN_MAX_SUPER_ADMINS', 2),

    // Idle session timeout (minutes) for the admin panel.
    'idle_timeout' => (int) env('ADMIN_IDLE_TIMEOUT', 30),

    // Initial Super Admin created by the database seeder.
    'seed_super_admin' => [
        'name' => env('ADMIN_SEED_NAME', 'Super Admin'),
        'email' => env('ADMIN_SEED_EMAIL'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],
];
