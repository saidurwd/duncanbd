<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Records security-relevant events that the framework raises but nothing else logs: lockouts, two-factor
 * changes and failures, recovery-code regeneration and completed password resets. They appear under
 * Security & audit → Security events (log name "security").
 */
class LogSecurityEvents
{
    public function handle(Lockout|PasswordReset|TwoFactorAuthenticationEnabled|TwoFactorAuthenticationConfirmed|TwoFactorAuthenticationDisabled|TwoFactorAuthenticationFailed|RecoveryCodesGenerated $event): void
    {
        [$description, $user] = match (true) {
            $event instanceof Lockout => ['sign-in locked out (too many attempts)', null],
            $event instanceof PasswordReset => ['password reset completed', $event->user],
            $event instanceof TwoFactorAuthenticationEnabled => ['two-factor setup started', $event->user],
            $event instanceof TwoFactorAuthenticationConfirmed => ['two-factor enabled', $event->user],
            $event instanceof TwoFactorAuthenticationDisabled => ['two-factor disabled', $event->user],
            $event instanceof TwoFactorAuthenticationFailed => ['two-factor code rejected', $event->user],
            default => ['recovery codes regenerated', $event->user],
        };

        $properties = ['ip' => Request::ip(), 'user_agent' => Request::userAgent()];
        if ($event instanceof Lockout) {
            // Never store the password; the e-mail identifies the targeted account.
            $properties['email'] = $event->request->input('email');
        }

        $log = activity('security')->withProperties($properties);

        if ($user instanceof Model) {
            $log->causedBy($user);
        }

        $log->log($description);
    }
}
