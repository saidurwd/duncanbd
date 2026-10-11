<?php

return [

    // Non-obvious admin URL prefix; override in .env (ADMIN_PATH).
    'path' => env('ADMIN_PATH', 'dbbd-console'),

    // Optional comma-separated IP/CIDR allowlist, e.g. office/VPN ranges. Empty = allow all.
    'ip_allowlist' => array_filter(array_map('trim', explode(',', (string) env('ADMIN_IP_ALLOWLIST', '')))),

    // Idle session timeout (minutes) for the admin panel.
    'idle_timeout' => (int) env('ADMIN_IDLE_TIMEOUT', 30),

    // Initial Super Admin created by the database seeder.
    'seed_super_admin' => [
        'name' => env('ADMIN_SEED_NAME', 'Super Admin'),
        'email' => env('ADMIN_SEED_EMAIL'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],
];
