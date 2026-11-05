<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLog;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Support\Csv;
use App\Support\UserAgent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Security & audit screens. Read-only: the log is never edited or deleted from the admin. */
class AuditController extends Controller
{
    /** Audit log: who created, changed or deleted which record. */
    public function audit(Request $request): View
    {
        return view('admin.audit.audit', [
            'entries' => $this->auditQuery($request)->latest('id')->paginate(40)->withQueryString(),
            'users' => $this->users(),
            'modules' => Activity::query()->whereNotIn('log_name', ['auth', 'security'])->distinct()->orderBy('log_name')->pluck('log_name'),
        ]);
    }

    public function show(Activity $activity): View
    {
        $activity->load('causer');

        return view('admin.audit.show', ['entry' => $activity]);
    }

    public function export(Request $request): StreamedResponse
    {
        return Csv::download('audit-log-'.now()->format('Y-m-d').'.csv', ['when (Dhaka)', 'module', 'action', 'record', 'user', 'ip'],
            $this->auditQuery($request)->latest('id')->limit(5000)->get()->map(fn (Activity $a) => [
                $a->created_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'), $a->log_name, $a->description, AuditLog::subjectLabel($a), $a->causer?->name ?? 'System', $a->getProperty('ip'),
            ]));
    }

    /** Activity log: every action by every person, newest first, with a per-person summary. */
    public function activity(Request $request): View
    {
        $summary = Activity::query()->where('causer_type', User::class)->where('created_at', '>=', now()->subDays(30))
            ->select('causer_id', DB::raw('count(*) as actions'), DB::raw('max(created_at) as last_at'))
            ->groupBy('causer_id')->orderByDesc('actions')->limit(8)->get();
        $names = User::whereIn('id', $summary->pluck('causer_id'))->pluck('name', 'id');

        return view('admin.audit.activity', [
            'entries' => AuditLog::filter(AuditLog::activity(), $request)
                ->when($request->query('module'), fn ($q, $m) => $q->where('log_name', (string) $m))
                ->latest('id')->paginate(40)->withQueryString(),
            'users' => $this->users(),
            'modules' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name'),
            'summary' => $summary->map(fn ($r) => ['name' => $names[$r->causer_id] ?? 'Deleted user', 'actions' => $r->actions, 'last' => \Carbon\CarbonImmutable::parse($r->last_at)]),
            'today' => Activity::whereDate('created_at', now()->timezone('Asia/Dhaka')->toDateString())->count(),
        ]);
    }

    /** Login history: successful and failed sign-ins and sign-outs. */
    public function logins(Request $request): View
    {
        $outcome = (string) $request->query('outcome');

        return view('admin.audit.logins', [
            'entries' => AuditLog::filter(AuditLog::logins(), $request)
                ->when(in_array($outcome, ['login', 'logout', 'login failed'], true), fn ($q) => $q->where('description', $outcome))
                ->latest('id')->paginate(40)->withQueryString(),
            'users' => $this->users(),
            'stats' => [
                'ok24' => Activity::where('log_name', 'auth')->where('description', 'login')->where('created_at', '>=', now()->subDay())->count(),
                'failed24' => Activity::where('log_name', 'auth')->where('description', 'login failed')->where('created_at', '>=', now()->subDay())->count(),
                'ips7' => Activity::where('log_name', 'auth')->where('description', 'login')->where('created_at', '>=', now()->subDays(7))->get(['properties'])->map(fn ($a) => $a->getProperty('ip'))->filter()->unique()->count(),
            ],
            'ua' => fn (?string $agent) => UserAgent::summary($agent),
        ]);
    }

    /** Security events: failed sign-ins, lockouts, two-factor and password changes, role changes. */
    public function events(Request $request): View
    {
        return view('admin.audit.events', [
            'entries' => AuditLog::filter(AuditLog::securityEvents(), $request)->latest('id')->paginate(40)->withQueryString(),
            'users' => $this->users(),
            'suspects' => AuditLog::bruteForceSuspects(),
            'stats' => [
                'failed24' => Activity::where('log_name', 'auth')->where('description', 'login failed')->where('created_at', '>=', now()->subDay())->count(),
                'lockouts7' => Activity::where('log_name', 'security')->where('description', 'like', 'sign-in locked out%')->where('created_at', '>=', now()->subDays(7))->count(),
                'changes30' => Activity::where(fn ($q) => $q->where('log_name', 'security')->orWhere(fn ($w) => $w->where('log_name', 'identity')->whereIn('description', AuditLog::SECURITY_IDENTITY)))
                    ->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    private function auditQuery(Request $request)
    {
        return AuditLog::filter(AuditLog::audit(), $request)
            ->when($request->query('module'), fn ($q, $m) => $q->where('log_name', (string) $m))
            ->when(in_array($request->query('event'), ['created', 'updated', 'deleted'], true), fn ($q) => $q->where('event', $request->query('event')));
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function users()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }
}
