<?php

use App\Domain\System\HealthCheck;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run --only-db')->daily()->at('01:30');
Schedule::command('backup:run')->weeklyOn(0, '02:00');
Schedule::command('backup:monitor')->daily()->at('03:00');
Schedule::command('model:prune')->daily();
Schedule::command('pages:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('careers:close-expired')->hourly();
Schedule::command('privacy:prune')->daily()->at('02:30');
// Audit/activity log retention: entries older than 60 days are deleted nightly (--force: required outside local).
Schedule::command('activitylog:clean --days=60 --force')->daily()->at('03:30');

// Lets System health prove the cron entry is alive.
Schedule::call(fn () => Cache::put(HealthCheck::SCHEDULER_KEY, now()->timestamp, now()->addHour()))->name('system-heartbeat')->everyMinute();
