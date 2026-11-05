<?php

namespace App\Domain\Audit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-side queries for the Security & audit screens. One table (spatie/laravel-activitylog) feeds all of them;
 * the screens differ only in which log names and descriptions they select.
 */
class AuditLog
{
    /** Identity-log descriptions that matter for security reviews. */
    public const SECURITY_IDENTITY = ['user created', 'role changed', 'password set by administrator', 'password reset link sent', 'two-factor reset', 'password changed by user'];

    /** Record changes (what was created/edited/deleted), excluding sign-in and security noise. */
    public static function audit(): Builder
    {
        return Activity::query()->with('causer')->whereNotIn('log_name', ['auth', 'security']);
    }

    /** Everything a person did, sign-ins included. */
    public static function activity(): Builder
    {
        return Activity::query()->with('causer');
    }

    public static function logins(): Builder
    {
        return Activity::query()->with('causer')->where('log_name', 'auth');
    }

    public static function securityEvents(): Builder
    {
        return Activity::query()->with('causer')->where(fn (Builder $q) => $q
            ->where('log_name', 'security')
            ->orWhere(fn ($w) => $w->where('log_name', 'auth')->where('description', 'login failed'))
            ->orWhere(fn ($w) => $w->where('log_name', 'identity')->whereIn('description', self::SECURITY_IDENTITY)));
    }

    /** Shared filters: user, free text, date range. Unknown or malformed values are ignored, never an error. */
    public static function filter(Builder $query, Request $request): Builder
    {
        $search = trim((string) $request->query('q'));

        return $query
            ->when(ctype_digit((string) $request->query('user')), fn ($q) => $q->where('causer_id', (int) $request->query('user')))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where(fn ($w) => $w->where('description', 'like', $like)->orWhere('subject_type', 'like', $like)->orWhere('properties', 'like', $like));
            })
            ->when(self::date($request->query('from')), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when(self::date($request->query('to')), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()));
    }

    private static function date(mixed $value): ?\Carbon\CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $value, 'Asia/Dhaka');
        } catch (\Throwable) {
            return null;
        }
    }

    /** "App\Domain\Cms\Models\Page" + 12 -> "Page #12". */
    public static function subjectLabel(Activity $activity): string
    {
        if (! $activity->subject_type) {
            return '—';
        }

        return class_basename($activity->subject_type).($activity->subject_id ? ' #'.$activity->subject_id : '');
    }

    /** Severity used to colour security events: danger / warning / info. */
    public static function severity(Activity $activity): string
    {
        return match (true) {
            str_contains($activity->description, 'locked out') => 'danger',
            $activity->description === 'login failed', $activity->description === 'two-factor code rejected' => 'warning',
            in_array($activity->description, ['two-factor disabled', 'two-factor reset', 'password set by administrator', 'role changed'], true) => 'warning',
            default => 'info',
        };
    }

    /**
     * Accounts or addresses with a burst of failed sign-ins in the last hour (possible guessing).
     *
     * @return list<array{who: string, count: int}>
     */
    public static function bruteForceSuspects(int $threshold = 5): array
    {
        $rows = Activity::query()->where('created_at', '>=', now()->subHour())
            ->where(fn ($q) => $q->where(fn ($w) => $w->where('log_name', 'auth')->where('description', 'login failed'))
                ->orWhere(fn ($w) => $w->where('log_name', 'security')->where('description', 'like', 'sign-in locked out%')))
            ->get(['properties']);

        $counts = [];
        foreach ($rows as $row) {
            foreach (array_filter([$row->getProperty('email'), $row->getProperty('ip')]) as $who) {
                $counts[$who] = ($counts[$who] ?? 0) + 1;
            }
        }
        arsort($counts);

        return collect($counts)->filter(fn ($n) => $n >= $threshold)->map(fn ($n, $who) => ['who' => (string) $who, 'count' => $n])->values()->all();
    }
}
