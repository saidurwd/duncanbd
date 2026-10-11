<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin guard rails: optional IP allowlist, active account, mandatory 2FA, idle timeout.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = config('admin.ip_allowlist');

        if ($allowlist !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowlist)) {
            abort(404);
        }

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'This account has been deactivated.']);
        }

        $last = $request->session()->get('admin.last_activity');
        if ($last !== null && now()->timestamp - $last > config('admin.idle_timeout') * 60) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Your session expired. Please sign in again.']);
        }
        $request->session()->put('admin.last_activity', now()->timestamp);

        $onSecurityPage = $request->routeIs('admin.security*') || $request->is('user/*') || $request->is('two-factor-*');

        if (! $user->hasTwoFactorEnabled() && ! $onSecurityPage) {
            return redirect()->route('admin.security')
                ->with('status', 'Two-factor authentication is mandatory. Please set it up to continue.');
        }

        return $next($request);
    }
}
