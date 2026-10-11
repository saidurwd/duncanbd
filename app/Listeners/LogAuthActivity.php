<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Writes sign-in activity to the audit log (spatie/laravel-activitylog).
 */
class LogAuthActivity
{
    public function handle(Login|Logout|Failed $event): void
    {
        [$description, $causer] = match (true) {
            $event instanceof Login => ['login', $event->user],
            $event instanceof Logout => ['logout', $event->user],
            default => ['login failed', $event->user],
        };

        $log = activity('auth')->withProperties([
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'email' => $event instanceof Failed ? ($event->credentials['email'] ?? null) : null,
        ]);

        if ($causer instanceof Model) {
            $log->causedBy($causer);
        }

        $log->log($description);
    }
}
