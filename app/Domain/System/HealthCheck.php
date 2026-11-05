<?php

namespace App\Domain\System;

use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Runs read-only health checks for Security & audit → System health. Nothing here changes data beyond a
 * throw-away cache key. Every check is isolated: a failing one is reported, it never breaks the page.
 *
 * A check is ['group', 'name', 'status' => ok|warn|fail, 'detail', 'hint'].
 */
class HealthCheck
{
    public const SCHEDULER_KEY = 'system.scheduler.heartbeat';

    /** @return list<array{group: string, name: string, status: string, detail: string, hint: ?string}> */
    public function run(Request $request): array
    {
        $checks = [];
        foreach ([
            $this->application($request), $this->database(), $this->cache(), $this->storage(),
            $this->queue(), $this->scheduler(), $this->mail(), $this->backups(), $this->security($request),
        ] as $group) {
            array_push($checks, ...$group);
        }

        return $checks;
    }

    /** @param list<array{status: string}> $checks @return array{ok: int, warn: int, fail: int} */
    public static function summary(array $checks): array
    {
        $count = array_count_values(array_column($checks, 'status'));

        return ['ok' => $count['ok'] ?? 0, 'warn' => $count['warn'] ?? 0, 'fail' => $count['fail'] ?? 0];
    }

    // ---- groups ---------------------------------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    private function application(Request $request): array
    {
        $production = app()->isProduction();
        $out = [];

        $out[] = $this->row('Application', 'Environment', $production ? 'ok' : 'warn', 'APP_ENV = '.app()->environment(), $production ? null : 'Set APP_ENV=production on the live server.');
        $out[] = $this->row('Application', 'Debug mode', config('app.debug') ? ($production ? 'fail' : 'warn') : 'ok', config('app.debug') ? 'On' : 'Off', config('app.debug') ? 'APP_DEBUG must be false in production: error pages leak configuration and code.' : null);
        $out[] = $this->row('Application', 'Application key', config('app.key') ? 'ok' : 'fail', config('app.key') ? 'Set' : 'Missing', config('app.key') ? null : 'Run php artisan key:generate.');
        $out[] = $this->row('Application', 'PHP version', version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'fail', PHP_VERSION, version_compare(PHP_VERSION, '8.3.0', '>=') ? null : 'PHP 8.3 or newer is required.');

        $missing = array_values(array_filter(['mbstring', 'openssl', 'pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'zip'], fn ($e) => ! extension_loaded($e)));
        $out[] = $this->row('Application', 'PHP extensions', $missing ? 'fail' : 'ok', $missing ? 'Missing: '.implode(', ', $missing) : 'All required extensions loaded', $missing ? 'Install the missing extensions and restart PHP.' : null);

        $upload = ini_get('upload_max_filesize');
        $out[] = $this->row('Application', 'Upload limits', $this->bytes($upload) >= 20 * 1024 * 1024 ? 'ok' : 'warn', "upload_max_filesize {$upload}, post_max_size ".ini_get('post_max_size').', memory_limit '.ini_get('memory_limit'), $this->bytes($upload) >= 20 * 1024 * 1024 ? null : 'Raise upload_max_filesize and post_max_size if editors upload large PDFs.');

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function database(): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $version = (string) DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $ms = (int) round((microtime(true) - $start) * 1000);

            return [$this->row('Database', 'Connection', $ms > 500 ? 'warn' : 'ok', DB::connection()->getDriverName().' '.$version.", {$ms} ms", $ms > 500 ? 'Slow query round-trip; check database load.' : null)];
        } catch (\Throwable $e) {
            return [$this->row('Database', 'Connection', 'fail', 'Cannot connect: '.class_basename($e), 'Check DB_* settings and that the database server is running.')];
        }
    }

    /** @return list<array<string, mixed>> */
    private function cache(): array
    {
        try {
            $key = 'system.health.probe.'.bin2hex(random_bytes(4));
            Cache::put($key, 'ok', 30);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return [$this->row('Cache', 'Read / write', $ok ? 'ok' : 'fail', config('cache.default').' store '.($ok ? 'working' : 'did not return the value written'), $ok ? null : 'Check the cache store configuration.')];
        } catch (\Throwable $e) {
            return [$this->row('Cache', 'Read / write', 'fail', 'Error: '.class_basename($e), 'Check the cache store configuration.')];
        }
    }

    /** @return list<array<string, mixed>> */
    private function storage(): array
    {
        $out = [];
        $writable = is_writable(storage_path()) && is_writable(storage_path('logs'));
        $out[] = $this->row('Storage', 'Writable folders', $writable ? 'ok' : 'fail', $writable ? 'storage/ and storage/logs are writable' : 'storage/ is not writable', $writable ? null : 'Fix ownership/permissions on storage/ and bootstrap/cache.');

        $link = public_path('storage');
        $linked = is_link($link) || is_dir($link);
        $out[] = $this->row('Storage', 'Public uploads', $linked ? 'ok' : 'fail', $linked ? 'public/storage is available' : 'public/storage is missing', $linked ? null : 'Run php artisan storage:link (or set PUBLIC_DISK_IN_PUBLIC on hosts without symlinks).');

        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if ($free !== false && $total) {
            $pct = $free / $total * 100;
            $out[] = $this->row('Storage', 'Disk space', $pct < 5 ? 'fail' : ($pct < 15 ? 'warn' : 'ok'), $this->human($free).' free of '.$this->human($total).' ('.round($pct).'%)', $pct < 15 ? 'Free up space or expand the disk: uploads and backups will start failing.' : null);
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function queue(): array
    {
        $connection = config('queue.default');
        $out = [];

        try {
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $pending = $connection === 'database' && Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;
            $out[] = $this->row('Queue', 'Failed jobs', $failed > 0 ? 'warn' : 'ok', $failed.' failed', $failed > 0 ? 'Inspect with php artisan queue:failed, then retry or flush.' : null);
            $out[] = $this->row('Queue', 'Pending jobs', $pending !== null && $pending > 100 ? 'warn' : 'ok', ($pending === null ? 'Driver "'.$connection.'"' : $pending.' waiting').' ('.$connection.')', $pending !== null && $pending > 100 ? 'A backlog usually means the queue worker is not running.' : null);
        } catch (\Throwable $e) {
            $out[] = $this->row('Queue', 'Jobs table', 'fail', 'Error: '.class_basename($e), null);
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function scheduler(): array
    {
        $last = Cache::get(self::SCHEDULER_KEY);

        if (! $last) {
            return [$this->row('Scheduler', 'Cron heartbeat', 'warn', 'No heartbeat recorded yet', 'Add a cron entry running php artisan schedule:run every minute. Scheduled pages, backups and data clean-up depend on it.')];
        }

        $age = now()->timestamp - (int) $last;

        return [$this->row('Scheduler', 'Cron heartbeat', $age > 600 ? 'fail' : 'ok', 'Last run '.now()->subSeconds($age)->diffForHumans(), $age > 600 ? 'The scheduler has stopped: check the cron entry for schedule:run.' : null)];
    }

    /** @return list<array<string, mixed>> */
    private function mail(): array
    {
        $mailer = config('mail.default');
        $dummy = in_array($mailer, ['log', 'array'], true);

        return [$this->row('Mail', 'Mail transport', $dummy && app()->isProduction() ? 'warn' : 'ok', 'Mailer: '.$mailer.' (from '.config('mail.from.address').')', $dummy && app()->isProduction() ? 'Mail is only being written to the log. Enquiry notifications and password resets will not be delivered.' : null)];
    }

    /** @return list<array<string, mixed>> */
    private function backups(): array
    {
        try {
            $disk = Storage::disk((string) (config('backup.backup.destination.disks')[0] ?? 'backups'));
            $files = collect($disk->allFiles(config('backup.backup.name')))->filter(fn ($f) => str_ends_with($f, '.zip'));
            if ($files->isEmpty()) {
                return [$this->row('Backups', 'Latest backup', 'warn', 'No backup archives found', 'Run php artisan backup:run and confirm the scheduler is running.')];
            }
            $latest = $files->map(fn ($f) => $disk->lastModified($f))->max();
            $age = now()->timestamp - $latest;

            return [$this->row('Backups', 'Latest backup', $age > 3 * 86400 ? 'fail' : ($age > 36 * 3600 ? 'warn' : 'ok'), now()->subSeconds($age)->diffForHumans().' ('.$files->count().' archives kept)', $age > 36 * 3600 ? 'Backups are overdue. Check the scheduler and disk space.' : null)];
        } catch (\Throwable $e) {
            return [$this->row('Backups', 'Latest backup', 'warn', 'Could not read the backup disk: '.class_basename($e), null)];
        }
    }

    /** @return list<array<string, mixed>> */
    private function security(Request $request): array
    {
        $out = [];
        $production = app()->isProduction();

        $secure = $request->isSecure() || str_starts_with((string) config('app.url'), 'https://');
        $out[] = $this->row('Security', 'HTTPS', $secure ? 'ok' : ($production ? 'fail' : 'warn'), $secure ? 'Site is served over HTTPS' : 'Site is served over plain HTTP', $secure ? null : 'Serve the site and admin over HTTPS only.');

        $cookie = (bool) config('session.secure');
        $out[] = $this->row('Security', 'Secure session cookie', $cookie || ! $production ? 'ok' : 'warn', $cookie ? 'Enabled' : 'Off', $cookie || ! $production ? null : 'Set SESSION_SECURE_COOKIE=true.');

        $out[] = $this->row('Security', 'Admin path', config('admin.path') === 'admin' ? 'warn' : 'ok', '/'.config('admin.path'), config('admin.path') === 'admin' ? 'The default /admin path is the first thing bots probe. Consider setting ADMIN_PATH.' : null);

        try {
            $admins = User::permission('pages.edit')->where('is_active', true)->get();
            $without = $admins->filter(fn (User $u) => ! $u->hasTwoFactorEnabled());
            $out[] = $this->row('Security', 'Two-factor for editors', $without->isEmpty() ? 'ok' : 'warn', $without->isEmpty() ? 'All '.$admins->count().' active editors use two-factor' : $without->count().' of '.$admins->count().' active editors have no two-factor', $without->isEmpty() ? null : 'Ask them to enable two-factor under My security.');
        } catch (\Throwable) {
            // Permission tables not seeded: nothing to report.
        }

        $dormant = User::where('is_active', true)->where(fn ($q) => $q->whereNull('last_login_at')->orWhere('last_login_at', '<', now()->subDays(180)))->count();
        $out[] = $this->row('Security', 'Dormant accounts', $dormant > 0 ? 'warn' : 'ok', $dormant.' active account(s) with no sign-in in 180 days', $dormant > 0 ? 'Deactivate accounts that are no longer needed (Users & roles).' : null);

        return $out;
    }

    // ---- helpers --------------------------------------------------------------------------------------

    /** @return array{group: string, name: string, status: string, detail: string, hint: ?string} */
    private function row(string $group, string $name, string $status, string $detail, ?string $hint): array
    {
        return ['group' => $group, 'name' => $name, 'status' => $status, 'detail' => $detail, 'hint' => $hint];
    }

    private function bytes(string|false $ini): int
    {
        $ini = trim((string) $ini);
        $n = (int) $ini;

        return match (strtolower(substr($ini, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }

    private function human(float|int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($bytes < 1024 || $unit === 'TB') {
                return round($bytes, $unit === 'B' ? 0 : 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return (string) $bytes;
    }
}
