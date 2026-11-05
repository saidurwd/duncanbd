<?php

namespace App\Domain\System;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;

/** Facts shown on Security & audit → About. Read-only; no secrets (never env values, keys or paths with credentials). */
class SystemInfo
{
    /** Packages worth naming on the About page. */
    private const PACKAGES = [
        'laravel/framework' => 'Laravel framework',
        'laravel/fortify' => 'Sign-in, passwords and two-factor',
        'spatie/laravel-permission' => 'Roles and permissions',
        'spatie/laravel-activitylog' => 'Audit and activity log',
        'spatie/laravel-medialibrary' => 'Media library',
        'spatie/laravel-backup' => 'Backups',
        'spatie/laravel-responsecache' => 'Page caching',
    ];

    /** @return array<string, string> */
    public function application(): array
    {
        return array_filter([
            'Website' => config('app.name'),
            'Built for' => 'Duncan Brothers (Bangladesh) Limited, a Camellia Plc company',
            'Release' => $this->release(),
            'Environment' => app()->environment(),
            'Website address' => config('app.url'),
            'Admin path' => '/'.config('admin.path'),
            'Timezone' => config('app.timezone').' (admin screens show Asia/Dhaka)',
            'Server time' => now()->timezone('Asia/Dhaka')->format('d M Y H:i:s').' Dhaka',
        ]);
    }

    /** @return array<string, string> */
    public function server(): array
    {
        try {
            $db = DB::connection()->getDriverName().' '.DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (\Throwable) {
            $db = 'unavailable';
        }

        return [
            'PHP' => PHP_VERSION.' ('.PHP_SAPI.')',
            'Laravel' => app()->version(),
            'Database' => trim($db),
            'Web server' => $_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI,
            'Operating system' => PHP_OS_FAMILY.' '.php_uname('r'),
            'Cache / queue / session' => config('cache.default').' / '.config('queue.default').' / '.config('session.driver'),
        ];
    }

    /** @return list<array{name: string, purpose: string, version: string}> */
    public function packages(): array
    {
        $rows = [];
        foreach (self::PACKAGES as $name => $purpose) {
            if (InstalledVersions::isInstalled($name)) {
                $rows[] = ['name' => $name, 'purpose' => $purpose, 'version' => ltrim((string) InstalledVersions::getPrettyVersion($name), 'v')];
            }
        }

        return $rows;
    }

    /** Short git revision of the deployed code, read from .git without running a shell. Null when not a checkout. */
    private function release(): ?string
    {
        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return null;
        }

        $ref = trim((string) file_get_contents($head));
        if (str_starts_with($ref, 'ref: ')) {
            $file = base_path('.git/'.substr($ref, 5));
            $sha = is_file($file) ? trim((string) file_get_contents($file)) : null;
            $branch = basename(substr($ref, 5));
        } else {
            $sha = $ref;
            $branch = 'detached';
        }

        return $sha ? substr($sha, 0, 8).' ('.$branch.')' : null;
    }
}
